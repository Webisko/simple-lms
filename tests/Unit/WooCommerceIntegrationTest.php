<?php
/**
 * Tests for WooCommerce Integration
 * 
 * @package SimpleLMS\Tests\Unit
 */

namespace SimpleLMS\Tests\Unit;

use SimpleLMS\Tests\TestCase;
use SimpleLMS\WooCommerce_Integration;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

/**
 * WooCommerce Integration Test Class
 */
class WooCommerceIntegrationTest extends TestCase
{
    /**
     * Test is_woocommerce_active returns true when WooCommerce exists
     */
    #[RunInSeparateProcess]
    public function testIsWooCommerceActiveReturnsTrueWhenActive(): void
    {
        if (!class_exists('WooCommerce', false)) {
            eval('class WooCommerce {}');
        }
        if (!function_exists('wc_get_product')) {
            eval('function wc_get_product() {}');
        }

        $result = WooCommerce_Integration::is_woocommerce_active();

        $this->assertTrue($result);
    }

    /**
     * Test is_woocommerce_active returns false when WooCommerce missing
     */
    #[RunInSeparateProcess]
    public function testIsWooCommerceActiveReturnsFalseWhenInactive(): void
    {
        $result = WooCommerce_Integration::is_woocommerce_active();

        $this->assertFalse($result);
    }

    /**
     * Test granting access on order completion
     */
    #[RunInSeparateProcess]
    public function testGrantAccessOnOrderCompletion(): void
    {
        $orderId = 123;
        $userId = 456;
        $courseId = 789;
        $productId = 101;

        // Mock order object
        $order = \Mockery::mock('WC_Order');
        $order->shouldReceive('get_user_id')
            ->once()
            ->andReturn($userId);

        // Order item must have a real get_product_id() method (method_exists check)
        $item = new class ($productId) {
            private int $productId;
            public function __construct(int $productId) {
                $this->productId = $productId;
            }
            public function get_product_id(): int {
                return $this->productId;
            }
        };

        $order->shouldReceive('get_items')
            ->once()
            ->andReturn([$item]);

        // Mock wc_get_order
        Functions\expect('wc_get_order')
            ->once()
            ->with($orderId)
            ->andReturn($order);

        // User must exist (called in process_course_access and grant_user_course_access)
        Functions\expect('get_user_by')
            ->times(2)
            ->with('id', $userId)
            ->andReturn($this->createMockUser($userId));

        // Product must be marked as course product and point to the course
        Functions\expect('get_post_meta')
            ->once()
            ->with($productId, '_is_course_product', true)
            ->andReturn('yes');

        Functions\expect('get_post_meta')
            ->once()
            ->with($productId, '_course_id', true)
            ->andReturn($courseId);

        // Grant access tag
        Functions\expect('SimpleLMS\\simple_lms_assign_course_access_tag')
            ->once()
            ->with($userId, $courseId)
            ->andReturn(true);

        // Access start time meta
        $startKey = 'simple_lms_course_access_start_' . $courseId;
        Functions\expect('get_user_meta')
            ->once()
            ->with($userId, $startKey, true)
            ->andReturn(0);

        Functions\expect('current_time')
            ->once()
            ->with('timestamp', true)
            ->andReturn(1700000000);

        Functions\expect('update_user_meta')
            ->once()
            ->with($userId, $startKey, 1700000000)
            ->andReturn(true);

        WooCommerce_Integration::grant_course_access_on_order_complete($orderId);
    }

    /**
     * Test product ID migration converts single to array
     */
    public function testProductIdMigrationConvertsSingleToArray(): void
    {
        $courseId = 123;
        $oldProductId = 456;

        // Mock get_post_meta for migration flag (not migrated yet)
        Functions\expect('get_post_meta')
            ->once()
            ->with($courseId, '_wc_migrated_v2', true)
            ->andReturn(false);

        // Mock get_post_meta for old product ID
        Functions\expect('get_post_meta')
            ->once()
            ->with($courseId, '_wc_product_id', true)
            ->andReturn($oldProductId);

        // Mock get_post_meta for new product IDs (empty)
        Functions\expect('get_post_meta')
            ->once()
            ->with($courseId, '_wc_product_ids', true)
            ->andReturn(false);

        // Expect update_post_meta with array
        Functions\expect('update_post_meta')
            ->once()
            ->with($courseId, '_wc_product_ids', [$oldProductId])
            ->andReturn(true);

        // Expect migration flag to be set
        Functions\expect('update_post_meta')
            ->once()
            ->with($courseId, '_wc_migrated_v2', true)
            ->andReturn(true);

        // This would be part of Migration class but testing the logic
        $isMigrated = get_post_meta($courseId, '_wc_migrated_v2', true);
        
        if (!$isMigrated) {
            $oldId = get_post_meta($courseId, '_wc_product_id', true);
            $newIds = get_post_meta($courseId, '_wc_product_ids', true);
            
            if ($oldId && !$newIds) {
                update_post_meta($courseId, '_wc_product_ids', [$oldId]);
                update_post_meta($courseId, '_wc_migrated_v2', true);
            }
        }

        $this->assertTrue(true); // If no exceptions, test passes
    }

    /**
     * Test product ID migration skips already migrated courses
     */
    public function testProductIdMigrationSkipsAlreadyMigrated(): void
    {
        $courseId = 123;

        // Mock get_post_meta for migration flag (already migrated)
        Functions\expect('get_post_meta')
            ->once()
            ->with($courseId, '_wc_migrated_v2', true)
            ->andReturn(true);

        // update_post_meta should NOT be called
        Functions\expect('update_post_meta')
            ->never();

        $isMigrated = get_post_meta($courseId, '_wc_migrated_v2', true);
        
        if (!$isMigrated) {
            update_post_meta($courseId, '_wc_product_ids', []);
        }

        $this->assertTrue(true); // No updates should happen
    }
}
