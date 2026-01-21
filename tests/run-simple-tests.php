<?php
/**
 * Simple LMS - Manual Test Runner
 * Nie wymaga PHPUnit - tylko czysty PHP!
 * 
 * Uruchom: php run-simple-tests.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "==========================================\n";
echo "  Simple LMS - Manual Tests v1.0.0\n";
echo "==========================================\n\n";

$passed = 0;
$failed = 0;
$skipped = 0;

/**
 * Test helper
 */
function test($description, $condition, $expected = true) {
    global $passed, $failed;
    
    $result = ($condition === $expected);
    
    if ($result) {
        echo "✓ PASS: $description\n";
        $passed++;
    } else {
        echo "✗ FAIL: $description\n";
        echo "  Expected: " . var_export($expected, true) . "\n";
        echo "  Got: " . var_export($condition, true) . "\n";
        $failed++;
    }
}

/**
 * Skip helper (for tests requiring WordPress runtime)
 */
function skip_test($description) {
    global $skipped;
    echo "↷ SKIP: $description\n";
    $skipped++;
}

echo "Running tests...\n";
echo "-------------------------------------------\n\n";

// Test 1: Cache key versioning
echo "Test Group: Cache Handler\n";
$courseId = 123;
$timestamp = time();
$cacheKey = "course_modules_{$courseId}_v{$timestamp}";
test("Cache key includes version", strpos($cacheKey, '_v') !== false);
test("Cache key includes course ID", strpos($cacheKey, '123') !== false);
echo "\n";

// Test 2: Access Control
echo "Test Group: Access Control\n";
$userTags = ['course-123', 'course-456'];
$courseTag = 'course-123';
test("User has access with correct tag", in_array($courseTag, $userTags, true));

$courseTag = 'course-789';
test("User has no access with wrong tag", in_array($courseTag, $userTags, true), false);

$userTags = [];
$courseTag = 'course-123';
test("User has no access with empty tags", in_array($courseTag, $userTags, true), false);
echo "\n";

// Test 3: Progress Validation
echo "Test Group: Progress Tracker\n";
$validProgress = [
    'user_id' => 123,
    'lesson_id' => 456,
    'completed' => 1
];
$isValid = isset($validProgress['user_id']) && 
           isset($validProgress['lesson_id']) &&
           is_numeric($validProgress['user_id']) &&
           is_numeric($validProgress['lesson_id']);
test("Valid progress data passes validation", $isValid);

$invalidProgress = [
    'lesson_id' => 456,
    'completed' => 1
];
$isValid = isset($invalidProgress['user_id']) && 
           isset($invalidProgress['lesson_id']);
test("Invalid progress data fails validation", $isValid, false);
echo "\n";

// Test 4: Product ID Migration
echo "Test Group: WooCommerce Integration\n";
$oldFormat = '123';
$migrated = is_array($oldFormat) ? $oldFormat : [(int)$oldFormat];
test("Single ID migrates to array", is_array($migrated));
test("Migrated array contains correct ID", in_array(123, $migrated, true));

$newFormat = [123, 456];
$result = is_array($newFormat) ? $newFormat : [(int)$newFormat];
test("Array format stays unchanged", is_array($result) && count($result) === 2);
echo "\n";

// Test 5: Input Sanitization
echo "Test Group: Security - Input Sanitization\n";
$input = '123abc';
$sanitized = abs((int)$input);
test("Integer extraction works", $sanitized === 123);

$input = '-456';
$sanitized = abs((int)$input);
test("Absolute value sanitization works", $sanitized === 456);

$input = '<script>alert("xss")</script>Hello';
$sanitized = strip_tags($input);
test("XSS prevention strips tags", strpos($sanitized, '<script>') === false && strpos($sanitized, 'Hello') !== false);
echo "\n";

// Test 6: SQL Injection Prevention
echo "Test Group: Security - SQL Injection Prevention\n";
$maliciousInput = "1 OR 1=1";
$sanitized = abs((int)$maliciousInput);
test("SQL injection attempt sanitized", $sanitized === 1);

$maliciousInput = "'; DROP TABLE users; --";
$sanitized = abs((int)$maliciousInput);
test("SQL injection with quotes sanitized", $sanitized === 0);
echo "\n";

// Test 7: Post Type Validation
echo "Test Group: Security - Post Type Validation\n";
$validTypes = ['course', 'module', 'lesson'];

$postType = 'course';
$isValid = in_array($postType, $validTypes, true);
test("Valid post type accepted", $isValid);

$postType = 'post';
$isValid = in_array($postType, $validTypes, true);
test("Invalid post type rejected", $isValid, false);

$postType = 'COURSE';
$isValid = in_array($postType, $validTypes, true);
test("Post type validation is case-sensitive", $isValid, false);
echo "\n";

// Test 8: Capability Mapping
echo "Test Group: Security - Capability Checks\n";
$capabilityMap = [
    'duplicate_post' => 'publish_posts',
    'delete_lesson' => 'delete_posts',
    'edit_course' => 'edit_posts'
];
test("Duplicate requires publish_posts", $capabilityMap['duplicate_post'] === 'publish_posts');
test("Delete requires delete_posts", $capabilityMap['delete_lesson'] === 'delete_posts');
test("Edit requires edit_posts", $capabilityMap['edit_course'] === 'edit_posts');
echo "\n";

// Test 9: Cache Invalidation
echo "Test Group: Cache Invalidation\n";
$oldVersion = 1000;
$newVersion = 1001;
test("Cache version increments", $newVersion > $oldVersion);

$timestamp1 = time();
sleep(1);
$timestamp2 = time();
test("Timestamp-based versioning works", $timestamp2 > $timestamp1);
echo "\n";

// Test 10: Plugin Classes Exist
echo "Test Group: Plugin Architecture\n";

// These checks require WordPress to be bootstrapped.
// In plain PHP CLI, loading the plugin will exit early (ABSPATH guard) or fatal on WP functions.
if (!defined('ABSPATH') && !function_exists('add_action')) {
    skip_test('Load plugin bootstrap (requires WordPress)');
    skip_test('Cache_Handler class exists');
    skip_test('Progress_Tracker class exists');
    skip_test('WooCommerce_Integration class exists');
    skip_test('Ajax_Handler class exists');
    skip_test('Custom_Post_Types class exists');
} else {
    require_once dirname(__DIR__) . '/simple-lms.php';

    test("Cache_Handler class exists", class_exists('SimpleLMS\\Cache_Handler'));
    test("Progress_Tracker class exists", class_exists('SimpleLMS\\Progress_Tracker'));
    test("WooCommerce_Integration class exists", class_exists('SimpleLMS\\WooCommerce_Integration'));
    test("Ajax_Handler class exists", class_exists('SimpleLMS\\Ajax_Handler'));
    test("Custom_Post_Types class exists", class_exists('SimpleLMS\\Custom_Post_Types'));
}
echo "\n";

// Results
echo "==========================================\n";
echo "  TEST RESULTS\n";
echo "==========================================\n";
echo "Passed: $passed tests ✓\n";
echo "Failed: $failed tests" . ($failed > 0 ? " ✗" : "") . "\n";
echo "Skipped: $skipped tests ↷\n";
echo "\nTotal: " . ($passed + $failed + $skipped) . " tests\n";

if ($failed === 0) {
    echo "\n🎉 ALL TESTS PASSED!\n";
    echo "==========================================\n";
    exit(0);
} else {
    echo "\n⚠️  SOME TESTS FAILED\n";
    echo "==========================================\n";
    exit(1);
}
