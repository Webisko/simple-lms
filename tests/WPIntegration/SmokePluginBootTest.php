<?php
/**
 * WordPress integration smoke tests (wp-env).
 *
 * These tests are intended to run inside a real WordPress runtime via wp-env.
 *
 * @package SimpleLMS\Tests\WPIntegration
 */

declare(strict_types=1);

namespace SimpleLMS\Tests\WPIntegration;

use PHPUnit\Framework\TestCase;

class SmokePluginBootTest extends TestCase
{
    public function test_plugin_bootstraps_and_registers_cpts(): void
    {
        if (!\defined('WPINC') || !\function_exists('register_post_type')) {
            $this->markTestSkipped('WordPress runtime not available.' );
        }

        $this->assertTrue(\function_exists('simpleLmsInit'));
        $this->assertTrue(\class_exists('SimpleLMSPlugin'));

        if (!\function_exists('post_type_exists')) {
            $this->markTestSkipped('post_type_exists() not available in this environment.');
        }

        // Ensure init has run at least once (CPT registration happens on init).
        \do_action('init');

        $this->assertTrue(\post_type_exists('course'));
        $this->assertTrue(\post_type_exists('module'));
        $this->assertTrue(\post_type_exists('lesson'));
    }

    public function test_rest_routes_registered(): void
    {
        if (!\class_exists('WP_REST_Server')) {
            $this->markTestSkipped('WP_REST_Server not available in this environment.');
        }

        global $wp_rest_server;
        $wp_rest_server = new \WP_REST_Server();

        \do_action('rest_api_init');

        $routes = $wp_rest_server->get_routes();

        $this->assertArrayHasKey('/simple-lms/v1/courses', $routes);
        $this->assertArrayHasKey('/simple-lms/v1/modules/(?P<id>\\d+)', $routes);
        $this->assertArrayHasKey('/simple-lms/v1/lessons/(?P<id>\\d+)', $routes);
    }

}
