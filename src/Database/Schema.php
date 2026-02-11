<?php
/**
 * Database Schema for BugSneak.
 *
 * @package BugSneak\Database
 */

namespace BugSneak\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Schema
 */
class Schema {

	/**
	 * Table name without prefix.
	 */
	const TABLE_NAME = 'bugsneak_logs';

	/**
	 * Creates the custom database table and handles schema updates.
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . self::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			error_type varchar(100) NOT NULL,
			error_message text NOT NULL,
			file_path varchar(255) NOT NULL,
			line_number int(11) NOT NULL,
			stack_trace longtext NOT NULL,
			wp_version varchar(20) NOT NULL,
			active_theme varchar(100) NOT NULL,
			culprit varchar(255) NOT NULL,
			share_token varchar(64) NOT NULL,
			code_snippet longtext NOT NULL,
			error_hash varchar(32) NOT NULL,
			occurrence_count int(11) DEFAULT 1 NOT NULL,
			request_context longtext NOT NULL,
			env_context text NOT NULL,
			status varchar(20) DEFAULT 'open' NOT NULL,
			last_seen datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY error_type (error_type),
			KEY share_token (share_token),
			KEY error_hash (error_hash),
			KEY last_seen (last_seen),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// dbDelta is sometimes unreliable for adding columns. Ensure they exist.
		self::ensure_columns_exist();
		self::ensure_indexes_exist();

		update_option( 'bugsneak_db_version', BUGSNEAK_VERSION );
	}

	/**
	 * Manually ensures all required columns exist in the table.
	 */
	private static function ensure_columns_exist() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$columns = [
			'wp_version'       => 'varchar(20) NOT NULL',
			'active_theme'     => 'varchar(100) NOT NULL',
			'culprit'          => 'varchar(255) NOT NULL',
			'share_token'      => 'varchar(64) NOT NULL',
			'code_snippet'     => 'longtext NOT NULL',
			'error_hash'       => 'varchar(32) NOT NULL',
			'occurrence_count' => 'int(11) DEFAULT 1 NOT NULL',
			'request_context'  => 'longtext NOT NULL',
			'env_context'      => 'text NOT NULL',
			'status'           => 'varchar(20) DEFAULT \'open\' NOT NULL',
			'last_seen'        => 'datetime DEFAULT CURRENT_TIMESTAMP NOT NULL',
		];

		foreach ( $columns as $column => $definition ) {
			$check = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM %i LIKE %s", $table_name, $column ) );
			if ( empty( $check ) ) {
				$wpdb->query( $wpdb->prepare( "ALTER TABLE %i ADD %i %s", $table_name, $column, $definition ) );
			}
		}
	}

	/**
	 * Ensure all required indexes exist (safe for upgrades).
	 */
	private static function ensure_indexes_exist() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		$required_indexes = [ 'error_hash', 'last_seen', 'created_at', 'error_type', 'share_token' ];

		$existing = $wpdb->get_results( $wpdb->prepare( "SHOW INDEX FROM %i", $table_name ), ARRAY_A );
		$existing_keys = array_unique( array_column( $existing, 'Key_name' ) );

		foreach ( $required_indexes as $index_name ) {
			if ( ! in_array( $index_name, $existing_keys, true ) ) {
				$wpdb->query( $wpdb->prepare( "ALTER TABLE %i ADD INDEX %i (%i)", $table_name, $index_name, $index_name ) );
			}
		}
	}

	/**
	 * Fetch logs from the database.
	 *
	 * @param int $limit Max logs to fetch.
	 * @return array
	 */
	public static function get_logs( $limit = 100 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM %i ORDER BY last_seen DESC LIMIT %d", $table_name, (int) $limit ),
			ARRAY_A
		);
	}

	/**
	 * Fetch a single log by its share token.
	 *
	 * @param string $token Secure token.
	 * @return array|null
	 */
	public static function get_log_by_token( $token ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM %i WHERE share_token = %s", $table_name, sanitize_text_field( $token ) ),
			ARRAY_A
		);
	}

	/**
	 * Truncate all logs.
	 */
	public static function clear_logs() {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$wpdb->query( $wpdb->prepare( "TRUNCATE TABLE %i", $table_name ) );
	}
}
