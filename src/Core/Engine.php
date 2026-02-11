<?php
/**
 * Core Engine for BugSneak.
 *
 * @package BugSneak\Core
 */

namespace BugSneak\Core;

use BugSneak\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Engine
 *
 * The central manager for error interception and logging.
 */
class Engine {

	/**
	 * @var Engine|null
	 */
	private static $instance = null;

	/**
	 * @return Engine
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->initialize_handlers();
		$this->drain_early_buffer();
	}

	private function initialize_handlers() {
		// These replace EarlyBoot's anonymous closures with the full
		// handler pipeline (Settings guards, overlay rendering, etc).
		new Handlers\ErrorHandler( $this );
		new Handlers\ExceptionHandler( $this );
		new Handlers\ShutdownHandler( $this );
	}

	/**
	 * Drain errors captured by EarlyBoot before the full engine loaded.
	 * This processes any crashes that happened during early WordPress boot.
	 */
	private function drain_early_buffer() {
		if ( ! class_exists( 'BugSneak_Early_Buffer', false ) ) {
			return; // MU loader wasn't active.
		}

		$buffered = \BugSneak_Early_Buffer::drain();

		foreach ( $buffered as $error ) {
			switch ( $error['handler'] ) {
				case 'error':
					$type_map = [
						E_ERROR           => 'PHP Fatal Error',
						E_WARNING         => 'PHP Warning',
						E_NOTICE          => 'PHP Notice',
						E_PARSE           => 'PHP Parse Error',
						E_DEPRECATED      => 'PHP Deprecated',
						E_STRICT          => 'PHP Strict',
						E_USER_ERROR      => 'User Error',
						E_USER_WARNING    => 'User Warning',
						E_USER_NOTICE     => 'User Notice',
						E_USER_DEPRECATED => 'User Deprecated',
					];
					$type = $type_map[ $error['errno'] ] ?? 'PHP Error';
					$this->log_error( $type, $error['message'], $error['file'], $error['line'], [] );
					break;

				case 'exception':
					$this->log_error(
						$error['class'] ?? 'Exception',
						$error['message'],
						$error['file'],
						$error['line'],
						$error['trace'] ?? []
					);
					break;

				case 'shutdown':
					$type_map = [
						E_ERROR         => 'PHP Fatal Error',
						E_PARSE         => 'PHP Parse Error',
						E_CORE_ERROR    => 'PHP Core Error',
						E_COMPILE_ERROR => 'PHP Compile Error',
						E_USER_ERROR    => 'User Error',
					];
					$type = $type_map[ $error['type'] ] ?? 'Fatal Error';
					$this->log_error( $type . ' (Fatal)', $error['message'], $error['file'], $error['line'], [] );
					break;
			}
		}
	}

