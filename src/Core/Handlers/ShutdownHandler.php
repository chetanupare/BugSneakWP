<?php
/**
 * Shutdown Handler for BugSneak.
 *
 * @package BugSneak\Core\Handlers
 */

namespace BugSneak\Core\Handlers;

use BugSneak\Core\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ShutdownHandler
 */
class ShutdownHandler {

	/**
	 * @var Engine
	 */
	private $engine;

	/**
	 * ShutdownHandler constructor.
	 *
	 * @param Engine $engine Core engine instance.
	 */
	public function __construct( Engine $engine ) {
		$this->engine = $engine;
		register_shutdown_function( array( $this, 'handle' ) );
	}

	/**
	 * Handle the PHP shutdown process.
	 */
	public function handle() {
		$error = error_get_last();

		if ( $error && ( $error['type'] & ( E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR ) ) ) {

			$type = $this->get_error_type_name( $error['type'] );

			$this->engine->log_error(
				$type . ' (Fatal)',
				$error['message'],
				$error['file'],
				$error['line'],
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Core feature for providing error context
				debug_backtrace()
			);

			// Render the beautiful diagnostic overlay for front-end crashes.
			$this->engine->render_overlay(
				array(
					'type'    => $type,
					'message' => $error['message'],
					'file'    => $error['file'],
					'line'    => $error['line'],
				)
			);
		}
	}

	/**
	 * Map PHP error constants to strings.
	 *
	 * @param int $type Error type constant.
	 * @return string
	 */
	private function get_error_type_name( $type ) {
		$errors = array(
			E_ERROR         => 'PHP Fatal Error',
			E_PARSE         => 'PHP Parse Error',
			E_CORE_ERROR    => 'PHP Core Error',
			E_COMPILE_ERROR => 'PHP Compile Error',
			E_USER_ERROR    => 'User Error',
		);
		return $errors[ $type ] ?? 'Fatal Error';
	}
}
