<?php
/**
 * Exception Handler for BugSneak.
 *
 * @package BugSneak\Core\Handlers
 */

namespace BugSneak\Core\Handlers;

use BugSneak\Core\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exception Handler for BugSneak.
 */
class ExceptionHandler {

	/**
	 * @var Engine
	 */
	private $engine;

	/**
	 * @var callable|null
	 */
	private $previous_handler;

	/**
	 * ExceptionHandler constructor.
	 *
	 * @param Engine $engine Core engine instance.
	 */
	public function __construct( Engine $engine ) {
		$this->engine           = $engine;
		$this->previous_handler = set_exception_handler( array( $this, 'handle' ) );
	}

	/**
	 * Handle uncaught exceptions.
	 *
	 * @param \Throwable $exception The exception object.
	 */
	public function handle( $exception ) {
		$this->engine->log_error(
			get_class( $exception ),
			$exception->getMessage(),
			$exception->getFile(),
			$exception->getLine(),
			$exception->getTrace()
		);

		// Render the beautiful diagnostic overlay for front-end crashes.
		$this->engine->render_overlay(
			array(
				'type'    => 'Uncaught Exception',
				'message' => $exception->getMessage(),
				'file'    => $exception->getFile(),
				'line'    => $exception->getLine(),
			)
		);

		if ( $this->previous_handler ) {
			call_user_func( $this->previous_handler, $exception );
		}
	}
}
