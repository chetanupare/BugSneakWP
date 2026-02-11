<?php
/**
 * Internationalization strings for BugSneak Admin.
 *
 * @package BugSneak\Admin
 */

namespace BugSneak\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class I18n
 *
 * Provides localized strings for the BugSneak admin interface.
 */
class I18n {

	/**
	 * Get i18n strings for the Dashboard.
	 *
	 * @return array
	 */
	public static function get_dashboard_strings() {
		return array(
			'search_placeholder' => __( 'Search errors...', 'bugsneak' ),
			'loading'            => __( 'Loading...', 'bugsneak' ),
			'fatal'              => __( 'Fatal Errors', 'bugsneak' ),
			'warning'            => __( 'Warnings', 'bugsneak' ),
			'all'                => __( 'All', 'bugsneak' ),
			'open'               => __( 'Open', 'bugsneak' ),
			'resolved'           => __( 'Resolved', 'bugsneak' ),
			'ignored'            => __( 'Ignored', 'bugsneak' ),
			'update_failed'      => __( 'Status update failed', 'bugsneak' ),
			'fetch_failed'       => __( 'Failed to fetch logs', 'bugsneak' ),
			'env'                => __( 'Environment', 'bugsneak' ),
			'stack_trace'        => __( 'Stack Trace', 'bugsneak' ),
			'request'            => __( 'Request Details', 'bugsneak' ),
			'analyze'            => __( 'Analyze with AI', 'bugsneak' ),
			'culprit'            => __( 'Culprit detected', 'bugsneak' ),
			'light_mode'         => __( 'Light Mode', 'bugsneak' ),
			'dark_mode'          => __( 'Dark Mode', 'bugsneak' ),
			'settings'           => __( 'Settings', 'bugsneak' ),
			'dashboard'          => __( 'Dashboard', 'bugsneak' ),
			'docs'               => __( 'Docs', 'bugsneak' ),
			'help'               => __( 'Help', 'bugsneak' ),
			'no_errors'          => __( 'No errors found', 'bugsneak' ),
			'occurrence_new'     => __( 'new', 'bugsneak' ),
			'select_error'       => __( 'Select an error to begin diagnostic', 'bugsneak' ),
			'ignore'             => __( 'Ignore', 'bugsneak' ),
			'resolve'            => __( 'Resolve', 'bugsneak' ),
			'context'            => __( 'Context', 'bugsneak' ),
			'no_data'            => __( 'No data.', 'bugsneak' ),
			'telemetry_notice'   => __( 'Telemetry: 100% Local · No External Calls', 'bugsneak' ),
			'copy_bundle'        => __( 'Copy Bundle', 'bugsneak' ),
			'copied'             => __( 'Copied!', 'bugsneak' ),
			'confidence_high'    => __( 'High Confidence', 'bugsneak' ),
			'confidence_medium'  => __( 'Medium Confidence', 'bugsneak' ),
			'confidence_low'     => __( 'Low Confidence', 'bugsneak' ),
			'spike_detected'     => __( 'Abnormal Spike Detected', 'bugsneak' ),
			'spike_desc'         => __( 'Error rate allows this to be classified as a spike.', 'bugsneak' ),
			'unknown_source'     => __( 'Unknown Source', 'bugsneak' ),
			'system'             => __( 'System', 'bugsneak' ),
			'unknown_source_msg' => __( 'Error originated from an unknown source.', 'bugsneak' ),
			'ai_insight'         => __( 'BugSneak AI Insight', 'bugsneak' ),
			'ai_insight_labeled' => __( 'BugSneak AI Insight (%s)', 'bugsneak' ),
			'analyzing'          => __( 'Analyzing...', 'bugsneak' ),
			'deep_dive'          => __( 'Deep Dive Analysis', 'bugsneak' ),
			'generating'         => __( 'Generating insight...', 'bugsneak' ),
			'ai_prompt'          => __( 'Run a deep dive analysis to get code snippets, solutions, and architectural advice from the AI engine.', 'bugsneak' ),
			'error_here'         => __( 'error here', 'bugsneak' ),
			'no_code'            => __( 'No code context available', 'bugsneak' ),
		);
	}

