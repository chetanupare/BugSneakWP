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
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register all REST API routes.
	 */
	public function register_routes() {
		$ns = 'bugsneak/v1';

		// Logs.
		register_rest_route( $ns, '/logs', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_logs' ],
			'permission_callback' => [ $this, 'check_permission' ],
		]);

		register_rest_route( $ns, '/logs/(?P<id>\d+)/status', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'update_log_status' ],
			'permission_callback' => [ $this, 'check_permission' ],
		]);

		register_rest_route( $ns, '/clear', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'clear_logs' ],
			'permission_callback' => [ $this, 'check_permission' ],
		]);

		// Settings.
		register_rest_route( $ns, '/settings', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_settings' ],
				'permission_callback' => [ $this, 'check_permission' ],
			],
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'save_settings' ],
				'permission_callback' => [ $this, 'check_permission' ],
			],
		]);

		// Purge.
		register_rest_route( $ns, '/purge', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'purge_logs' ],
			'permission_callback' => [ $this, 'check_permission' ],
		]);

		// AI Analysis.
		register_rest_route( $ns, '/analyze/(?P<id>\d+)', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'analyze_log' ],
			'permission_callback' => [ $this, 'check_permission' ],
		]);
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
		$logs = array_map( function( $log ) {
			$log['classification'] = \BugSneak\Intelligence\ErrorPatterns::analyze( $log['error_message'] );
			return $log;
		}, $logs );

		return rest_ensure_response( $logs );
	}

	/**
	 * Clear all logs.
	 */
	public function clear_logs() {
		\BugSneak\Database\Schema::clear_logs();
		return rest_ensure_response( [ 'success' => true ] );
	}

	/**
	 * Get all settings + DB stats.
	 */
	public function get_settings() {
		return rest_ensure_response( [
			'settings' => \BugSneak\Admin\Settings::get_all(),
			'stats'    => \BugSneak\Admin\Settings::get_db_stats(),
			'defaults' => \BugSneak\Admin\Settings::DEFAULTS,
		] );
	}

	/**
	 * Save settings (partial merge).
	 *
	 * @param \WP_REST_Request $request Request object.
	 */
	public function save_settings( $request ) {
		$body = $request->get_json_params();
		if ( empty( $body ) ) {
			return new \WP_Error( 'invalid_body', 'No settings provided.', [ 'status' => 400 ] );
		}

		$saved = \BugSneak\Admin\Settings::save( $body );

		return rest_ensure_response( [
			'success'  => $saved,
			'settings' => \BugSneak\Admin\Settings::get_all(),
		] );
	}

	/**
	 * Purge all logs.
	 */
	public function purge_logs() {
		$result = \BugSneak\Admin\Settings::purge_all();
		return rest_ensure_response( [
			'success' => $result,
			'stats'   => \BugSneak\Admin\Settings::get_db_stats(),
		] );
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

		$log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM %i WHERE id = %d", $table, $id ), ARRAY_A );

		if ( ! $log ) {
			return new \WP_Error( 'not_found', 'Log entry not found.', [ 'status' => 404 ] );
		}

		$insight = \BugSneak\Core\AIProcessor::analyze( $log );

		if ( is_wp_error( $insight ) ) {
			return $insight;
		}

		return rest_ensure_response( [
			'success' => true,
			'insight' => $insight,
		] );
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

		if ( ! in_array( $status, [ 'open', 'resolved', 'ignored' ], true ) ) {
			return new \WP_Error( 'invalid_status', 'Invalid status provided.', [ 'status' => 400 ] );
		}

		$updated = $wpdb->update(
			$table,
			[ 'status' => $status ],
			[ 'id' => $id ],
			[ '%s' ],
			[ '%d' ]
		);

		return rest_ensure_response( [
			'success' => (bool) $updated,
			'status'  => $status,
		] );
	}
}
