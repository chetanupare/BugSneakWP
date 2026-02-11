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
			'category'   => 'Memory Exhaustion',
			'severity'   => 'critical',
			'weight'     => 90,
			'tags'       => array( 'memory', 'php', 'fatal' ),
			'suggestion' => 'Increase WP_MEMORY_LIMIT in wp-config.php or inspect memory-heavy plugins.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Maximum execution time of \d+ seconds exceeded/i',
			'category'   => 'Execution Timeout',
			'severity'   => 'critical',
			'weight'     => 85,
			'tags'       => array( 'timeout', 'performance' ),
			'suggestion' => 'Increase max_execution_time in php.ini or optimize long-running processes.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Call to undefined function/i',
			'category'   => 'Missing Function',
			'severity'   => 'high',
			'weight'     => 80,
			'tags'       => array( 'dependency', 'plugin', 'fatal' ),
			'suggestion' => 'Ensure required plugin or dependency is active and loaded properly.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Call to undefined method/i',
			'category'   => 'Missing Method',
			'severity'   => 'high',
			'weight'     => 80,
			'tags'       => array( 'dependency', 'code-error', 'fatal' ),
			'suggestion' => 'Plugin or class version mismatch. Verify compatibility and updates.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Class not found/i',
			'category'   => 'Missing Class',
			'severity'   => 'high',
			'weight'     => 80,
			'tags'       => array( 'autoloader', 'dependency', 'fatal' ),
			'suggestion' => 'Autoloader issue or missing dependency. Check plugin installation integrity.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Cannot redeclare (class|function)/i',
			'category'   => 'Redeclaration Conflict',
			'severity'   => 'high',
			'weight'     => 75,
			'tags'       => array( 'conflict', 'plugin' ),
			'suggestion' => 'Possible plugin conflict or duplicate file inclusion.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/WordPress database error/i',
			'category'   => 'Database Error',
			'severity'   => 'critical',
			'weight'     => 95,
			'tags'       => array( 'database', 'sql' ),
			'suggestion' => 'Verify database credentials and check for corrupted tables.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Base table or view not found/i',
			'category'   => 'Missing Database Table',
			'severity'   => 'critical',
			'weight'     => 90,
			'tags'       => array( 'database', 'schema' ),
			'suggestion' => 'Plugin activation may have failed. Try reactivating plugin.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/MySQL server has gone away/i',
			'category'   => 'Database Connection Lost',
			'severity'   => 'critical',
			'weight'     => 95,
			'tags'       => array( 'database', 'timeout' ),
			'suggestion' => 'Database server timed out or packet size is too large (max_allowed_packet).',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Parse error/i',
			'category'   => 'Syntax Error',
			'severity'   => 'critical',
			'weight'     => 100,
			'tags'       => array( 'php', 'syntax' ),
			'suggestion' => 'Check PHP syntax in the referenced file.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/TypeError/i',
			'category'   => 'Type Mismatch',
			'severity'   => 'high',
			'weight'     => 70,
			'tags'       => array( 'php', 'types' ),
			'suggestion' => 'Invalid parameter type passed to function. Review recent code changes.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Too few arguments to function/i',
			'category'   => 'Argument Mismatch',
			'severity'   => 'high',
			'weight'     => 70,
			'tags'       => array( 'php', 'signature' ),
			'suggestion' => 'Function signature mismatch. Check plugin version compatibility.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Undefined (array key|index|offset)/i',
			'category'   => 'Undefined Array Key',
			'severity'   => 'low',
			'weight'     => 50,
			'tags'       => array( 'notice', 'php' ),
			'suggestion' => 'Check if array key/index exists before accessing it.',
		),

		array(
			'type'       => 'literal',
			'pattern'    => 'Trying to get property of non-object',
			'category'   => 'Invalid Object Access',
			'severity'   => 'medium',
			'weight'     => 60,
			'tags'       => array( 'php', 'logic' ),
			'suggestion' => 'Object expected but null returned. Check conditional logic.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Call to a member function .* on null/i',
			'category'   => 'Null Reference',
			'severity'   => 'high',
			'weight'     => 75,
			'tags'       => array( 'php', 'null' ),
			'suggestion' => 'Object may not be initialized properly.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Cannot modify header information/i',
			'category'   => 'Header Output Issue',
			'severity'   => 'medium',
			'weight'     => 65,
			'tags'       => array( 'php', 'headers' ),
			'suggestion' => 'Output was sent before headers. Check for whitespace or echo statements.',
		),

		array(
			'type'       => 'literal',
			'pattern'    => 'rest_no_route',
			'category'   => 'Invalid REST Route',
			'severity'   => 'medium',
			'weight'     => 55,
			'tags'       => array( 'api', 'rest' ),
			'suggestion' => 'Ensure REST route is registered correctly.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/session_start\(\)/i',
			'category'   => 'Session Conflict',
			'severity'   => 'medium',
			'weight'     => 50,
			'tags'       => array( 'php', 'session' ),
			'suggestion' => 'Session may already be started by another plugin.',
		),

		array(
			'type'       => 'literal',
			'pattern'    => 'GD library',
			'category'   => 'Image Processing Error',
			'severity'   => 'medium',
			'weight'     => 50,
			'tags'       => array( 'images', 'extension' ),
			'suggestion' => 'Ensure GD or Imagick extension is enabled.',
		),

		array(
			'type'       => 'literal',
			'pattern'    => 'Deprecated',
			'category'   => 'Deprecated Function Usage',
			'severity'   => 'low',
			'weight'     => 30,
			'tags'       => array( 'php', 'deprecated' ),
			'suggestion' => 'Plugin may need update for current PHP version.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/cURL error/i',
			'category'   => 'HTTP Connection Failure',
			'severity'   => 'high',
			'weight'     => 70,
			'tags'       => array( 'http', 'network' ),
			'suggestion' => 'External API request failed. Check server connectivity or firewall settings.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Call to (private|protected) method/i',
			'category'   => 'Visibility Violation',
			'severity'   => 'high',
			'weight'     => 70,
			'tags'       => array( 'php', 'oop' ),
			'suggestion' => 'Attempting to access a protected/private class method. Check plugin compatibility.',
		),

		array(
			'type'       => 'literal',
			'pattern'    => 'must be compatible with',
			'category'   => 'Declaration Incompatibility',
			'severity'   => 'medium',
			'weight'     => 60,
			'tags'       => array( 'php', 'oop', 'strict' ),
			'suggestion' => 'Child class method signature does not match parent. Update plugin/theme.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Division by zero/i',
			'category'   => 'Math Error',
			'severity'   => 'medium',
			'weight'     => 50,
			'tags'       => array( 'php', 'math' ),
			'suggestion' => 'Code attempted to divide by zero. Check logical conditions.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/json_decode/i',
			'category'   => 'JSON Parsing Error',
			'severity'   => 'medium',
			'weight'     => 50,
			'tags'       => array( 'php', 'json', 'data' ),
			'suggestion' => 'Failed to decode JSON data. Response might be malformed or empty.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Input variables exceeded/i',
			'category'   => 'Max Input Vars Exceeded',
			'severity'   => 'high',
			'weight'     => 65,
			'tags'       => array( 'php', 'server', 'limit' ),
			'suggestion' => 'Menu/Form too large. Increase max_input_vars in php.ini.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/failed to open stream: Permission denied/i',
			'category'   => 'File Write Permission',
			'severity'   => 'high',
			'weight'     => 85,
			'tags'       => array( 'filesystem', 'permissions' ),
			'suggestion' => 'Server cannot write to file/folder. Check chmod/chown settings.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/failed to open stream/i',
			'category'   => 'Missing File',
			'severity'   => 'high',
			'weight'     => 80,
			'tags'       => array( 'filesystem', 'missing' ),
			'suggestion' => 'Verify file path and ensure plugin/theme files exist.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/move_uploaded_file/i',
			'category'   => 'Upload Failure',
			'severity'   => 'high',
			'weight'     => 80,
			'tags'       => array( 'filesystem', 'upload' ),
			'suggestion' => 'Failed to move uploaded file. Check upload_tmp_dir or permissions.',
		),
		array(
			'type'       => 'regex',
			'pattern'    => '/There has been a critical error on this website/i',
			'category'   => 'WordPress Critical Error',
			'severity'   => 'critical',
			'weight'     => 100,
			'tags'       => array( 'wp-core', 'fatal' ),
			'suggestion' => 'Enable WP_DEBUG to view detailed error logs.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/nonce verification failed/i',
			'category'   => 'Nonce Verification Failure',
			'severity'   => 'medium',
			'weight'     => 70,
			'tags'       => array( 'security', 'nonce' ),
			'suggestion' => 'Nonce may be expired or invalid. Refresh the page and retry.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Error establishing a database connection/i',
			'category'   => 'Database Connection Failure',
			'severity'   => 'critical',
			'weight'     => 100,
			'tags'       => array( 'database', 'wp-config' ),
			'suggestion' => 'Verify DB credentials in wp-config.php and check MySQL service.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Plugin could not be activated because it triggered a fatal error/i',
			'category'   => 'Plugin Activation Failure',
			'severity'   => 'high',
			'weight'     => 90,
			'tags'       => array( 'plugin', 'activation' ),
			'suggestion' => 'Review stack trace to identify incompatible code or PHP version mismatch.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Required parameter .* follows optional parameter/i',
			'category'   => 'PHP 8 Compatibility Issue',
			'severity'   => 'high',
			'weight'     => 85,
			'tags'       => array( 'php8', 'compatibility' ),
			'suggestion' => 'Update plugin or fix function signature for PHP 8 compatibility.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/rest_forbidden/i',
			'category'   => 'REST Permission Denied',
			'severity'   => 'medium',
			'weight'     => 70,
			'tags'       => array( 'rest', 'permission' ),
			'suggestion' => 'Check REST permission_callback and current_user_can logic.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Block validation failed/i',
			'category'   => 'Block Validation Failure',
			'severity'   => 'medium',
			'weight'     => 75,
			'tags'       => array( 'gutenberg', 'block' ),
			'suggestion' => 'Block markup may differ from saved content. Check custom block rendering.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/wp_cron/i',
			'category'   => 'WP Cron Failure',
			'severity'   => 'medium',
			'weight'     => 60,
			'tags'       => array( 'cron', 'scheduler' ),
			'suggestion' => 'Ensure WP-Cron is enabled or configure a real server cron job.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Table .* doesn\'t exist/i',
			'category'   => 'Missing Database Table',
			'severity'   => 'critical',
			'weight'     => 95,
			'tags'       => array( 'database', 'migration' ),
			'suggestion' => 'Plugin may not have created required tables. Try reactivating it.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/is_multisite/i',
			'category'   => 'Multisite Misconfiguration',
			'severity'   => 'medium',
			'weight'     => 65,
			'tags'       => array( 'multisite', 'network' ),
			'suggestion' => 'Verify multisite constants in wp-config.php.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/ImagickException/i',
			'category'   => 'Imagick Processing Failure',
			'severity'   => 'medium',
			'weight'     => 75,
			'tags'       => array( 'imagick', 'media' ),
			'suggestion' => 'Ensure Imagick extension is installed and enabled.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/Cannot modify header information - headers already sent/i',
			'category'   => 'Headers Already Sent',
			'severity'   => 'medium',
			'weight'     => 80,
			'tags'       => array( 'output', 'php' ),
			'suggestion' => 'Remove whitespace before <?php or after closing ?> tags.',
		),

		array(
			'type'       => 'regex',
			'pattern'    => '/SSL certificate problem/i',
			'category'   => 'SSL Certificate Error',
			'severity'   => 'high',
			'weight'     => 85,
			'tags'       => array( 'ssl', 'api' ),
			'suggestion' => 'Verify SSL certificate chain and hosting configuration.',
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
				'category'   => 'Unclassified',
				'severity'   => 'unknown',
				'confidence' => 0,
				'tags'       => array(),
				'suggestion' => 'Review stack trace and recent changes.',
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
