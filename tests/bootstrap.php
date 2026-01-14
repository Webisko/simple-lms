<?php
/**
 * PHPUnit Bootstrap for Simple LMS Tests
 * 
 * Sets up the testing environment with Brain Monkey for WordPress mocking
 * 
 * @package SimpleLMS\Tests
 */

// Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Brain Monkey setup
use Brain\Monkey;

// Define WordPress constants that plugins might use
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

// Define plugin constants
define('SIMPLE_LMS_VERSION', '1.3.2');
define('SIMPLE_LMS_PLUGIN_DIR', dirname(__DIR__) . '/');
define('SIMPLE_LMS_PLUGIN_URL', 'http://example.com/wp-content/plugins/simple-lms/');
define('SIMPLE_LMS_PLUGIN_BASENAME', 'simple-lms/simple-lms.php');

// Initialize Brain Monkey before each test
Monkey\setUp();

// Register shutdown function to tear down Brain Monkey after tests
register_shutdown_function(function () {
    Monkey\tearDown();
});
