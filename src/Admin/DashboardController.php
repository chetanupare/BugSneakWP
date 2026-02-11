<?php
/**
 * Admin Dashboard Controller for BugSneak.
 *
 * @package BugSneak\Admin
 */

namespace BugSneak\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard Controller for BugSneak.
 */
class DashboardController {

	/**
	 * Singleton instance of the controller.
	 *
	 * @var DashboardController|null
	 */
	private static $instance = null;

	/**
	 * Reference to the Engine.
	 *
	 * @var \BugSneak\Core\Engine
	 */
	private $engine;

	/**
	 * Get the singleton instance.
	 *
	 * @return DashboardController
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self( \BugSneak\Core\Engine::get_instance() );
		}
		return self::$instance;
	}

	/**
	 * DashboardController constructor.
	 *
	 * @param \BugSneak\Core\Engine $engine Core engine instance.
	 */
	private function __construct( $engine ) {
		$this->engine = $engine;
		add_action( 'admin_menu', array( $this, 'register_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ) );
	}

	/**
	 * Add custom body class for BugSneak pages.
	 *
	 * @param string $classes Existing classes.
	 * @return string
	 */
	public function add_admin_body_class( $classes ) {
		$screen = get_current_screen();
		if ( $screen && ( 'tools_page_bugsneak' === $screen->id || 'tools_page_bugsneak-settings' === $screen->id ) ) {
			// Add body class for scoping.
			return $classes . ' bugsneak-admin-page';
		}
		return $classes;
	}

	/**
	 * Register menu pages.
	 */
	public function register_pages() {
		add_management_page(
			'BugSneak',
			'BugSneak',
			'manage_options',
			'bugsneak',
			array( $this, 'render_dashboard' )
		);

		add_management_page(
			'BugSneak Settings',
			'', // Hidden from menu - accessed via dashboard link.
			'manage_options',
			'bugsneak-settings',
			array( $this, 'render_settings' )
		);
	}

	/**
	 * Enqueue assets based on current page.
	 *
	 * @param string $hook Admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		$is_dashboard = ( 'tools_page_bugsneak' === $hook );
		$is_settings  = ( 'tools_page_bugsneak-settings' === $hook );

		if ( ! $is_dashboard && ! $is_settings ) {
			return;
		}

		// Shared dependencies.
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_script( 'wp-element' );
		wp_enqueue_script( 'wp-api-fetch' );

		wp_enqueue_style( 'bugsneak-inter', BUGSNEAK_URL . 'assets/vendor/inter.css', array(), BUGSNEAK_VERSION );
		wp_enqueue_style( 'bugsneak-material-icons', BUGSNEAK_URL . 'assets/vendor/material-icons.css', array(), BUGSNEAK_VERSION );

		wp_enqueue_script( 'bugsneak-marked', BUGSNEAK_URL . 'assets/vendor/marked.min.js', array(), '12.0.0', false );
		wp_enqueue_script( 'bugsneak-tailwind', BUGSNEAK_URL . 'assets/vendor/tailwindcss.js', array(), BUGSNEAK_VERSION, false );
		wp_add_inline_script(
			'bugsneak-tailwind',
			"
			tailwind.config = {
				darkMode: 'class',
				theme: {
					extend: {
						colors: {
							'primary': '#6366f1', 'primary-light': '#818cf8', 'primary-dark': '#4f46e5',
							'secondary': '#22d3ee',
							'background-light': '#f8fafc', 'background-dark': '#0f172a',
							'surface-light': '#ffffff', 'surface-dark': '#1e293b',
							'danger': '#ef4444', 'warning': '#f59e0b', 'success': '#10b981',
							'text-main': '#f8fafc', 'text-muted': '#94a3b8',
						},
						fontFamily: {
							'display': ['Inter', 'sans-serif'],
							'mono': ['JetBrains Mono', 'monospace'],
						},
					},
				},
			}
		"
		);

		wp_enqueue_style( 'bugsneak-dashboard-css', BUGSNEAK_URL . 'assets/dashboard.css', array(), BUGSNEAK_VERSION );

		if ( $is_dashboard ) {
			wp_enqueue_script( 'bugsneak-dashboard-app', BUGSNEAK_URL . 'assets/dashboard.js', array( 'wp-element', 'wp-api-fetch' ), BUGSNEAK_VERSION . '.' . time(), true );
			// Localize dashboard strings.
			wp_localize_script(
				'bugsneak-dashboard-app',
				'bugsneakData',
				array(
					'root'        => esc_url_raw( rest_url( 'bugsneak/v1' ) ),
					'nonce'       => wp_create_nonce( 'wp_rest' ),
					'logs'        => array_map(
						function ( $log ) use ( $is_dashboard ) {
							// Spike Detection (Velocity Check).
							$duration        = strtotime( $log['last_seen'] ) - strtotime( $log['created_at'] );
							$velocity        = $duration > 0 ? ( $log['occurrence_count'] / $duration ) * 60 : ( $log['occurrence_count'] > 1 ? 999 : 0 );
							$log['is_spike'] = ( $log['occurrence_count'] > 10 && $velocity > 5 );

							// Build Context for Intelligence Engine.
							$context             = \BugSneak\Intelligence\ContextBuilder::build();
							$context['culprit']  = $log['culprit'];
							$context['is_spike'] = $log['is_spike'];

							$log['classification'] = \BugSneak\Intelligence\ErrorClassifier::classify( $log['error_message'], $context );

							return $log;
						},
						\BugSneak\Database\Schema::get_logs( 50 )
					),
					'settingsUrl' => admin_url( 'tools.php?page=bugsneak-settings' ),
					'env'         => array(
						'php_version'  => PHP_VERSION,
						'wp_version'   => $GLOBALS['wp_version'],
						'memory_limit' => ini_get( 'memory_limit' ),
						'server_os'    => php_uname( 's' ) . ' ' . php_uname( 'r' ),
						'theme'        => wp_get_theme()->get( 'Name' ),
					),
					'logo_light'  => BUGSNEAK_URL . 'logo-dark-new.png',
					'logo_dark'   => BUGSNEAK_URL . 'logo-dark-new.png',
					'logo_text'   => BUGSNEAK_URL . 'logo-text-new.svg',
					'ai_enabled'  => \BugSneak\Admin\Settings::get( 'ai_enabled', false ),
					'ai_provider' => \BugSneak\Admin\Settings::get( 'ai_provider', 'gemini' ),
				)
			);
		}

		if ( $is_settings ) {
			wp_enqueue_script( 'bugsneak-settings-app', BUGSNEAK_URL . 'assets/settings.js', array( 'wp-element', 'wp-api-fetch' ), BUGSNEAK_VERSION, true );
			// Localize settings strings.
			wp_localize_script(
				'bugsneak-settings-app',
				'bugsneakSettingsData',
				array(
					'root'         => esc_url_raw( rest_url( 'bugsneak/v1' ) ),
					'nonce'        => wp_create_nonce( 'wp_rest' ),
					'dashboardUrl' => admin_url( 'tools.php?page=bugsneak' ),
					'logo_light'   => BUGSNEAK_URL . 'logo-dark-new.png',
					'logo_dark'    => BUGSNEAK_URL . 'logo-dark-new.png',
					'logo_text'    => BUGSNEAK_URL . 'logo-text-new.svg',
				)
			);
		}
	}

	/**
	 * Render the Dashboard page.
	 */
	public function render_dashboard() {
		$this->render_full_page_shell( 'bugsneak-app' );
	}

	/**
	 * Render the Settings page.
	 */
	public function render_settings() {
		$this->render_full_page_shell( 'bugsneak-settings-app' );
	}

	/**
	 * Render the full-page WP admin shell.
	 *
	 * @param string $root_id The React root element ID.
	 */
	private function render_full_page_shell( $root_id ) {
		?>
		<style>
			/* Lock global viewport to prevent "drifting" into white space */
			html, body { height: 100vh !important; overflow: hidden !important; }
			#wpwrapper { height: 100vh !important; overflow: hidden !important; }
			
			/* Ensure the content area fills the remaining viewport */
			#wpcontent { 
				padding: 0 !important; 
				height: calc(100vh - 32px) !important; /* Default WP Admin Bar height */
				box-sizing: border-box; 
				overflow: hidden !important; /* Ensure no double scrollbars */
			}

			/* Handle responsive admin bar height (46px on mobile/small screens) */
			@media screen and (max-width: 782px) {
				html { margin-top: 46px !important; }
				#wpcontent { height: calc(100vh - 46px) !important; }
			}

			#wpbody { height: 100% !important; }
			#wpbody-content { 
				padding: 0 !important; 
				margin: 0 !important; 
				float: none !important; 
				width: 100% !important; 
				height: 100% !important; 
				display: flex; 
				flex-direction: column; 
				overflow: hidden !important; 
			}

			/* Surgical fix: Re-enable scrolling only for the WordPress admin sidebar */
			#adminmenuwrap { 
				height: calc(100vh - 32px) !important; 
				overflow-y: auto !important; 
				overflow-x: hidden !important; 
			}
			@media screen and (max-width: 782px) {
				#adminmenuwrap { height: calc(100vh - 46px) !important; }
			}

			#wpbody-content > *:not(#<?php echo esc_attr( $root_id ); ?>) { display: none !important; }
			#<?php echo esc_attr( $root_id ); ?> { 
				flex: 1 1 auto; 
				width: 100%; 
				margin: 0; 
				padding: 0; 
				min-height: 0; 
				display: flex; 
				flex-direction: column; 
				height: 100% !important; 
			}

			#wpfooter { display: none !important; }
			.notice, .updated, .error, .update-nag, .is-dismissible, .notice-dismiss { display: none !important; }
		</style>
		<div id="<?php echo esc_attr( $root_id ); ?>"></div>
		<?php
	}
}
