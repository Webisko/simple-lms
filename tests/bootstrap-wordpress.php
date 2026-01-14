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

// Load WordPress test bootstrap
require_once WP_TESTS_DIR . '/includes/functions.php';

/**
 * Manually load plugin for testing
 */
function _manually_load_plugin() {
    // Load Simple LMS plugin
    require dirname(dirname(__FILE__)) . '/simple-lms.php';
    
    // Initialize components needed for tests
    \SimpleLMS\Access_Control::init();
    \SimpleLMS\Progress_Tracker::init();
    \SimpleLMS\Cache_Handler::init();
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start WordPress test environment
require WP_TESTS_DIR . '/includes/bootstrap.php';

// Load test case base class
require dirname(__FILE__) . '/TestCase.php';
