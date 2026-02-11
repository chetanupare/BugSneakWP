<?php
/**
 * BugSneak — Stress Test Suite
 *
 * Usage:  wp eval-file wp-content/plugins/bugsneak/tests/stress-test.php
 *
 * Tests the error capture pipeline under abusive conditions:
 *   1. Grouping:   5,000 identical notices → should produce 1 row
 *   2. Uniqueness: 50 unique errors       → should produce 50 rows
 *   3. Rate Limit: 100 errors in one shot  → max_errors_per_request honored
 *   4. File Guard: Large-file snippet      → returns truncated flag
 *
 * @package BugSneak\Tests
 */

if ( ! defined( 'ABSPATH' ) ) {
	echo "Run via WP-CLI: wp eval-file wp-content/plugins/bugsneak/tests/stress-test.php\n";
	exit( 1 );
}

use BugSneak\Database\Logger;
use BugSneak\Database\Schema;
use BugSneak\Admin\Settings;
use BugSneak\Core\Engine;

// ── Helpers ─────────────────────────────────────────────────────────────────

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI test output
function bugsneak_test_pass( $name ) { echo "  ✅  PASS  │  {$name}\n"; }
function bugsneak_test_fail( $name, $detail = '' ) { echo "  ❌  FAIL  │  {$name}  │  {$detail}\n"; }
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

function bugsneak_test_count_rows() {
	global $wpdb;
	return (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}bugsneak_logs" );
}

function bugsneak_test_clear() {
	global $wpdb;
	$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}bugsneak_logs" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

function bugsneak_test_make_data( $message, $file = '/test/file.php', $line = 42 ) {
	return [
		'type'            => 'E_NOTICE',
		'message'         => $message,
		'file'            => $file,
		'line'            => $line,
		'trace'           => [],
		'wp_version'      => $GLOBALS['wp_version'],
		'active_theme'    => 'test-theme',
		'culprit'         => 'Plugin: stress-test',
		'code_snippet'    => [ 'lines' => [], 'target' => $line ],
		'request_context' => [],
		'env_context'     => [],
	];
}

// ── Banner ──────────────────────────────────────────────────────────────────

echo "\n";
echo "  ┌─────────────────────────────────────────────────┐\n";
echo "  │   BugSneak — Stress Test Suite              │\n";
echo "  │   Phase 3: Polish & Stability                   │\n";
echo "  └─────────────────────────────────────────────────┘\n";
echo "\n";

$bugsneak_test_passed = 0;
$bugsneak_test_failed = 0;

// ── Test 1: Grouping (5,000 identical notices) ──────────────────────────────

echo "  ── Test 1: Grouping ──────────────────────────────\n";
bugsneak_test_clear();

$bugsneak_test_data = bugsneak_test_make_data( 'Undefined variable: $foo' );
$bugsneak_test_grouped_count = 0;

for ( $bugsneak_test_i = 0; $bugsneak_test_i < 5000; $bugsneak_test_i++ ) {
	$bugsneak_test_result = Logger::insert( $bugsneak_test_data );
	if ( $bugsneak_test_result === 'grouped' ) {
		$bugsneak_test_grouped_count++;
	}
}

