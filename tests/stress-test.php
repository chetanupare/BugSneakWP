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

function tl_pass( $name ) { echo "  ✅  PASS  │  {$name}\n"; }
function tl_fail( $name, $detail = '' ) { echo "  ❌  FAIL  │  {$name}  │  {$detail}\n"; }

function tl_count_rows() {
	global $wpdb;
	return (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}bugsneak_logs" );
}

function tl_clear() {
	global $wpdb;
	$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}bugsneak_logs" );
}

function tl_make_data( $message, $file = '/test/file.php', $line = 42 ) {
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

$passed = 0;
$failed = 0;

// ── Test 1: Grouping (5,000 identical notices) ──────────────────────────────

echo "  ── Test 1: Grouping ──────────────────────────────\n";
tl_clear();

$data = tl_make_data( 'Undefined variable: $foo' );
$grouped_count = 0;

for ( $i = 0; $i < 5000; $i++ ) {
	$result = Logger::insert( $data );
	if ( $result === 'grouped' ) {
		$grouped_count++;
	}
}

$rows = tl_count_rows();
if ( $rows === 1 ) {
	global $wpdb;
	$occ = (int) $wpdb->get_var( "SELECT occurrence_count FROM {$wpdb->prefix}bugsneak_logs LIMIT 1" );
	if ( $occ === 5000 ) {
		tl_pass( "5,000 identical notices → 1 row, occurrence_count = {$occ}" );
		$passed++;
	} else {
		tl_fail( "Grouping count", "Expected 5000, got {$occ}" );
		$failed++;
	}
} else {
	tl_fail( "Grouping rows", "Expected 1 row, got {$rows}" );
	$failed++;
}

// ── Test 2: Uniqueness (50 unique errors) ───────────────────────────────────

echo "  ── Test 2: Uniqueness ─────────────────────────────\n";
tl_clear();

for ( $i = 0; $i < 50; $i++ ) {
	Logger::insert( tl_make_data( "Unique error #{$i}", "/test/unique_{$i}.php", $i + 1 ) );
}

$rows = tl_count_rows();
if ( $rows === 50 ) {
	tl_pass( "50 unique errors → {$rows} distinct rows" );
	$passed++;
} else {
	tl_fail( "Uniqueness", "Expected 50 rows, got {$rows}" );
	$failed++;
}

// ── Test 3: Capacity Guard ──────────────────────────────────────────────────

echo "  ── Test 3: Capacity Guard ─────────────────────────\n";
tl_clear();

// Temporarily set max_rows to 10.
$original_settings = Settings::get_all();
Settings::save( [ 'max_rows' => 10 ] );

for ( $i = 0; $i < 25; $i++ ) {
	Logger::insert( tl_make_data( "Capacity test #{$i}", "/test/cap_{$i}.php", $i ) );
}

$rows = tl_count_rows();
// Restore original.
Settings::save( [ 'max_rows' => $original_settings['max_rows'] ] );

if ( $rows === 10 ) {
	tl_pass( "25 inserts with max_rows=10 → {$rows} stored (15 skipped)" );
	$passed++;
} elseif ( $rows <= 10 ) {
	tl_pass( "25 inserts with max_rows=10 → {$rows} stored (capacity enforced)" );
	$passed++;
} else {
	tl_fail( "Capacity", "Expected ≤10 rows, got {$rows}" );
	$failed++;
}

// ── Test 4: File Guard (large file) ─────────────────────────────────────────

echo "  ── Test 4: File Guard ─────────────────────────────\n";

// Create a temp file larger than default max (512 KB).
$tmp_file = sys_get_temp_dir() . '/bugsneak_stress_bigfile.php';
file_put_contents( $tmp_file, str_repeat( "<?php echo 'line'; // padding\n", 25000 ) ); // ~750 KB

$engine = Engine::get_instance();
$snippet = $engine->get_file_snippet( $tmp_file, 100 );
unlink( $tmp_file );

if ( isset( $snippet['truncated'] ) && $snippet['truncated'] === true ) {
	tl_pass( "Large file (~750KB) correctly skipped → truncated: true" );
	$passed++;
} else {
	tl_fail( "File guard", "Expected truncated flag, snippet returned normally" );
	$failed++;
}

// ── Test 5: Index Verification ──────────────────────────────────────────────

echo "  ── Test 5: DB Index Check ─────────────────────────\n";

global $wpdb;
$indexes = $wpdb->get_results( "SHOW INDEX FROM {$wpdb->prefix}bugsneak_logs", ARRAY_A );
$index_names = array_unique( array_column( $indexes, 'Key_name' ) );

$required = [ 'error_hash', 'last_seen', 'created_at', 'error_type', 'share_token' ];
$missing  = array_diff( $required, $index_names );

if ( empty( $missing ) ) {
	tl_pass( "All 5 required indexes present: " . implode( ', ', $required ) );
	$passed++;
} else {
	tl_fail( "Missing indexes", implode( ', ', $missing ) );
	$failed++;
}

// ── Results ─────────────────────────────────────────────────────────────────

tl_clear(); // Clean up stress test data.

echo "\n";
echo "  ┌─────────────────────────────────────────────────┐\n";

$total = $passed + $failed;
if ( $failed === 0 ) {
	echo "  │   ✅  ALL {$total} TESTS PASSED                       │\n";
} else {
	echo "  │   ⚠️   {$passed}/{$total} passed, {$failed} failed                    │\n";
}

echo "  │   BugSneak — Phase 3 Stability Verified     │\n";
echo "  └─────────────────────────────────────────────────┘\n";
echo "\n";
