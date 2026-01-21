<?php
/**
 * PHPUnit Bootstrap for Simple LMS Tests
 * 
 * Sets up the testing environment with Brain Monkey for WordPress mocking
 * 
 * @package SimpleLMS\Tests
 */

// Composer autoloader (tests suite dependencies)
require_once __DIR__ . '/vendor/autoload.php';

// Brain Monkey will be set up per-test (see tests/TestCase.php)

// Define WordPress constants that plugins might use
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', false);
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

// Common WordPress time constants
if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}
if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

// wpdb constants
if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

// Minimal WP core class stubs for type hints used in plugin code
if (!class_exists('WP_Post')) {
    class WP_Post {
        /** @var int */
        public $ID = 0;
        /** @var string */
        public $post_type = 'post';
    }
}

if (!class_exists('WP_User')) {
    class WP_User {
        /** @var int */
        public $ID = 0;
    }
}

// Define plugin constants
define('SIMPLE_LMS_VERSION', '1.0.0');
define('SIMPLE_LMS_PLUGIN_DIR', dirname(__DIR__) . '/');
define('SIMPLE_LMS_PLUGIN_URL', 'http://example.com/wp-content/plugins/simple-lms/');
define('SIMPLE_LMS_PLUGIN_BASENAME', 'simple-lms/simple-lms.php');


