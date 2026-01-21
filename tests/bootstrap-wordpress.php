<?php
/**
 * Bootstrap for WordPress Integration Tests
 * 
 * This bootstrap loads WordPress test suite for integration tests
 * that require full WordPress environment.
 * 
 * @package SimpleLMS\Tests
 */

// Define test environment
define('SIMPLE_LMS_TESTING', true);

// Set WordPress tests directory
if (!defined('WP_TESTS_DIR')) {
    $wp_tests_dir = getenv('WP_TESTS_DIR');
    if (!$wp_tests_dir) {
        $wp_tests_dir = '/tmp/wordpress-tests-lib';
    }
    define('WP_TESTS_DIR', $wp_tests_dir);
}

// Set WordPress core directory
if (!defined('WP_CORE_DIR')) {
    $wp_core_dir = getenv('WP_CORE_DIR');
    if (!$wp_core_dir) {
        $wp_core_dir = '/tmp/wordpress';
    }
    define('WP_CORE_DIR', $wp_core_dir);
}

// Ensure PHPUnit Polyfills are available for the WP test suite.
if (!defined('WP_TESTS_PHPUNIT_POLYFILLS_PATH')) {
    $polyfills_path = __DIR__ . '/vendor/yoast/phpunit-polyfills';
    if (is_dir($polyfills_path)) {
        define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', $polyfills_path);
    }
}

// Load WordPress test bootstrap
if (file_exists(WP_TESTS_DIR . '/includes/functions.php') && file_exists(WP_TESTS_DIR . '/includes/bootstrap.php')) {
    require_once WP_TESTS_DIR . '/includes/functions.php';

    /**
     * Manually load plugin for testing
     */
    function _manually_load_plugin() {
        require dirname(dirname(__FILE__)) . '/simple-lms.php';
    }

    tests_add_filter('muplugins_loaded', '_manually_load_plugin');

    // Start WordPress test environment
    require WP_TESTS_DIR . '/includes/bootstrap.php';
    return;
}

// Fallback: run against a real wp-env WordPress install without the WP test suite.
$wpCoreDir = getenv('WP_CORE_DIR');
if (!$wpCoreDir) {
    $wpCoreDir = defined('WP_CORE_DIR') ? WP_CORE_DIR : '/var/www/html';
}

$wpLoad = rtrim($wpCoreDir, '/\\') . '/wp-load.php';
if (!file_exists($wpLoad)) {
    fwrite(STDERR, "Could not locate wp-load.php at {$wpLoad}\n");
    exit(1);
}

require_once $wpLoad;

// Ensure the plugin is loaded (safe if already loaded by WordPress).
require_once dirname(dirname(__FILE__)) . '/simple-lms.php';

// In the wp-env fallback path, WordPress is already loaded and 'plugins_loaded'
// has likely fired before requiring the plugin file. Ensure the plugin boots.
if (\function_exists('simpleLmsInit')) {
    \simpleLmsInit();
}