	/**
	 * Get i18n strings for the Settings page.
	 *
	 * @return array
	 */
	public static function get_settings_strings() {
		return array(
			'settings'           => __( 'Settings', 'bugsneak' ),
			'error_levels'       => __( 'Error Levels to Capture', 'bugsneak' ),
			'grouping'           => __( 'Error Grouping', 'bugsneak' ),
			'database'           => __( 'Database & Retention', 'bugsneak' ),
			'culprit'            => __( 'Culprit Detection', 'bugsneak' ),
			'code'               => __( 'Code Snippet View', 'bugsneak' ),
			'context'            => __( 'Context Capture', 'bugsneak' ),
			'performance'        => __( 'Performance & Safety', 'bugsneak' ),
			'notifications'      => __( 'Notifications', 'bugsneak' ),
			'ui'                 => __( 'UI Preferences', 'bugsneak' ),
			'developer'          => __( 'Developer Mode', 'bugsneak' ),
			'ai'                 => __( 'AI Integration', 'bugsneak' ),
			'save'               => __( 'Save Settings', 'bugsneak' ),
			'saving'             => __( 'Saving...', 'bugsneak' ),
			'saved'              => __( 'Settings saved!', 'bugsneak' ),
			'save_failed'        => __( 'Failed to save settings', 'bugsneak' ),
			'purge'              => __( 'Purge All', 'bugsneak' ),
			'purge_confirm'      => __( 'This will permanently delete ALL error logs. Continue?', 'bugsneak' ),
			'loading'            => __( 'Loading settings...', 'bugsneak' ),
			'fatal_errors'       => __( 'Fatal Errors', 'bugsneak' ),
			'parse_errors'       => __( 'Parse Errors', 'bugsneak' ),
			'exceptions'         => __( 'Uncaught Exceptions', 'bugsneak' ),
			'warnings'           => __( 'Warnings', 'bugsneak' ),
			'notices'            => __( 'Notices', 'bugsneak' ),
			'deprecated'         => __( 'Deprecated', 'bugsneak' ),
			'strict'             => __( 'Strict Standards', 'bugsneak' ),
			'capture_mode'       => __( 'Capture Mode', 'bugsneak' ),
			'always_enabled'     => __( 'Always enabled', 'bugsneak' ),
			'debug_only'         => __( 'WP_DEBUG mode only', 'bugsneak' ),
			'production'         => __( 'Production (always)', 'bugsneak' ),
			'enable_grouping'    => __( 'Enable intelligent grouping', 'bugsneak' ),
			'max_occurrences'    => __( 'Max occurrence counter', 'bugsneak' ),
			'reset_grouping'     => __( 'Reset grouping after (minutes)', 'bugsneak' ),
			'merge_stack'        => __( 'Merge identical stack traces only', 'bugsneak' ),
			'retention'          => __( 'Auto-delete logs older than', 'bugsneak' ),
			'max_rows'           => __( 'Maximum stored errors', 'bugsneak' ),
			'frequency'          => __( 'Cleanup cron frequency', 'bugsneak' ),
			'strategy'           => __( 'Detection strategy', 'bugsneak' ),
			'ignore_core'        => __( 'Ignore WordPress core frames', 'bugsneak' ),
			'ignore_mu'          => __( 'Ignore mu-plugins', 'bugsneak' ),
			'blacklist'          => __( 'Blacklisted plugins (comma-separated)', 'bugsneak' ),
			'lines_before'       => __( 'Lines before error', 'bugsneak' ),
			'lines_after'        => __( 'Lines after error', 'bugsneak' ),
			'syntax'             => __( 'Enable syntax highlighting', 'bugsneak' ),
			'dark_code'          => __( 'Dark theme for code', 'bugsneak' ),
			'max_file_size'      => __( 'Max file size to read (KB)', 'bugsneak' ),
			'memory_safety'      => __( 'Prevents memory issues', 'bugsneak' ),
			'disable_frontend'   => __( 'Disable frontend logging', 'bugsneak' ),
			'disable_admin'      => __( 'Disable admin logging', 'bugsneak' ),
			'admin_only'         => __( 'Enable only for administrators', 'bugsneak' ),
			'safe_mode'          => __( 'Safe mode (minimal context)', 'bugsneak' ),
			'log_once'           => __( 'Log only once per request', 'bugsneak' ),
			'max_per_request'    => __( 'Max errors per request', 'bugsneak' ),
			'prevents_loops'     => __( 'Prevents loops', 'bugsneak' ),
			'dev_mode_hint'      => __( 'When enabled, the dashboard will show:', 'bugsneak' ),
			'back_to_dashboard'  => __( 'Back to Dashboard', 'bugsneak' ),
			'system_health'      => __( 'System Health', 'bugsneak' ),
			'all_systems_go'     => __( 'All Systems Go', 'bugsneak' ),
			'protected'          => __( 'Protected', 'bugsneak' ),
			'optimal'            => __( 'Optimal', 'bugsneak' ),
			'near_limit'         => __( 'Near Limit', 'bugsneak' ),
			'database_usage'     => __( 'Database Usage', 'bugsneak' ),
			'logs'               => __( 'logs', 'bugsneak' ),
			'save_changes'       => __( 'Save Changes', 'bugsneak' ),
			'saved_label'        => __( 'Saved', 'bugsneak' ),
			'v2_hint'            => __( 'Email, Slack, Webhooks, and Daily Digests are coming in v2.0.', 'bugsneak' ),
			'modern_dark_mode'   => __( 'Modern Dark Mode is active.', 'bugsneak' ),
			'get_api_key'        => __( 'Get a free API Key at %s.', 'bugsneak' ),
			'requires_api_key'   => __( 'Requires an OpenAI API Key. Get one at %s.', 'bugsneak' ),
			'telemetry_footer'   => __( 'BugSneak · 100% Local · Zero External Calls', 'bugsneak' ),
			'days'               => __( 'days', 'bugsneak' ),
			'minutes'            => __( 'minutes', 'bugsneak' ),
			'never'              => __( 'never', 'bugsneak' ),
			'unlimited'          => __( 'unlimited', 'bugsneak' ),
			'logs_human'         => __( '%s logs · %s', 'bugsneak' ),
			'status'             => __( 'Status', 'bugsneak' ),
			'logging'            => __( 'Logging', 'bugsneak' ),
			'daily'              => __( 'Daily', 'bugsneak' ),
			'weekly'             => __( 'Weekly', 'bugsneak' ),
			'detect_first'       => __( 'First plugin in stack trace', 'bugsneak' ),
			'detect_deepest'     => __( 'Deepest plugin in stack trace', 'bugsneak' ),
			'capture_user'       => __( 'Capture current user data', 'bugsneak' ),
			'capture_cookies'    => __( 'Capture cookies', 'bugsneak' ),
			'capture_env'        => __( 'Capture environment variables', 'bugsneak' ),
			'capture_memory'     => __( 'Capture memory usage', 'bugsneak' ),
			'capture_filter'     => __( 'Capture current_filter()', 'bugsneak' ),
			'email_notification' => __( 'Email on fatal error', 'bugsneak' ),
			'slack_notification' => __( 'Slack webhook', 'bugsneak' ),
			'daily_digest'       => __( 'Daily error summary digest', 'bugsneak' ),
			'webhook_trigger'    => __( 'Real-time webhook trigger', 'bugsneak' ),
			'compact_view'       => __( 'Compact view', 'bugsneak' ),
			'expand_traces'      => __( 'Expanded stack traces by default', 'bugsneak' ),
			'show_sidebar'       => __( 'Show context sidebar', 'bugsneak' ),
			'enable_dev_mode'    => __( 'Enable Developer Mode', 'bugsneak' ),
			'raw_stack'          => __( 'Raw stack trace JSON', 'bugsneak' ),
			'request_id'         => __( 'Request ID', 'bugsneak' ),
			'error_hash'         => __( 'Error hash', 'bugsneak' ),
			'grouping_key'       => __( 'Internal grouping key', 'bugsneak' ),
			'query_time'         => __( 'DB query time', 'bugsneak' ),
			'enable_ai'          => __( 'Enable AI-Powered Insights', 'bugsneak' ),
			'ai_provider_label'  => __( 'AI Provider', 'bugsneak' ),
			'gemini_label'       => __( 'Google Gemini (Free Tier available)', 'bugsneak' ),
			'openai_label'       => __( 'OpenAI ChatGPT', 'bugsneak' ),
			'gemini_key'         => __( 'Gemini API Key', 'bugsneak' ),
			'openai_key'         => __( 'OpenAI API Key', 'bugsneak' ),
			'model_label'        => __( 'Model', 'bugsneak' ),
			'system_status'      => __( 'System Status', 'bugsneak' ),
		);
	}
}
