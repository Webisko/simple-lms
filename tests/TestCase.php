<?php
/**
 * Base Test Case for Simple LMS Tests
 * 
 * Provides common functionality for all test classes
 * 
 * @package SimpleLMS\Tests
 */

namespace SimpleLMS\Tests;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case class
 */
abstract class TestCase extends PHPUnitTestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Setup which is run before each test method
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        
        // Set up common WordPress functions that are used frequently
        $this->setUpCommonWordPressFunctions();
    }

    /**
     * Teardown which is run after each test method
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Set up commonly used WordPress functions with default behaviors
     */
    protected function setUpCommonWordPressFunctions(): void
    {
        // Translation functions
        Monkey\Functions\when('__')->returnArg(1);
        Monkey\Functions\when('_e')->returnArg(1);
        Monkey\Functions\when('esc_html__')->returnArg(1);
        Monkey\Functions\when('esc_attr__')->returnArg(1);
        
        // Escaping functions
        Monkey\Functions\when('esc_html')->returnArg(1);
        Monkey\Functions\when('esc_attr')->returnArg(1);
        Monkey\Functions\when('esc_url')->returnArg(1);
        Monkey\Functions\when('esc_js')->returnArg(1);
        Monkey\Functions\when('esc_sql')->returnArg(1);
        Monkey\Functions\when('sanitize_text_field')->returnArg(1);
        Monkey\Functions\when('sanitize_key')->returnArg(1);
        Monkey\Functions\when('wp_kses_post')->returnArg(1);
        Monkey\Functions\when('esc_url_raw')->returnArg(1);
        
        // Common utility functions
        Monkey\Functions\when('absint')->alias(function ($value) {
            return abs((int) $value);
        });
        
        // Current time
        Monkey\Functions\when('current_time')->alias(function ($type) {
            return $type === 'timestamp' ? time() : date('Y-m-d H:i:s');
        });
    }

    /**
     * Helper: Create a mock WP_Post object
     * 
     * @param int $id Post ID
     * @param string $type Post type
     * @param array $extra Extra properties
     * @return object
     */
    protected function createMockPost(int $id, string $type = 'post', array $extra = []): object
    {
        $post = (object) array_merge([
            'ID' => $id,
            'post_type' => $type,
            'post_title' => 'Test Post ' . $id,
            'post_content' => 'Test content',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_date' => '2025-01-01 12:00:00',
            'post_modified' => '2025-01-01 12:00:00',
        ], $extra);

        return $post;
    }

    /**
     * Helper: Create a mock WP_User object
     * 
     * @param int $id User ID
     * @param array $extra Extra properties
     * @return object
     */
    protected function createMockUser(int $id, array $extra = []): object
    {
        $user = (object) array_merge([
            'ID' => $id,
            'user_login' => 'testuser' . $id,
            'user_email' => 'test' . $id . '@example.com',
            'display_name' => 'Test User ' . $id,
        ], $extra);

        return $user;
    }

    /**
     * Helper: Mock WordPress database (wpdb)
     * 
     * @return \Mockery\MockInterface
     */
    protected function createMockWpdb()
    {
        $wpdb = \Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        $wpdb->last_error = '';
        $wpdb->last_query = '';
        
        return $wpdb;
    }

    /**
     * Assert that a WordPress action was added
     * 
     * @param string $hook Hook name
     * @param callable|array $callback Callback function
     * @param int $priority Priority
     */
    protected function assertHookAdded(string $hook, $callback, int $priority = 10): void
    {
        $this->assertTrue(
            Monkey\Actions\has($hook),
            "Failed asserting that action '{$hook}' was added"
        );
    }
}
