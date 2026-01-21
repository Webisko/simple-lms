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
        if (\function_exists('SimpleLMS\\simple_lms_reset_post_meta_cache')) {
            \SimpleLMS\simple_lms_reset_post_meta_cache();
        }
        $_POST = [];

        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Set up commonly used WordPress functions with default behaviors
     */
    protected function setUpCommonWordPressFunctions(): void
    {
        // Activation/deactivation hooks are not part of Brain Monkey's hook API
        Monkey\Functions\when('register_activation_hook')->justReturn(true);
        Monkey\Functions\when('register_deactivation_hook')->justReturn(true);

        // Common environment helpers
        Monkey\Functions\when('is_admin')->justReturn(false);
        Monkey\Functions\when('wp_doing_ajax')->justReturn(false);
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
