<?php
/**
 * Plugin Name: BugSneak
 * Plugin URI:  https://bugsneak.com
 * Description: Crash Intelligence System — intercepts PHP Fatal Errors, Exceptions, and Warnings with beautiful diagnostics.
 * Version:     1.3.5
 * Author:      Chetan Upare
 * Author URI:  https://github.com/chetanupare/
 * License:     GPLv2 or later
 * Text Domain: bugsneak
 * Domain Path: /languages
 * Requires at least: 6.2
 * Stable tag: 1.3.5
 *
 * @package BugSneak
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants.
define( 'BUGSNEAK_VERSION', '1.3.5' );
define( 'BUGSNEAK_PATH', plugin_dir_path( __FILE__ ) );
define( 'BUGSNEAK_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoloader for BugSneak classes (PSR-4 style).
 *
 * @param string $class Class name.
 */
spl_autoload_register(
	function ( $class ) {
		$prefix   = 'BugSneak\\';
		$base_dir = BUGSNEAK_PATH . 'src/';

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );
		$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

/**
 * Initialize the plugin architecture.
 */
function bugsneak_init() {
	// Initialize core engine (will drain EarlyBoot buffer if present).
	BugSneak\Core\Engine::get_instance();

	// Initialize admin, API, and settings controllers.
	BugSneak\Admin\DashboardController::get_instance();
	BugSneak\Api\RestController::get_instance();
	BugSneak\Admin\Settings::get_instance();
}

add_action( 'plugins_loaded', 'bugsneak_init' );

// ── MU Loader Management ───────────────────────────────────────────────────

/**
 * Get the path to the MU loader file.
 *
 * @return string
 */
function bugsneak_mu_loader_path() {
	return WPMU_PLUGIN_DIR . '/bugsneak-loader.php';
}

/**
 * Get the content for the MU loader file.
 *
 * @return string
 */
function bugsneak_mu_loader_content() {
	$earlyboot_path = str_replace( '\\', '/', BUGSNEAK_PATH ) . 'src/EarlyBoot.php';
	$version        = BUGSNEAK_VERSION;

	return "<?php
/**
 * BugSneak MU Loader — Auto-generated on plugin activation.
 *
 * This file loads BugSneak's early-boot crash capture BEFORE any normal
 * plugin. It registers error/exception/shutdown handlers as early as
 * possible for maximum WSOD capture reliability.
 *
 * DO NOT EDIT — this file is managed automatically by BugSneak.
 * It will be removed when the plugin is deactivated.
 *
 * @generated BugSneak v" . $version . "
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\$earlyboot = '" . $earlyboot_path . "';

if ( file_exists( \$earlyboot ) ) {
	require_once \$earlyboot;
}";
}

/**
 * Install the MU loader on plugin activation.
 */
function bugsneak_activate() {
	// 1. Create database table.
	BugSneak\Database\Schema::create_table();

	// 2. Install MU loader.
	$mu_path = bugsneak_mu_loader_path();

	// Ensure mu-plugins directory exists.
	if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
		wp_mkdir_p( WPMU_PLUGIN_DIR );
	}

	// Safety: only write if file doesn't exist, or if it's ours.
	$should_write = true;

	if ( file_exists( $mu_path ) ) {
		$existing = file_get_contents( $mu_path );
		// Only overwrite if it's a BugSneak file.
		if ( strpos( $existing, 'BugSneak MU Loader' ) === false ) {
			$should_write = false; // Not our file — don't touch it.
		}
	}

	if ( $should_write ) {
		$written = @file_put_contents( $mu_path, bugsneak_mu_loader_content() );

		if ( false === $written ) {
			// Permission failure — show admin notice, but don't crash activation.
			set_transient( 'bugsneak_mu_notice', 'failed', 60 );
		}
	}
}

/**
 * Remove MU loader on plugin deactivation.
 */
function bugsneak_deactivate() {
	$mu_path = bugsneak_mu_loader_path();

	if ( file_exists( $mu_path ) ) {
		$content = file_get_contents( $mu_path );
		// Only delete if it's our file.
		if ( strpos( $content, 'BugSneak MU Loader' ) !== false ) {
			wp_delete_file( $mu_path );
		}
	}

	// Clear scheduled cron.
	$timestamp = wp_next_scheduled( 'bugsneak_cleanup_event' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'bugsneak_cleanup_event' );
	}
}

register_activation_hook( __FILE__, 'bugsneak_activate' );
register_deactivation_hook( __FILE__, 'bugsneak_deactivate' );

// ── MU Installation Notice ─────────────────────────────────────────────────

add_action( 'admin_notices', function () {
	$notice = get_transient( 'bugsneak_mu_notice' );
	if ( ! $notice ) {
		return;
	}
	delete_transient( 'bugsneak_mu_notice' );

	if ( 'failed' === $notice ) {
		echo '<div class="notice notice-warning is-dismissible"><p>';
		echo '<strong>BugSneak:</strong> Could not create MU loader at <code>' . esc_html( WPMU_PLUGIN_DIR ) . '</code>. ';
		echo 'Early crash capture may be reduced. Please ensure the <code>mu-plugins</code> directory is writable.';
		echo '</p></div>';
	}
} );