<?php
/**
 * BugSneak — Early Boot
 *
 * Minimal crash capture bootstrap. Loaded by the MU loader BEFORE
 * any normal plugin. Contains ONLY the three PHP error handlers.
 * No admin UI, no settings, no database writes — those happen later
 * when the full plugin loads at `plugins_loaded`.
 *
 * @package BugSneak
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Guard: only run once.
if ( defined( 'TRACELINE_EARLY_LOADED' ) ) {
	return;
}
define( 'TRACELINE_EARLY_LOADED', true );

/**
 * Minimal error buffer — stores errors captured before the full
 * plugin engine is ready. Engine drains this on initialization.
 */
final class BugSneak_Early_Buffer {

	/** @var array Captured errors waiting for the engine. */
	private static $buffer = [];

	/** @var bool Whether the engine has drained the buffer. */
	private static $drained = false;

	/**
	 * Push an error into the buffer.
	 *
	 * @param array $error Captured error data.
	 */
	public static function push( $error ) {
		if ( self::$drained ) {
			return; // Engine is handling errors now.
		}
		// Cap buffer to prevent memory issues during fatal loops.
		if ( count( self::$buffer ) < 50 ) {
			self::$buffer[] = $error;
		}
	}

	/**
	 * Drain and return all buffered errors. Called once by Engine.
	 *
	 * @return array
	 */
	public static function drain() {
		self::$drained = true;
		$errors        = self::$buffer;
		self::$buffer  = [];
		return $errors;
	}

	/**
	 * Whether the engine has taken over.
	 *
	 * @return bool
	 */
	public static function is_drained() {
		return self::$drained;
	}
}

// ── Early Error Handler ─────────────────────────────────────────────────────
/**
 * BugSneak is a crash intelligence system. We must use set_error_handler
 * to intercept PHP warnings and notices before they are lost.
 */
set_error_handler(
	function ( $errno, $errstr, $errfile, $errline ) {
		if ( ! ( error_reporting() & $errno ) ) {
			return false;
		}

		// If the full engine is loaded, let it handle errors.
		if ( BugSneak_Early_Buffer::is_drained() ) {
			return false;
		}

		BugSneak_Early_Buffer::push( [
			'handler' => 'error',
			'errno'   => $errno,
			'message' => $errstr,
			'file'    => $errfile,
			'line'    => $errline,
		] );

		return false; // Allow normal PHP error handling to continue.
	}
);

// ── Early Exception Handler ─────────────────────────────────────────────────

set_exception_handler(
	function ( $exception ) {
		// If the full engine is loaded, it has its own handler.
		if ( BugSneak_Early_Buffer::is_drained() ) {
			throw $exception; // Re-throw for the engine's handler.
		}

		BugSneak_Early_Buffer::push( [
			'handler' => 'exception',
			'class'   => get_class( $exception ),
			'message' => $exception->getMessage(),
			'file'    => $exception->getFile(),
			'line'    => $exception->getLine(),
			'trace'   => $exception->getTrace(),
		] );
	}
);

// ── Early Shutdown Handler ──────────────────────────────────────────────────

register_shutdown_function(
	function () {
		$error = error_get_last();

		if ( ! $error ) {
			return;
		}

		$fatal_mask = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR;
		if ( ! ( $error['type'] & $fatal_mask ) ) {
			return;
		}

		// If the full engine is loaded, its ShutdownHandler handles this.
		if ( BugSneak_Early_Buffer::is_drained() ) {
			return;
		}

		BugSneak_Early_Buffer::push( [
			'handler' => 'shutdown',
			'type'    => $error['type'],
			'message' => $error['message'],
			'file'    => $error['file'],
			'line'    => $error['line'],
		] );

		// Last resort fallback. Only used if the site is failing to boot
		// and the database is unreachable. Essential for diagnostic survival.
		error_log( sprintf(
			'[BugSneak EarlyBoot] Fatal: %s in %s on line %d',
			$error['message'],
			$error['file'],
			$error['line']
		) );
	}
);
