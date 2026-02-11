<?php
/**
 * Settings Manager for BugSneak.
 *
 * @package BugSneak\Admin
 */

namespace BugSneak\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 *
 * Central configuration manager. All settings stored as a single JSON option.
 */
class Settings {

	/**
	 * WordPress option key.
	 */
	const OPTION_KEY = 'bugsneak_settings';

	/**
	 * Default settings for all 10 sections.
	 */
	const DEFAULTS = [
		// 1. Error Levels
		'error_levels'         => [
			'fatal'       => true,
			'parse'       => true,
			'exceptions'  => true,
			'warnings'    => true,
			'notices'     => true,
			'deprecated'  => true,
			'strict'      => false,
		],
		'capture_mode'         => 'debug', // 'debug' or 'production'

		// 2. Grouping
		'grouping_enabled'     => true,
		'max_occurrences'      => 0, // 0 = unlimited
		'grouping_reset_mins'  => 0, // 0 = never reset
		'merge_stack_only'     => false,

		// 3. Database & Retention
		'retention_days'       => 30,
		'max_rows'             => 10000,
		'cleanup_frequency'    => 'daily', // 'daily', 'weekly'

		// 4. Culprit Detection
		'culprit_strategy'     => 'first', // 'first', 'deepest'
		'ignore_core'          => true,
		'ignore_mu_plugins'    => false,
		'blacklisted_plugins'  => '',

		// 5. Code Snippet
		'lines_before'         => 5,
		'lines_after'          => 5,
		'syntax_highlight'     => true,
		'code_dark_theme'      => true,
		'max_file_size_kb'     => 512,

		// 6. Context Capture
		'capture_get'          => true,
		'capture_post'         => true,
		'capture_server'       => true,
		'capture_user'         => true,
		'capture_cookies'      => false,
		'capture_env'          => false,
		'capture_memory'       => true,
		'capture_filter'       => true,

		// 7. Performance & Safety
		'disable_frontend'     => false,
		'disable_admin'        => false,
		'admin_only'           => false,
		'safe_mode'            => false,
		'log_once_per_request' => false,
		'max_errors_per_request' => 10,

		// 8. Notifications (v2.0 stub)
		'notify_email'         => false,
		'notify_slack'         => false,
		'notify_digest'        => false,
		'notify_webhook'       => false,
		'email_address'        => '',
		'slack_webhook_url'    => '',

		// 9. UI Preferences
		'ui_theme'             => 'dark',
		'ui_compact'           => false,
		'ui_expand_traces'     => false,
		'ui_show_sidebar'      => true,

		// 10. Developer Mode
		'developer_mode'       => false,

		// 11. AI Integration
		'ai_enabled'           => false,
		'ai_provider'          => 'gemini', // 'gemini' or 'openai'
		'ai_gemini_key'        => '',
		'ai_openai_key'        => '',
		'ai_gemini_model'      => 'gemini-2.0-flash',
		'ai_openai_model'      => 'gpt-4o-mini',
	];

	/**
	 * Instance of the class.
	 *
	 * @var Settings|null
	 */
	private static $instance = null;

	/**
	 * Cached settings.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Settings
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'init', [ $this, 'schedule_cleanup' ] );
		add_action( 'bugsneak_cleanup_event', [ $this, 'run_cleanup' ] );
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get_all() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$saved = get_option( self::OPTION_KEY, [] );
		if ( is_string( $saved ) ) {
			$saved = json_decode( $saved, true ) ?: [];
		}

		// Deep merge: handle nested arrays like error_levels.
		$merged = self::DEFAULTS;
		foreach ( $saved as $key => $value ) {
			if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
				$merged[ $key ] = array_merge( $merged[ $key ], $value );
			} else {
				$merged[ $key ] = $value;
			}
		}

		self::$cache = $merged;
		return $merged;
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Optional fallback.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$all = self::get_all();
		return $all[ $key ] ?? ( $default ?? ( self::DEFAULTS[ $key ] ?? null ) );
	}

	/**
	 * Save settings (merge with existing).
	 *
	 * @param array $settings Partial or full settings array.
	 * @return bool
	 */
	public static function save( $settings ) {
		$current = self::get_all();

		foreach ( $settings as $key => $value ) {
			if ( ! array_key_exists( $key, self::DEFAULTS ) ) {
				continue; // Skip unknown keys.
			}

			// Sanitize based on expected types.
			$default_type = gettype( self::DEFAULTS[ $key ] );

			if ( 'boolean' === $default_type ) {
				$current[ $key ] = (bool) $value;
			} elseif ( 'integer' === $default_type ) {
				$current[ $key ] = (int) $value;
			} elseif ( 'string' === $default_type ) {
				$current[ $key ] = sanitize_text_field( (string) $value );
			} elseif ( 'array' === $default_type ) {
				$current[ $key ] = is_array( $value ) ? $value : $current[ $key ];
			}
		}

		self::$cache = $current;
		return update_option( self::OPTION_KEY, wp_json_encode( $current ) );
	}

	/**
	 * Get database statistics.
	 *
	 * @return array
	 */
	public static function get_db_stats() {
		global $wpdb;
		$table = $wpdb->prefix . 'bugsneak_logs';

		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM %i", $table ) );

		$size_bytes = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT data_length + index_length FROM information_schema.TABLES WHERE table_schema = %s AND table_name = %s",
				DB_NAME,
				$table
			)
		);

		$oldest = $wpdb->get_var( $wpdb->prepare( "SELECT MIN(created_at) FROM %i", $table ) );
		$newest = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(last_seen) FROM %i", $table ) );

		return [
			'log_count'     => $count,
			'db_size_bytes' => $size_bytes,
			'db_size_human' => size_format( $size_bytes, 1 ),
			'oldest_log'    => $oldest,
			'newest_log'    => $newest,
		];
	}

	/**
	 * Schedule the cleanup cron event.
	 */
	public function schedule_cleanup() {
		$frequency = self::get( 'cleanup_frequency', 'daily' );
		$hook      = 'bugsneak_cleanup_event';

		if ( ! wp_next_scheduled( $hook ) ) {
			wp_schedule_event( time(), $frequency, $hook );
		}
	}

	/**
	 * Run the database cleanup.
	 */
	public function run_cleanup() {
		global $wpdb;
		$table = $wpdb->prefix . 'bugsneak_logs';

		$retention_days = self::get( 'retention_days', 30 );
		$max_rows       = self::get( 'max_rows', 10000 );

		// Delete by age.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM %i WHERE last_seen < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$table,
				(int) $retention_days
			)
		);

		// Prune by row count (keep newest).
		$total_rows = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM %i", $table ) );
		if ( $total_rows > $max_rows ) {
			$to_delete = $total_rows - $max_rows;
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM %i ORDER BY id ASC LIMIT %d",
					$table,
					(int) $to_delete
				)
			);
		}
	}

	/**
	 * Purge all logs.
	 *
	 * @return bool
	 */
	public static function purge_all() {
		global $wpdb;
		$table = $wpdb->prefix . 'bugsneak_logs';
		return (bool) $wpdb->query( $wpdb->prepare( "TRUNCATE TABLE %i", $table ) );
	}
}
