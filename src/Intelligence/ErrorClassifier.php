<?php
namespace BugSneak\Intelligence;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ErrorClassifier {

	/**
	 * Pattern Rules
	 * Supports:
	 * - regex
	 * - literal match
	 * - weight (confidence)
	 * - severity
	 * - tags
	 */
	protected static array $rules = array(

		array(
			'type'       => 'regex',
			'pattern'    => '/Allowed memory size exhausted/i',
			'category'   => __( 'Memory Exhaustion', 'bugsneak' ),
			'severity'   => 'critical',
			'weight'     => 90,
			'tags'       => array( 'memory', 'php', 'fatal' ),
			'suggestion' => __( 'Increase WP_MEMORY_LIMIT in wp-config.php or inspect memory-heavy plugins.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Maximum execution time of \d+ seconds exceeded/i',
			'category'   => __( 'Execution Timeout', 'bugsneak' ),
			'severity'   => 'critical',
			'weight'     => 85,
			'tags'       => array( 'timeout', 'performance' ),
			'suggestion' => __( 'Increase max_execution_time in php.ini or optimize long-running processes.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Call to undefined function/i',
			'category'   => __( 'Missing Function', 'bugsneak' ),
			'severity'   => 'high',
			'weight'     => 80,
			'tags'       => array( 'dependency', 'plugin', 'fatal' ),
			'suggestion' => __( 'Ensure required plugin or dependency is active and loaded properly.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Call to undefined method/i',
			'category'   => __( 'Missing Method', 'bugsneak' ),
			'severity'   => 'high',
			'weight'     => 80,
			'tags'       => array( 'dependency', 'code-error', 'fatal' ),
			'suggestion' => __( 'Plugin or class version mismatch. Verify compatibility and updates.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Class not found/i',
			'category'   => __( 'Missing Class', 'bugsneak' ),
			'severity'   => 'high',
			'weight'     => 80,
			'tags'       => array( 'autoloader', 'dependency', 'fatal' ),
			'suggestion' => __( 'Autoloader issue or missing dependency. Check plugin installation integrity.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Cannot redeclare (class|function)/i',
			'category'   => __( 'Redeclaration Conflict', 'bugsneak' ),
			'severity'   => 'high',
			'weight'     => 75,
			'tags'       => array( 'conflict', 'plugin' ),
			'suggestion' => __( 'Possible plugin conflict or duplicate file inclusion.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/WordPress database error/i',
			'category'   => __( 'Database Error', 'bugsneak' ),
			'severity'   => 'critical',
			'weight'     => 95,
			'tags'       => array( 'database', 'sql' ),
			'suggestion' => __( 'Verify database credentials and check for corrupted tables.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Base table or view not found/i',
			'category'   => __( 'Missing Database Table', 'bugsneak' ),
			'severity'   => 'critical',
			'weight'     => 90,
			'tags'       => array( 'database', 'schema' ),
			'suggestion' => __( 'Plugin activation may have failed. Try reactivating plugin.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/MySQL server has gone away/i',
			'category'   => __( 'Database Connection Lost', 'bugsneak' ),
			'severity'   => 'critical',
			'weight'     => 95,
			'tags'       => array( 'database', 'timeout' ),
			'suggestion' => __( 'Database server timed out or packet size is too large (max_allowed_packet).', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Parse error/i',
			'category'   => __( 'Syntax Error', 'bugsneak' ),
			'severity'   => 'critical',
			'weight'     => 100,
			'tags'       => array( 'php', 'syntax' ),
			'suggestion' => __( 'Check PHP syntax in the referenced file.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/TypeError/i',
			'category'   => __( 'Type Mismatch', 'bugsneak' ),
			'severity'   => 'high',
			'weight'     => 70,
			'tags'       => array( 'php', 'types' ),
			'suggestion' => __( 'Invalid parameter type passed to function. Review recent code changes.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Too few arguments to function/i',
			'category'   => __( 'Argument Mismatch', 'bugsneak' ),
			'severity'   => 'high',
			'weight'     => 70,
			'tags'       => array( 'php', 'signature' ),
			'suggestion' => __( 'Function signature mismatch. Check plugin version compatibility.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Undefined (array key|index|offset)/i',
			'category'   => __( 'Undefined Array Key', 'bugsneak' ),
			'severity'   => 'low',
			'weight'     => 50,
			'tags'       => array( 'notice', 'php' ),
			'suggestion' => __( 'Check if array key/index exists before accessing it.', 'bugsneak' ),
		),

		array(
			'type'       => 'literal',
			'pattern'    => 'Trying to get property of non-object',
			'category'   => __( 'Invalid Object Access', 'bugsneak' ),
			'severity'   => 'medium',
			'weight'     => 60,
			'tags'       => array( 'php', 'logic' ),
			'suggestion' => __( 'Object expected but null returned. Check conditional logic.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Call to a member function .* on null/i',
			'category'   => __( 'Null Reference', 'bugsneak' ),
			'severity'   => 'high',
			'weight'     => 75,
			'tags'       => array( 'php', 'null' ),
			'suggestion' => __( 'Object may not be initialized properly.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Cannot modify header information/i',
			'category'   => __( 'Header Output Issue', 'bugsneak' ),
			'severity'   => 'medium',
			'weight'     => 65,
			'tags'       => array( 'php', 'headers' ),
			'suggestion' => __( 'Output was sent before headers. Check for whitespace or echo statements.', 'bugsneak' ),
		),

		array(
			'type'       => 'literal',
			'pattern'    => 'rest_no_route',
			'category'   => __( 'Invalid REST Route', 'bugsneak' ),
			'severity'   => 'medium',
			'weight'     => 55,
			'tags'       => array( 'api', 'rest' ),
			'suggestion' => __( 'Ensure REST route is registered correctly.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/session_start\(\)/i',
			'category'   => __( 'Session Conflict', 'bugsneak' ),
			'severity'   => 'medium',
			'weight'     => 50,
			'tags'       => array( 'php', 'session' ),
			'suggestion' => __( 'Session may already be started by another plugin.', 'bugsneak' ),
		),

		array(
			'type'       => 'literal',
			'pattern'    => 'GD library',
			'category'   => __( 'Image Processing Error', 'bugsneak' ),
			'severity'   => 'medium',
			'weight'     => 50,
			'tags'       => array( 'images', 'extension' ),
			'suggestion' => __( 'Ensure GD or Imagick extension is enabled.', 'bugsneak' ),
		),

		array(
			'type'       => 'literal',
			'pattern'    => 'Deprecated',
			'category'   => __( 'Deprecated Function Usage', 'bugsneak' ),
			'severity'   => 'low',
			'weight'     => 30,
			'tags'       => array( 'php', 'deprecated' ),
			'suggestion' => __( 'Plugin may need update for current PHP version.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/cURL error/i',
			'category'   => __( 'HTTP Connection Failure', 'bugsneak' ),
			'severity'   => 'high',
			'weight'     => 70,
			'tags'       => array( 'http', 'network' ),
			'suggestion' => __( 'External API request failed. Check server connectivity or firewall settings.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Call to (private|protected) method/i',
			'category'   => __( 'Visibility Violation', 'bugsneak' ),
			'severity'   => 'high',
			'weight'     => 70,
			'tags'       => array( 'php', 'oop' ),
			'suggestion' => __( 'Attempting to access a protected/private class method. Check plugin compatibility.', 'bugsneak' ),
		),

		array(
			'type'       => 'literal',
			'pattern'    => 'must be compatible with',
			'category'   => __( 'Declaration Incompatibility', 'bugsneak' ),
			'severity'   => 'medium',
			'weight'     => 60,
			'tags'       => array( 'php', 'oop', 'strict' ),
			'suggestion' => __( 'Child class method signature does not match parent. Update plugin/theme.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Division by zero/i',
			'category'   => __( 'Math Error', 'bugsneak' ),
			'severity'   => 'medium',
			'weight'     => 50,
			'tags'       => array( 'php', 'math' ),
			'suggestion' => __( 'Code attempted to divide by zero. Check logical conditions.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/json_decode/i',
			'category'   => __( 'JSON Parsing Error', 'bugsneak' ),
			'severity'   => 'medium',
			'weight'     => 50,
			'tags'       => array( 'php', 'json', 'data' ),
			'suggestion' => __( 'Failed to decode JSON data. Response might be malformed or empty.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Input variables exceeded/i',
			'category'   => __( 'Max Input Vars Exceeded', 'bugsneak' ),
			'severity'   => 'high',
			'weight'     => 65,
			'tags'       => array( 'php', 'server', 'limit' ),
			'suggestion' => __( 'Menu/Form too large. Increase max_input_vars in php.ini.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/failed to open stream: Permission denied/i',
			'category'   => __( 'File Write Permission', 'bugsneak' ),
			'severity'   => 'high',
			'weight'     => 85,
			'tags'       => array( 'filesystem', 'permissions' ),
			'suggestion' => __( 'Server cannot write to file/folder. Check chmod/chown settings.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/failed to open stream/i',
			'category'   => __( 'Missing File', 'bugsneak' ),
			'severity'   => 'high',
			'weight'     => 80,
			'tags'       => array( 'filesystem', 'missing' ),
			'suggestion' => __( 'Verify file path and ensure plugin/theme files exist.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/move_uploaded_file/i',
			'category'   => __( 'Upload Failure', 'bugsneak' ),
			'severity'   => 'high',
			'weight'     => 80,
			'tags'       => array( 'filesystem', 'upload' ),
			'suggestion' => __( 'Failed to move uploaded file. Check upload_tmp_dir or permissions.', 'bugsneak' ),
		),
		array(
			'type'       => 'regex',
			'pattern'    => '/There has been a critical error on this website/i',
			'category'   => __( 'WordPress Critical Error', 'bugsneak' ),
			'severity'   => 'critical',
			'weight'     => 100,
			'tags'       => array( 'wp-core', 'fatal' ),
			'suggestion' => __( 'Enable WP_DEBUG to view detailed error logs.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/nonce verification failed/i',
			'category'   => __( 'Nonce Verification Failure', 'bugsneak' ),
			'severity'   => 'medium',
			'weight'     => 70,
			'tags'       => array( 'security', 'nonce' ),
			'suggestion' => __( 'Nonce may be expired or invalid. Refresh the page and retry.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Error establishing a database connection/i',
			'category'   => __( 'Database Connection Failure', 'bugsneak' ),
			'severity'   => 'critical',
			'weight'     => 100,
			'tags'       => array( 'database', 'wp-config' ),
			'suggestion' => __( 'Verify DB credentials in wp-config.php and check MySQL service.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Plugin could not be activated because it triggered a fatal error/i',
			'category'   => __( 'Plugin Activation Failure', 'bugsneak' ),
			'severity'   => 'high',
			'weight'     => 90,
			'tags'       => array( 'plugin', 'activation' ),
			'suggestion' => __( 'Review stack trace to identify incompatible code or PHP version mismatch.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Required parameter .* follows optional parameter/i',
			'category'   => __( 'PHP 8 Compatibility Issue', 'bugsneak' ),
			'severity'   => 'high',
			'weight'     => 85,
			'tags'       => array( 'php8', 'compatibility' ),
			'suggestion' => __( 'Update plugin or fix function signature for PHP 8 compatibility.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/rest_forbidden/i',
			'category'   => __( 'REST Permission Denied', 'bugsneak' ),
			'severity'   => 'medium',
			'weight'     => 70,
			'tags'       => array( 'rest', 'permission' ),
			'suggestion' => __( 'Check REST permission_callback and current_user_can logic.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Block validation failed/i',
			'category'   => __( 'Block Validation Failure', 'bugsneak' ),
			'severity'   => 'medium',
			'weight'     => 75,
			'tags'       => array( 'gutenberg', 'block' ),
			'suggestion' => __( 'Block markup may differ from saved content. Check custom block rendering.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/wp_cron/i',
			'category'   => __( 'WP Cron Failure', 'bugsneak' ),
			'severity'   => 'medium',
			'weight'     => 60,
			'tags'       => array( 'cron', 'scheduler' ),
			'suggestion' => __( 'Ensure WP-Cron is enabled or configure a real server cron job.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Table .* doesn\'t exist/i',
			'category'   => __( 'Missing Database Table', 'bugsneak' ),
			'severity'   => 'critical',
			'weight'     => 95,
			'tags'       => array( 'database', 'migration' ),
			'suggestion' => __( 'Plugin may not have created required tables. Try reactivating it.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/is_multisite/i',
			'category'   => __( 'Multisite Misconfiguration', 'bugsneak' ),
			'severity'   => 'medium',
			'weight'     => 65,
			'tags'       => array( 'multisite', 'network' ),
			'suggestion' => __( 'Verify multisite constants in wp-config.php.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/ImagickException/i',
			'category'   => __( 'Imagick Processing Failure', 'bugsneak' ),
			'severity'   => 'medium',
			'weight'     => 75,
			'tags'       => array( 'imagick', 'media' ),
			'suggestion' => __( 'Ensure Imagick extension is installed and enabled.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Cannot modify header information - headers already sent/i',
			'category'   => __( 'Headers Already Sent', 'bugsneak' ),
			'severity'   => 'medium',
			'weight'     => 80,
			'tags'       => array( 'output', 'php' ),
			'suggestion' => __( 'Remove whitespace before <?php or after closing ?> tags.', 'bugsneak' ),
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/SSL certificate problem/i',
			'category'   => __( 'SSL Certificate Error', 'bugsneak' ),
			'severity'   => 'high',
			'weight'     => 85,
			'tags'       => array( 'ssl', 'api' ),
			'suggestion' => __( 'Verify SSL certificate chain and hosting configuration.', 'bugsneak' ),
		),
	);

	/**
	 * Classify error message with context-aware scoring.
	 *
	 * @param string $message The error message to classify.
	 * @param array  $context Environmental context (php_version, wp_version, is_admin, etc.)
	 * @return array Classification result.
	 */
	public static function classify( string $message, array $context = array() ): array {
		$matches = array();

		// Allow advanced users to override/extend rules
		$rules = apply_filters( 'bugsneak_classification_rules', self::$rules );

		foreach ( $rules as $rule ) {

			$matched = false;

			if ( $rule['type'] === 'regex' ) {
				$matched = preg_match( $rule['pattern'], $message );
			}

			if ( $rule['type'] === 'literal' ) {
				$matched = stripos( $message, $rule['pattern'] ) !== false;
			}

			if ( $matched ) {
				// Apply dynamic scoring based on context
				$rule['weight'] = self::applyContextScore( $rule, $context );
				$matches[]      = $rule;
			}
		}

		if ( empty( $matches ) ) {
			return array(
				'category'   => __( 'Unclassified', 'bugsneak' ),
				'severity'   => 'unknown',
				'confidence' => 0,
				'tags'       => array(),
				'suggestion' => __( 'Review stack trace and recent changes.', 'bugsneak' ),
			);
		}

		// Sort by weight (highest first)
		usort(
			$matches,
			function ( $a, $b ) {
				return $b['weight'] <=> $a['weight'];
			}
		);

		$primary = $matches[0];

		return array(
			'category'   => $primary['category'],
			'severity'   => $primary['severity'],
			// Cap confidence at 100%
			'confidence' => min( $primary['weight'], 100 ),
			'tags'       => self::collectTags( $matches ),
			'suggestion' => $primary['suggestion'],
		);
	}

	/**
	 * Apply context-based score adjustments.
	 *
	 * @param array $rule    The matched rule.
	 * @param array $context The environment context.
	 * @return int Modified weight.
	 */
	protected static function applyContextScore( array $rule, array $context ): int {
		$score = $rule['weight'];
		$tags  = $rule['tags'] ?? array();

		// 1. PHP Version Compatibility
		// If PHP < 8.0 and error is compatibility-related -> Boost score
		if ( isset( $context['php_version'] ) && version_compare( $context['php_version'], '8.0', '<' ) ) {
			if ( in_array( 'compatibility', $tags ) || in_array( 'php8', $tags ) ) {
				$score += 15;
			}
		}

		// 2. WordPress Version Check
		// If WP < 6.0 -> Boost score
		if ( isset( $context['wp_version'] ) && version_compare( $context['wp_version'], '6.0', '<' ) ) {
			$score += 5;
		}

		// 3. Environment Context
		if ( ! empty( $context['is_multisite'] ) && in_array( 'multisite', $tags ) ) {
			$score += 10;
		}

		if ( ! empty( $context['is_rest'] ) && in_array( 'rest', $tags ) ) {
			$score += 10;
		}

		if ( ! empty( $context['is_admin'] ) && in_array( 'permission', $tags ) ) {
			$score += 8;
		}

		// 4. Culprit Correlation (Even Smarter)
		// If the suspected culprit matches a tag (e.g., 'woocommerce'), boost confidence
		if ( ! empty( $context['culprit'] ) ) {
			// Simple slug matching
			$culprit = strtolower( $context['culprit'] );
			foreach ( $tags as $tag ) {
				if ( strpos( $culprit, strtolower( $tag ) ) !== false ) {
					$score += 10;
					break;
				}
			}
		}

		// 5. Spike Detection
		if ( ! empty( $context['is_spike'] ) ) {
			$score += 5;
		}

		// 6. Healthy Environment Penalty (Adaptive Scoring)
		// If environment is very modern, likely NOT a legacy issue
		if ( isset( $context['php_version'] ) && version_compare( $context['php_version'], '8.2', '>=' ) &&
			isset( $context['wp_version'] ) && version_compare( $context['wp_version'], '6.4', '>=' )
		) {
			$score -= 5;
		}

		return $score;
	}

	/**
	 * Merge tags from all matched rules
	 */
	protected static function collectTags( array $matches ): array {
		$tags = array();

		foreach ( $matches as $match ) {
			$tags = array_merge( $tags, $match['tags'] );
		}

		return array_values( array_unique( $tags ) );
	}
}