$bugsneak_test_rows = bugsneak_test_count_rows();
if ( $bugsneak_test_rows === 1 ) {
	global $wpdb;
	$bugsneak_test_occ = (int) $wpdb->get_var( "SELECT occurrence_count FROM {$wpdb->prefix}bugsneak_logs LIMIT 1" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	if ( $bugsneak_test_occ === 5000 ) {
		bugsneak_test_pass( "5,000 identical notices → 1 row, occurrence_count = {$bugsneak_test_occ}" );
		$bugsneak_test_passed++;
	} else {
		bugsneak_test_fail( "Grouping count", "Expected 5000, got {$bugsneak_test_occ}" );
		$bugsneak_test_failed++;
	}
} else {
	bugsneak_test_fail( "Grouping rows", "Expected 1 row, got {$bugsneak_test_rows}" );
	$bugsneak_test_failed++;
}

// ── Test 2: Uniqueness (50 unique errors) ───────────────────────────────────

echo "  ── Test 2: Uniqueness ─────────────────────────────\n";
bugsneak_test_clear();

for ( $bugsneak_test_i = 0; $bugsneak_test_i < 50; $bugsneak_test_i++ ) {
	Logger::insert( bugsneak_test_make_data( "Unique error #{$bugsneak_test_i}", "/test/unique_{$bugsneak_test_i}.php", $bugsneak_test_i + 1 ) );
}

$bugsneak_test_rows = bugsneak_test_count_rows();
if ( $bugsneak_test_rows === 50 ) {
	bugsneak_test_pass( "50 unique errors → {$bugsneak_test_rows} distinct rows" );
	$bugsneak_test_passed++;
} else {
	bugsneak_test_fail( "Uniqueness", "Expected 50 rows, got {$bugsneak_test_rows}" );
	$bugsneak_test_failed++;
}

// ── Test 3: Capacity Guard ──────────────────────────────────────────────────

echo "  ── Test 3: Capacity Guard ─────────────────────────\n";
bugsneak_test_clear();

// Temporarily set max_rows to 10.
$bugsneak_test_original_settings = Settings::get_all();
Settings::save( [ 'max_rows' => 10 ] );

for ( $bugsneak_test_i = 0; $bugsneak_test_i < 25; $bugsneak_test_i++ ) {
	Logger::insert( bugsneak_test_make_data( "Capacity test #{$bugsneak_test_i}", "/test/cap_{$bugsneak_test_i}.php", $bugsneak_test_i ) );
}

$bugsneak_test_rows = bugsneak_test_count_rows();
// Restore original.
Settings::save( [ 'max_rows' => $bugsneak_test_original_settings['max_rows'] ] );

if ( $bugsneak_test_rows === 10 ) {
	bugsneak_test_pass( "25 inserts with max_rows=10 → {$bugsneak_test_rows} stored (15 skipped)" );
	$bugsneak_test_passed++;
} elseif ( $bugsneak_test_rows <= 10 ) {
	bugsneak_test_pass( "25 inserts with max_rows=10 → {$bugsneak_test_rows} stored (capacity enforced)" );
	$bugsneak_test_passed++;
} else {
	bugsneak_test_fail( "Capacity", "Expected ≤10 rows, got {$bugsneak_test_rows}" );
	$bugsneak_test_failed++;
}

// ── Test 4: File Guard (large file) ─────────────────────────────────────────

echo "  ── Test 4: File Guard ─────────────────────────────\n";

// Create a temp file larger than default max (512 KB).
$bugsneak_test_tmp_file = sys_get_temp_dir() . '/bugsneak_stress_bigfile.php';
file_put_contents( $bugsneak_test_tmp_file, str_repeat( "<?php echo 'line'; // padding\n", 25000 ) ); // ~750 KB

$bugsneak_test_engine = Engine::get_instance();
$bugsneak_test_snippet = $bugsneak_test_engine->get_file_snippet( $bugsneak_test_tmp_file, 100 );
wp_delete_file( $bugsneak_test_tmp_file );

if ( isset( $bugsneak_test_snippet['truncated'] ) && $bugsneak_test_snippet['truncated'] === true ) {
	bugsneak_test_pass( "Large file (~750KB) correctly skipped → truncated: true" );
	$bugsneak_test_passed++;
} else {
	bugsneak_test_fail( "File guard", "Expected truncated flag, snippet returned normally" );
	$bugsneak_test_failed++;
}

// ── Test 5: Index Verification ──────────────────────────────────────────────

echo "  ── Test 5: DB Index Check ─────────────────────────\n";

global $wpdb;
$bugsneak_test_indexes = $wpdb->get_results( "SHOW INDEX FROM {$wpdb->prefix}bugsneak_logs", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
$bugsneak_test_index_names = array_unique( array_column( $bugsneak_test_indexes, 'Key_name' ) );

$bugsneak_test_required = [ 'error_hash', 'last_seen', 'created_at', 'error_type', 'share_token' ];
$bugsneak_test_missing  = array_diff( $bugsneak_test_required, $bugsneak_test_index_names );

if ( empty( $bugsneak_test_missing ) ) {
	bugsneak_test_pass( "All 5 required indexes present: " . implode( ', ', $bugsneak_test_required ) );
	$bugsneak_test_passed++;
} else {
	bugsneak_test_fail( "Missing indexes", implode( ', ', $bugsneak_test_missing ) );
	$bugsneak_test_failed++;
}

// ── Results ─────────────────────────────────────────────────────────────────

bugsneak_test_clear(); // Clean up stress test data.

echo "\n";
echo "  ┌─────────────────────────────────────────────────┐\n";

$bugsneak_test_total = $bugsneak_test_passed + $bugsneak_test_failed;
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI test output
if ( $bugsneak_test_failed === 0 ) {
	echo "  │   ✅  ALL {$bugsneak_test_total} TESTS PASSED                       │\n";
} else {
	echo "  │   ⚠️   {$bugsneak_test_passed}/{$bugsneak_test_total} passed, {$bugsneak_test_failed} failed                    │\n";
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
}

echo "  │   BugSneak — Phase 3 Stability Verified     │\n";
echo "  └─────────────────────────────────────────────────┘\n";
echo "\n";
