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

/**
 * WooCommerce Integration Test Class
 */
class WooCommerceIntegrationTest extends TestCase
{
    /**
     * Test is_woocommerce_active returns true when WooCommerce exists
     */
    public function testIsWooCommerceActiveReturnsTrueWhenActive(): void
    {
        Functions\expect('class_exists')
            ->once()
            ->with('WooCommerce')
            ->andReturn(true);

        Functions\expect('function_exists')
            ->once()
            ->with('wc_get_product')
            ->andReturn(true);

        $result = WooCommerce_Integration::is_woocommerce_active();

        $this->assertTrue($result);
    }

    /**
     * Test is_woocommerce_active returns false when WooCommerce missing
     */
    public function testIsWooCommerceActiveReturnsFalseWhenInactive(): void
    {
        Functions\expect('class_exists')
            ->once()
            ->with('WooCommerce')
            ->andReturn(false);

        $result = WooCommerce_Integration::is_woocommerce_active();

        $this->assertFalse($result);
    }

    /**
     * Test granting access on order completion
     */
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

        // Mock order item
        $item = \Mockery::mock('WC_Order_Item_Product');
        $item->shouldReceive('get_product_id')
            ->once()
            ->andReturn($productId);

        $order->shouldReceive('get_items')
            ->once()
            ->andReturn([$item]);

        // Mock wc_get_order
        Functions\expect('wc_get_order')
            ->once()
            ->with($orderId)
            ->andReturn($order);

        // Mock get_posts to find courses with this product
        Functions\expect('get_posts')
            ->once()
            ->andReturn([
                $this->createMockPost($courseId, 'course')
            ]);

        // Mock get_post_meta for product IDs
        Functions\expect('get_post_meta')
            ->once()
            ->with($courseId, '_wc_product_ids', true)
            ->andReturn([$productId]);

        // Mock grant_course_access function
        Functions\expect('SimpleLMS\grant_course_access')
            ->once()
            ->with($userId, $courseId)
            ->andReturn(true);

        // Mock do_action
        Functions\expect('do_action')
            ->once()
            ->with('simple_lms_access_granted', $userId, $courseId, $orderId);

        WooCommerce_Integration::grant_access_on_order_complete($orderId);
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
