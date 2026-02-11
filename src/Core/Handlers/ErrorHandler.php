<?php
/**
 * Error Handler for BugSneak.
 *
 * @package BugSneak\Core\Handlers
 */

namespace BugSneak\Core\Handlers;

use BugSneak\Core\Engine;
use BugSneak\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ErrorHandler
 */
class ErrorHandler {

	/**
	 * @var Engine
	 */
	private $engine;

	/**
	 * Tracks errors captured in this request to enforce limits.
	 *
	 * @var int
	 */
	private static $request_error_count = 0;

	/**
	 * Tracks error hashes seen in this request for log-once mode.
	 *
	 * @var array
	 */
	private static $seen_hashes = array();

	/**
	 * @var callable|null
	 */
	private $previous_handler;

	/**
	 * ErrorHandler constructor.
	 *
	 * @param Engine $engine Core engine instance.
	 */
	public function __construct( Engine $engine ) {
		$this->engine = $engine;
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Core feature of error logging plugin
		$this->previous_handler = set_error_handler( array( $this, 'handle' ) );
	}

	/**
	 * Handle PHP errors.
	 *
	 * @param int    $errno   Error level.
	 * @param string $errstr  Error message.
	 * @param string $errfile Error file.
	 * @param int    $errline Error line.
	 * @return bool
	 */
	public function handle( $errno, $errstr, $errfile, $errline ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting -- Required for error capture
		if ( ! ( error_reporting() & $errno ) ) {
			return false;
		}

		$type = $this->get_error_type_name( $errno );

		// ── Setting Guards ──────────────────────────────────────────────

		// 1. Capture mode check.
		$mode = Settings::get( 'capture_mode', 'debug' );
		if ( 'debug' === $mode && ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) ) {
			return false;
		}

		// 2. Error level filtering.
		$levels = Settings::get( 'error_levels', array() );
		if ( ! $this->is_level_enabled( $errno, $levels ) ) {
			return false;
		}

		// 3. Frontend/Admin disable.
		if ( is_admin() && Settings::get( 'disable_admin', false ) ) {
			return false;
		}
		if ( ! is_admin() && Settings::get( 'disable_frontend', false ) ) {
			return false;
		}

		// 4. Admin-only mode.
		if ( Settings::get( 'admin_only', false ) && ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		// 5. Max errors per request.
		$max = Settings::get( 'max_errors_per_request', 10 );
		if ( $max > 0 && self::$request_error_count >= $max ) {
			return false;
		}

		// 6. Log once per request.
		if ( Settings::get( 'log_once_per_request', false ) ) {
			$hash = md5( $errstr . '|' . $errfile . '|' . $errline );
			if ( isset( self::$seen_hashes[ $hash ] ) ) {
				return false;
			}
			self::$seen_hashes[ $hash ] = true;
		}

		// ── Logging ─────────────────────────────────────────────────────

		++self::$request_error_count;

		$this->engine->log_error(
			$type,
			$errstr,
			$errfile,
			$errline,
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Core feature for providing error context
			debug_backtrace()
		);

		// Render overlay for non-fatal errors in debug mode.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$this->engine->render_overlay(
				array(
					'type'    => $type,
					'message' => $errstr,
					'file'    => $errfile,
					'line'    => $errline,
				)
			);
		}

		if ( $this->previous_handler ) {
			return call_user_func( $this->previous_handler, $errno, $errstr, $errfile, $errline );
		}

		return false;
	}

	/**
	 * Check if an error level is enabled in settings.
	 *
	 * @param int   $errno  PHP error constant.
	 * @param array $levels Enabled levels from settings.
	 * @return bool
	 */
	private function is_level_enabled( $errno, $levels ) {
		$map = array(
			E_WARNING           => 'warnings',
			E_NOTICE            => 'notices',
			E_USER_WARNING      => 'warnings',
			E_USER_NOTICE       => 'notices',
			E_DEPRECATED        => 'deprecated',
			E_USER_DEPRECATED   => 'deprecated',
			E_STRICT            => 'strict',
			E_RECOVERABLE_ERROR => 'fatal',
		);

		$key = $map[ $errno ] ?? 'notices';
		return ! empty( $levels[ $key ] );
	}

	/**
	 * Map PHP error constants to strings.
	 *
	 * @param int $type Error type constant.
	 * @return string
	 */
	private function get_error_type_name( $type ) {
		$errors = array(
			E_WARNING           => 'Warning',
			E_NOTICE            => 'Notice',
			E_USER_WARNING      => 'User Warning',
			E_USER_NOTICE       => 'User Notice',
			E_DEPRECATED        => 'Deprecated',
			E_USER_DEPRECATED   => 'User Deprecated',
			E_STRICT            => 'Strict Standards',
			E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
		);
		return $errors[ $type ] ?? 'PHP Error';
	}
}
