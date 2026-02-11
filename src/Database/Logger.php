<?php
/**
 * Logger for BugSneak.
 *
 * @package BugSneak\Database
 */

namespace BugSneak\Database;

use BugSneak\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Logger
 *
 * Handles inserting and grouping log entries with full safety guards.
 */
class Logger {

	/**
	 * Insert a log entry with full technical context.
	 *
	 * Returns a structured result:
	 *   'grouped'  — Existing row incremented.
	 *   'inserted' — New row created.
	 *   'skipped'  — Capacity limit reached; insert skipped.
	 *   'error'    — DB failure caught; site unaffected.
	 *
	 * @param array $data Error and context data.
	 * @return string One of: grouped, inserted, skipped, error.
	 */
	public static function insert( $data ) {
		try {
			global $wpdb;

			$table_name = $wpdb->prefix . 'bugsneak_logs';

			// ── Capacity guard ──────────────────────────────────────────
			$max_rows = Settings::get( 'max_rows', 10000 );
			if ( $max_rows > 0 ) {
				$current_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM %i", $table_name ) );
				if ( $current_count >= $max_rows ) {
					return 'skipped';
				}
			}

			// ── Fingerprint for grouping ────────────────────────────────
			$hash = md5( $data['message'] . '|' . $data['file'] . '|' . $data['line'] );

			// ── Grouping: increment existing ────────────────────────────
			$existing = $wpdb->get_row(
				$wpdb->prepare( "SELECT id, occurrence_count FROM %i WHERE error_hash = %s", $table_name, $hash )
			);

			if ( $existing ) {
				$wpdb->update(
					$table_name,
					[
						'occurrence_count' => (int) $existing->occurrence_count + 1,
						'last_seen'        => current_time( 'mysql' ),
					],
					[ 'id' => $existing->id ],
					[ '%d', '%s' ],
					[ '%d' ]
				);
				return 'grouped';
			}

			// ── New insert ──────────────────────────────────────────────
			$result = $wpdb->insert(
				$table_name,
				[
					'error_type'       => sanitize_text_field( $data['type'] ),
					'error_message'    => sanitize_textarea_field( $data['message'] ),
					'file_path'        => sanitize_text_field( $data['file'] ),
					'line_number'      => (int) $data['line'],
					'stack_trace'      => wp_json_encode( $data['trace'] ),
					'wp_version'       => sanitize_text_field( $data['wp_version'] ),
					'active_theme'     => sanitize_text_field( $data['active_theme'] ),
					'culprit'          => sanitize_text_field( $data['culprit'] ),
					'share_token'      => wp_generate_password( 32, false ),
					'code_snippet'     => wp_json_encode( $data['code_snippet'] ),
					'error_hash'       => $hash,
					'occurrence_count' => 1,
					'request_context'  => wp_json_encode( $data['request_context'] ),
					'env_context'      => wp_json_encode( $data['env_context'] ),
					'last_seen'        => current_time( 'mysql' ),
					'created_at'       => current_time( 'mysql' ),
				],
				[
					'%s', '%s', '%s', '%d', '%s', // type, message, file, line, trace
					'%s', '%s', '%s', '%s', '%s', // wp, theme, culprit, token, snippet
					'%s', '%d', '%s', '%s', '%s', '%s' // hash, count, request, env, last, created
				]
			);

			return $result ? 'inserted' : 'error';

		} catch ( \Throwable $e ) {
			// Graceful failure — never crash the site because of logging.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'BugSneak Logger failed: ' . $e->getMessage() );
			}
			return 'error';
		}
	}
}
