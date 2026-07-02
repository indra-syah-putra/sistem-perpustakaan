<?php
/**
 * Basic unit tests for helper functions.
 * Run: php tests/HelperTest.php
 */

require_once __DIR__ . '/../config/database.php';

$passed = 0;
$failed = 0;

function assert_eq($expected, $actual, $label) {
    global $passed, $failed;
    if ($expected === $actual) {
        echo "  PASS: $label\n";
        $passed++;
    } else {
        echo "  FAIL: $label — expected " . var_export($expected, true) . ", got " . var_export($actual, true) . "\n";
        $failed++;
    }
}

echo "=== Helper Function Tests ===\n\n";

// rupiah
echo "--- rupiah() ---\n";
assert_eq('Rp 1.000', rupiah(1000), '1000');
assert_eq('Rp 0', rupiah(0), '0');
assert_eq('Rp 12.345', rupiah(12345), '12345');

// tgl_indo
echo "\n--- tgl_indo() ---\n";
assert_eq('-', tgl_indo(null), 'null returns -');
$result = tgl_indo('2026-07-02');
assert_eq(true, str_contains($result, 'Juli'), 'July date contains Juli');
assert_eq(true, str_contains($result, '2026'), 'July date contains 2026');

// status_badge
echo "\n--- status_badge() ---\n";
$badge = status_badge('aktif');
assert_eq(true, str_contains($badge, 'aktif'), 'badge contains status text');
assert_eq(true, str_contains($badge, 'bg-success'), 'badge contains bg-success');
assert_eq(true, str_contains($badge, 'badge'), 'badge contains badge class');

// XSS safety: status_badge should escape HTML
echo "\n--- XSS Safety ---\n";
$badge = status_badge('<script>alert("xss")</script>');
assert_eq(true, str_contains($badge, '&lt;'), 'status_badge escapes HTML');

// env function (test with .env values)
echo "\n--- env() ---\n";
assert_eq('perpustakaan', env('DB_NAME'), 'DB_NAME from .env');
assert_eq('default', env('NONEXISTENT', 'default'), 'nonexistent key returns default');

echo "\n=== Results: $passed passed, $failed failed ===\n";
exit($failed > 0 ? 1 : 0);
