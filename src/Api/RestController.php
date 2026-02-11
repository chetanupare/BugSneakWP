<?php
/**
 * REST Controller for BugSneak.
 *
 * @package BugSneak\Api
 */

namespace BugSneak\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RestController
 */
class RestController {

	/**
	 * @var RestController|null
	 */
	private static $instance = null;

	/**
	 * @return RestController
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all REST API routes.
	 */
	public function register_routes() {
		$ns = 'bugsneak/v1';

		// Logs.
		register_rest_route(
			$ns,
			'/logs',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_logs' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			$ns,
			'/logs/(?P<id>\d+)/status',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_log_status' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			$ns,
			'/clear',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'clear_logs' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Settings.
		register_rest_route(
			$ns,
			'/settings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Purge.
		register_rest_route(
			$ns,
			'/purge',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'purge_logs' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// AI Analysis.
		register_rest_route(
			$ns,
			'/analyze/(?P<id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'analyze_log' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * @return bool
	 */
	public function check_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Fetch logs.
	 */
	public function get_logs() {
		$logs = \BugSneak\Database\Schema::get_logs( 100 );
		$logs = array_map(
			function ( $log ) {
				// Spike Detection (Velocity Check)
				$duration        = strtotime( $log['last_seen'] ) - strtotime( $log['created_at'] );
				$velocity        = $duration > 0 ? ( $log['occurrence_count'] / $duration ) * 60 : ( $log['occurrence_count'] > 1 ? 999 : 0 );
				$log['is_spike'] = ( $log['occurrence_count'] > 10 && $velocity > 5 );

				// Build Context for Intelligence Engine
				$context             = \BugSneak\Intelligence\ContextBuilder::build();
				$context['culprit']  = $log['culprit'];
				$context['is_spike'] = $log['is_spike'];
				$context['is_rest']  = true; // This is a REST request itself

				$log['classification'] = \BugSneak\Intelligence\ErrorClassifier::classify( $log['error_message'], $context );

				return $log;
			},
			$logs
		);

		return rest_ensure_response( $logs );
	}

	/**
	 * Clear all logs.
	 */
	public function clear_logs() {
		\BugSneak\Database\Schema::clear_logs();
		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Get all settings + DB stats.
	 */
	public function get_settings() {
		return rest_ensure_response(
			array(
				'settings' => \BugSneak\Admin\Settings::get_all(),
				'stats'    => \BugSneak\Admin\Settings::get_db_stats(),
				'defaults' => \BugSneak\Admin\Settings::DEFAULTS,
			)
		);
	}

	/**
	 * Save settings (partial merge).
	 *
	 * @param \WP_REST_Request $request Request object.
	 */
	public function save_settings( $request ) {
		$body = $request->get_json_params();
		if ( empty( $body ) ) {
			return new \WP_Error( 'invalid_body', 'No settings provided.', array( 'status' => 400 ) );
		}

		$saved = \BugSneak\Admin\Settings::save( $body );

		return rest_ensure_response(
			array(
				'success'  => $saved,
				'settings' => \BugSneak\Admin\Settings::get_all(),
			)
		);
	}

	/**
	 * Purge all logs.
	 */
	public function purge_logs() {
		$result = \BugSneak\Admin\Settings::purge_all();
		return rest_ensure_response(
			array(
				'success' => $result,
				'stats'   => \BugSneak\Admin\Settings::get_db_stats(),
			)
		);
	}

	/**
	 * Analyze a log using AI.
	 *
	 * @param \WP_REST_Request $request Request object.
	 */
	public function analyze_log( $request ) {
		global $wpdb;
		$id    = (int) $request['id'];
		$table = $wpdb->prefix . 'bugsneak_logs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for custom log retrieval
		$log = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $table, $id ), ARRAY_A );

		if ( ! $log ) {
			return new \WP_Error( 'not_found', 'Log entry not found.', array( 'status' => 404 ) );
		}

		$insight = \BugSneak\Core\AIProcessor::analyze( $log );

		if ( is_wp_error( $insight ) ) {
			return $insight;
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'insight' => $insight,
			)
		);
	}
	/**
	 * Update log status (ignore/resolve).
	 *
	 * @param \WP_REST_Request $request Request object.
	 */
	public function update_log_status( $request ) {
		global $wpdb;
		$id     = (int) $request['id'];
		$status = sanitize_text_field( $request->get_param( 'status' ) );
		$table  = $wpdb->prefix . 'bugsneak_logs';

		if ( ! in_array( $status, array( 'open', 'resolved', 'ignored' ), true ) ) {
			return new \WP_Error( 'invalid_status', 'Invalid status provided.', array( 'status' => 400 ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for custom log status update
		$updated = $wpdb->update(
			$table,
			array( 'status' => $status ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		return rest_ensure_response(
			array(
				'success' => (bool) $updated,
				'status'  => $status,
			)
		);
	}
}