	/**
	 * Log an error into the database with full context.
	 *
	 * @param string $type    Error type.
	 * @param string $message Error message.
	 * @param string $file    File path.
	 * @param int    $line    Line number.
	 * @param array  $trace   Stack trace.
	 */
	public function log_error( $type, $message, $file, $line, $trace ) {
		try {
			$severity = $this->classify_severity( $type, $message );

			$data = [
				'type'            => $type,
				'message'         => $message,
				'file'            => $file,
				'line'            => $line,
				'trace'           => $trace,
				'severity'        => $severity,
				'wp_version'      => $GLOBALS['wp_version'] ?? 'Unknown',
				'active_theme'    => wp_get_theme()->get( 'Name' ) . ' (' . wp_get_theme()->get( 'Version' ) . ')',
				'culprit'         => \BugSneak\Culprit\Detector::detect( $file ),
				'code_snippet'    => $this->get_file_snippet( $file, $line ),
				'request_context' => $this->get_request_context(),
				'env_context'     => $this->get_env_context(),
			];

			\BugSneak\Database\Logger::insert( $data );
		} catch ( \Throwable $e ) {
			// Fail silently — never let the logger crash the site.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'BugSneak Logger Failed: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Classify error severity.
	 *
	 * @param string $type    Internal error type.
	 * @param string $message Error message.
	 * @return string
	 */
	private function classify_severity( $type, $message ) {
		$type_lower = strtolower( $type );
		if ( false !== strpos( $type_lower, 'fatal' ) || false !== strpos( $type_lower, 'error' ) ) {
			return 'Fatal';
		}
		if ( false !== strpos( $type_lower, 'warning' ) ) {
			return 'Warning';
		}
		if ( false !== strpos( $type_lower, 'deprecated' ) ) {
			return 'Deprecated';
		}
		if ( false !== strpos( strtolower( $message ), 'memory' ) ) {
			return 'Memory';
		}
		return 'Notice';
	}

	/**
	 * Capture request context based on settings.
	 *
	 * @return array
	 */
	private function get_request_context() {
		$ctx = [];

		if ( Settings::get( 'capture_get', true ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Intentional capture of request context for error logging
			$ctx['get'] = $_GET;
		}
		if ( Settings::get( 'capture_post', true ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Intentional capture of request context for error logging
			$ctx['post'] = $_POST;
		}
		if ( Settings::get( 'capture_server', true ) ) {
			$ctx['server'] = array_intersect_key(
				$_SERVER,
				array_flip( [ 'HTTP_HOST', 'REQUEST_URI', 'REQUEST_METHOD', 'REMOTE_ADDR', 'HTTP_USER_AGENT' ] )
			);
		}
		if ( Settings::get( 'capture_cookies', false ) ) {
			$ctx['cookies'] = $_COOKIE;
		}

		return $ctx;
	}

	/**
	 * Capture environment and identity context based on settings.
	 *
	 * @return array
	 */
	private function get_env_context() {
		$ctx = [];

		if ( Settings::get( 'capture_user', true ) ) {
			$user = wp_get_current_user();
			$ctx['user_id']    = $user ? $user->ID : 0;
			$ctx['user_roles'] = $user ? $user->roles : [];
		}

		if ( Settings::get( 'capture_filter', true ) ) {
			$ctx['current_filter'] = current_filter();
		}

		if ( Settings::get( 'capture_memory', true ) ) {
			$ctx['memory_usage'] = memory_get_usage( true );
			$ctx['peak_memory']  = memory_get_peak_usage( true );
		}

		if ( Settings::get( 'capture_env', false ) ) {
			$ctx['php_version'] = PHP_VERSION;
			$ctx['server_os']   = php_uname( 's' );
		}

		return $ctx;
	}

	/**
	 * Extract code snippet around the error, respecting settings.
	 *
	 * @param string $file Path to file.
	 * @param int    $line Error line.
	 * @return array
	 */
	public function get_file_snippet( $file, $line ) {
		if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
			return [];
		}

		// Max file size guard.
		$max_kb = Settings::get( 'max_file_size_kb', 512 );
		if ( filesize( $file ) > $max_kb * 1024 ) {
			return [ 'lines' => [], 'target' => (int) $line, 'truncated' => true ];
		}

		$lines_before = Settings::get( 'lines_before', 5 );
		$lines_after  = Settings::get( 'lines_after', 5 );

		$file_lines = file( $file );
		$count      = count( $file_lines );
		$start      = max( 0, $line - $lines_before - 1 );
		$end        = min( $count, $line + $lines_after );
		$result     = [];

		for ( $i = $start; $i < $end; $i++ ) {
			$result[ $i + 1 ] = rtrim( $file_lines[ $i ] );
		}

		return [
			'lines'  => $result,
			'target' => (int) $line,
		];
	}

	/**
	 * Renders the diagnostic overlay.
	 *
	 * @param array $data Error data.
	 */
	public function render_overlay( $data ) {
		try {
			$mode = Settings::get( 'capture_mode', 'debug' );

			// In debug mode, only show if WP_DEBUG is on.
			if ( 'debug' === $mode && ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) ) {
				return;
			}

			// In production mode, only show to admins.
			if ( 'production' === $mode && ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Required to suppress output during overlay rendering
			ini_set( 'display_errors', '0' );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting -- Required to suppress output during overlay rendering
			error_reporting( 0 );

			while ( ob_get_level() ) {
				ob_clean();
				ob_end_clean();
			}

			if ( ! isset( $data['culprit'] ) ) {
				$data['culprit'] = \BugSneak\Culprit\Detector::detect( $data['file'] );
			}
			if ( ! isset( $data['code_snippet'] ) ) {
				$data['code_snippet'] = $this->get_file_snippet( $data['file'], $data['line'] );
			}
			if ( ! isset( $data['severity'] ) ) {
				$data['severity'] = $this->classify_severity( $data['type'] ?? 'Fatal', $data['message'] ?? '' );
			}

			$template_path = BUGSNEAK_PATH . 'src/Core/Templates/DiagnosticOverlay.php';

			if ( file_exists( $template_path ) ) {
				include $template_path;
			} else {
				echo '<div style="background:#0f172a; color:#f8fafc; padding:40px; font-family:sans-serif; min-height:100vh;">';
				echo '<h1 style="color:#ef4444;">' . esc_html( $data['message'] ) . '</h1>';
				echo '<p style="color:#6366f1;">BugSneak: ' . esc_html( $data['file'] ) . ' line ' . (int) $data['line'] . '</p>';
				echo '</div>';
			}

			exit();
		} catch ( \Throwable $e ) {
			// If overlay fails, just let PHP's own error handling (or other handlers) take over.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'BugSneak Overlay Failed: ' . $e->getMessage() );
			}
		}
	}
}
