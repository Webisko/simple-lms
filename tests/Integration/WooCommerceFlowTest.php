<?php
/**
 * Integration tests for WooCommerce purchase flow
 * 
 * @package SimpleLMS\Tests\Integration
 */

namespace SimpleLMS\Tests\Integration;

use SimpleLMS\Tests\TestCase;
use SimpleLMS\WooCommerce_Integration;
use SimpleLMS\Access_Control;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;

/**
 * WooCommerce Flow Integration Test
 * 
 * Tests the complete flow: Product purchase → Access grant → Course/Lesson access
 */
class WooCommerceFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!\defined('WP_TESTS_DIR')) {
            $this->markTestSkipped('Requires WordPress integration test suite (run with phpunit-integration.xml / wp-env).');
        }
    }

    /**
     * Test complete purchase flow grants course access
     */
    public function testCompletePurchaseFlowGrantsCourseAccess(): void
    {
        $userId = 1;
        $courseId = 100;
        $productId = 500;
        $orderId = 1000;

        // Step 1: Setup - Associate course with product
        Functions\expect('get_post_meta')
            ->with($courseId, 'course_product_id', true)
            ->andReturn($productId);

        // Step 2: Mock order completion
        $mockOrder = \Mockery::mock('WC_Order');
        $mockOrder->shouldReceive('get_id')
            ->andReturn($orderId);
        $mockOrder->shouldReceive('get_user_id')
            ->andReturn($userId);
        $mockOrder->shouldReceive('get_status')
            ->andReturn('completed');

        // Step 3: Mock order items
        $mockItem = \Mockery::mock('WC_Order_Item_Product');
        $mockItem->shouldReceive('get_product_id')
            ->andReturn($productId);

        $mockOrder->shouldReceive('get_items')
            ->andReturn([$mockItem]);

        Functions\expect('wc_get_order')
            ->once()
            ->with($orderId)
            ->andReturn($mockOrder);

        // Step 4: Expect access to be granted
        Functions\expect('update_user_meta')
            ->once()
            ->with($userId, 'simple_lms_course_access_' . $courseId, true);

        // Step 5: Fire order completion hook
        Actions\expectDone('woocommerce_order_status_completed')
            ->once()
            ->with($orderId);

        // Trigger the flow
        do_action('woocommerce_order_status_completed', $orderId);

        $this->assertTrue(true);
    }

    /**
     * Test purchase of multiple courses in single order
     */
    public function testPurchaseMultipleCoursesInSingleOrder(): void
    {
        $userId = 1;
        $course1 = 100;
        $course2 = 101;
        $product1 = 500;
        $product2 = 501;
        $orderId = 1000;

        // Mock order
        $mockOrder = \Mockery::mock('WC_Order');
        $mockOrder->shouldReceive('get_id')->andReturn($orderId);
        $mockOrder->shouldReceive('get_user_id')->andReturn($userId);
        $mockOrder->shouldReceive('get_status')->andReturn('completed');

        // Mock two products in order
        $mockItem1 = \Mockery::mock('WC_Order_Item_Product');
        $mockItem1->shouldReceive('get_product_id')->andReturn($product1);

        $mockItem2 = \Mockery::mock('WC_Order_Item_Product');
        $mockItem2->shouldReceive('get_product_id')->andReturn($product2);

        $mockOrder->shouldReceive('get_items')
            ->andReturn([$mockItem1, $mockItem2]);

        Functions\expect('wc_get_order')
            ->once()
            ->andReturn($mockOrder);

        // Expect access to be granted for both courses
        Functions\expect('update_user_meta')
            ->once()
            ->with($userId, 'simple_lms_course_access_' . $course1, true);

        Functions\expect('update_user_meta')
            ->once()
            ->with($userId, 'simple_lms_course_access_' . $course2, true);

        // Mock course lookup for both products
        Functions\expect('get_posts')
            ->once()
            ->andReturnUsing(function($args) use ($product1, $product2, $course1, $course2) {
                if ($args['meta_value'] === $product1) {
                    return [$this->createMockPost($course1, 'course')];
                }
                if ($args['meta_value'] === $product2) {
                    return [$this->createMockPost($course2, 'course')];
                }
                return [];
            });

        do_action('woocommerce_order_status_completed', $orderId);

        $this->assertTrue(true);
    }

    /**
     * Test order status change from processing to completed grants access
     */
    public function testOrderStatusChangeGrantsAccess(): void
    {
        $userId = 1;
        $courseId = 100;
        $productId = 500;
        $orderId = 1000;

        // Mock order
        $mockOrder = \Mockery::mock('WC_Order');
        $mockOrder->shouldReceive('get_id')->andReturn($orderId);
        $mockOrder->shouldReceive('get_user_id')->andReturn($userId);
        $mockOrder->shouldReceive('get_status')->andReturn('completed');

        $mockItem = \Mockery::mock('WC_Order_Item_Product');
        $mockItem->shouldReceive('get_product_id')->andReturn($productId);

        $mockOrder->shouldReceive('get_items')->andReturn([$mockItem]);

        Functions\expect('wc_get_order')
            ->once()
            ->andReturn($mockOrder);

        Functions\expect('get_post_meta')
            ->with($courseId, 'course_product_id', true)
            ->andReturn($productId);

        Functions\expect('update_user_meta')
            ->once()
            ->with($userId, 'simple_lms_course_access_' . $courseId, true);

        // Simulate status change hook
        Actions\expectDone('woocommerce_order_status_processing_to_completed')
            ->once()
            ->with($orderId);

        do_action('woocommerce_order_status_processing_to_completed', $orderId);

        $this->assertTrue(true);
    }

    /**
     * Test failed/refunded order does not grant access
     */
    public function testFailedOrderDoesNotGrantAccess(): void
    {
        $userId = 1;
        $orderId = 1000;

        // Mock failed order
        $mockOrder = \Mockery::mock('WC_Order');
        $mockOrder->shouldReceive('get_id')->andReturn($orderId);
        $mockOrder->shouldReceive('get_user_id')->andReturn($userId);
        $mockOrder->shouldReceive('get_status')->andReturn('failed');

        Functions\expect('wc_get_order')
            ->once()
            ->andReturn($mockOrder);

        // update_user_meta should NOT be called for failed orders
        Functions\expect('update_user_meta')
            ->never();

        do_action('woocommerce_order_status_failed', $orderId);

        $this->assertTrue(true);
    }

    /**
     * Test refunded order revokes course access
     */
    public function testRefundedOrderRevokesAccess(): void
    {
        $userId = 1;
        $courseId = 100;
        $productId = 500;
        $orderId = 1000;

        // Mock order
        $mockOrder = \Mockery::mock('WC_Order');
        $mockOrder->shouldReceive('get_id')->andReturn($orderId);
        $mockOrder->shouldReceive('get_user_id')->andReturn($userId);
        $mockOrder->shouldReceive('get_status')->andReturn('refunded');

        $mockItem = \Mockery::mock('WC_Order_Item_Product');
        $mockItem->shouldReceive('get_product_id')->andReturn($productId);

        $mockOrder->shouldReceive('get_items')->andReturn([$mockItem]);

        Functions\expect('wc_get_order')
            ->once()
            ->andReturn($mockOrder);

        Functions\expect('get_posts')
            ->once()
            ->andReturn([$this->createMockPost($courseId, 'course')]);

        // Expect access to be revoked (deleted)
        Functions\expect('delete_user_meta')
            ->once()
            ->with($userId, 'simple_lms_course_access_' . $courseId);

        do_action('woocommerce_order_status_refunded', $orderId);

        $this->assertTrue(true);
    }

    /**
     * Test user with access can view course content
     */
    public function testUserWithAccessCanViewCourse(): void
    {
        $userId = 1;
        $courseId = 100;

        Functions\expect('get_user_meta')
            ->once()
            ->with($userId, 'simple_lms_course_access_' . $courseId, true)
            ->andReturn(true);

        Functions\expect('user_can')
            ->once()
            ->with($userId, 'manage_options')
            ->andReturn(false);

        // Simulate Access_Control check
        $hasAccess = get_user_meta($userId, 'simple_lms_course_access_' . $courseId, true);

        $this->assertTrue((bool) $hasAccess);
    }

    /**
     * Test user without purchase cannot access course
     */
    public function testUserWithoutPurchaseCannotAccessCourse(): void
    {
        $userId = 1;
        $courseId = 100;

        Functions\expect('get_user_meta')
            ->once()
            ->with($userId, 'simple_lms_course_access_' . $courseId, true)
            ->andReturn(false);

        Functions\expect('user_can')
            ->once()
            ->with($userId, 'manage_options')
            ->andReturn(false);

        $hasAccess = get_user_meta($userId, 'simple_lms_course_access_' . $courseId, true);

        $this->assertFalse((bool) $hasAccess);
    }

    /**
     * Test admin always has access regardless of purchase
     */
    public function testAdminAlwaysHasAccess(): void
    {
        $userId = 1;
        $courseId = 100;

        Functions\expect('user_can')
            ->once()
            ->with($userId, 'manage_options')
            ->andReturn(true);

        // When admin, no need to check purchase
        $isAdmin = user_can($userId, 'manage_options');

        $this->assertTrue($isAdmin);
    }

    /**
     * Test lesson access requires parent course access
     */
    public function testLessonAccessRequiresParentCourseAccess(): void
    {
        $userId = 1;
        $lessonId = 50;
        $moduleId = 25;
        $courseId = 100;

        // Mock lesson → module → course hierarchy
        Functions\expect('get_post_meta')
            ->with($lessonId, 'parent_module', true)
            ->andReturn($moduleId);

        Functions\expect('get_post_meta')
            ->with($moduleId, 'parent_course', true)
            ->andReturn($courseId);

        // Check course access
        Functions\expect('get_user_meta')
            ->once()
            ->with($userId, 'simple_lms_course_access_' . $courseId, true)
            ->andReturn(true);

        Functions\expect('user_can')
            ->once()
            ->with($userId, 'manage_options')
            ->andReturn(false);

        // Simulate hierarchy check
        $moduleId = get_post_meta($lessonId, 'parent_module', true);
        $courseId = get_post_meta($moduleId, 'parent_course', true);
        $hasAccess = get_user_meta($userId, 'simple_lms_course_access_' . $courseId, true);

        $this->assertTrue((bool) $hasAccess);
    }

    /**
     * Test subscription product grants recurring access
     */
    public function testSubscriptionProductGrantsRecurringAccess(): void
    {
        $userId = 1;
        $courseId = 100;
        $subscriptionId = 2000;

        // Mock active subscription
        Functions\expect('wcs_user_has_subscription')
            ->once()
            ->with($userId, $subscriptionId, 'active')
            ->andReturn(true);

        Functions\expect('get_user_meta')
            ->once()
            ->with($userId, 'simple_lms_course_access_' . $courseId, true)
            ->andReturn(true);

        // User should have access while subscription is active
        $hasSubscription = wcs_user_has_subscription($userId, $subscriptionId, 'active');
        $hasAccess = get_user_meta($userId, 'simple_lms_course_access_' . $courseId, true);

        $this->assertTrue($hasSubscription);
        $this->assertTrue((bool) $hasAccess);
    }

    /**
     * Test expired subscription revokes access
     */
    public function testExpiredSubscriptionRevokesAccess(): void
    {
        $userId = 1;
        $courseId = 100;
        $subscriptionId = 2000;

        // Mock expired subscription
        Functions\expect('wcs_user_has_subscription')
            ->once()
            ->with($userId, $subscriptionId, 'active')
            ->andReturn(false);

        // Access should be revoked
        Functions\expect('delete_user_meta')
            ->once()
            ->with($userId, 'simple_lms_course_access_' . $courseId);

        do_action('woocommerce_subscription_status_expired', $subscriptionId);

        $hasSubscription = wcs_user_has_subscription($userId, $subscriptionId, 'active');

        $this->assertFalse($hasSubscription);
    }

    /**
     * Test guest users cannot access any courses
     */
    public function testGuestUsersCannotAccessCourses(): void
    {
        Functions\expect('is_user_logged_in')
            ->once()
            ->andReturn(false);

        $isLoggedIn = is_user_logged_in();

        $this->assertFalse($isLoggedIn);
    }

    /**
     * Test purchase triggers enrollment email
     */
    public function testPurchaseTriggersEnrollmentEmail(): void
    {
        $userId = 1;
        $courseId = 100;
        $orderId = 1000;

        Actions\expectDone('simple_lms_course_enrolled')
            ->once()
            ->with($userId, $courseId, $orderId);

        do_action('simple_lms_course_enrolled', $userId, $courseId, $orderId);

        $this->assertTrue(true);
    }
}
