<?php
/**
 * Tests for Ajax_Handler class
 * 
 * @package SimpleLMS\Tests\Unit
 */

namespace SimpleLMS\Tests\Unit;

use SimpleLMS\Tests\TestCase;
use SimpleLMS\Ajax_Handler;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;

/**
 * AJAX Handler Test Class
 */
class AjaxHandlerTest extends TestCase
{
    /**
     * Test AJAX handler initialization registers hooks
     */
    public function testInitRegistersAjaxHooks(): void
    {
        // Mock add_action calls
        Actions\expectAdded('wp_ajax_add_new_module')
            ->once();
        
        Actions\expectAdded('wp_ajax_delete_lesson')
            ->once();
        
        Actions\expectAdded('wp_ajax_update_modules_order')
            ->once();

        Ajax_Handler::init();
    }

    /**
     * Test verifyAjaxRequest fails with invalid nonce
     */
    public function testVerifyAjaxRequestFailsWithInvalidNonce(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Błąd weryfikacji bezpieczeństwa');

        // Mock check_ajax_referer to return false
        Functions\expect('check_ajax_referer')
            ->once()
            ->with('simple-lms-nonce', 'security', false)
            ->andReturn(false);

        // Use reflection to access private method
        $reflection = new \ReflectionClass(Ajax_Handler::class);
        $method = $reflection->getMethod('verifyAjaxRequest');
        $method->setAccessible(true);

        $method->invoke(null, 'edit_posts');
    }

    /**
     * Test verifyAjaxRequest fails without capability
     */
    public function testVerifyAjaxRequestFailsWithoutCapability(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Niewystarczające uprawnienia');

        // Mock check_ajax_referer to return true
        Functions\expect('check_ajax_referer')
            ->once()
            ->andReturn(true);

        // Mock current_user_can to return false
        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(false);

        $reflection = new \ReflectionClass(Ajax_Handler::class);
        $method = $reflection->getMethod('verifyAjaxRequest');
        $method->setAccessible(true);

        $method->invoke(null, 'edit_posts');
    }

    /**
     * Test verifyAjaxRequest succeeds with valid nonce and capability
     */
    public function testVerifyAjaxRequestSucceedsWithValidCredentials(): void
    {
        Functions\expect('check_ajax_referer')
            ->once()
            ->andReturn(true);

        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(true);

        $reflection = new \ReflectionClass(Ajax_Handler::class);
        $method = $reflection->getMethod('verifyAjaxRequest');
        $method->setAccessible(true);

        // Should not throw exception
        $method->invoke(null, 'edit_posts');

        $this->assertTrue(true);
    }

    /**
     * Test getPostInt sanitizes integer values
     */
    public function testGetPostIntSanitizesValues(): void
    {
        $_POST['test_id'] = '123';

        $reflection = new \ReflectionClass(Ajax_Handler::class);
        $method = $reflection->getMethod('getPostInt');
        $method->setAccessible(true);

        Functions\expect('absint')
            ->once()
            ->with('123')
            ->andReturn(123);

        $result = $method->invoke(null, 'test_id');

        $this->assertEquals(123, $result);
    }

    /**
     * Test getPostString sanitizes text values
     */
    public function testGetPostStringSanitizesValues(): void
    {
        $_POST['test_title'] = '<script>alert("xss")</script>Hello';

        $reflection = new \ReflectionClass(Ajax_Handler::class);
        $method = $reflection->getMethod('getPostString');
        $method->setAccessible(true);

        Functions\expect('sanitize_text_field')
            ->once()
            ->andReturn('Hello');

        $result = $method->invoke(null, 'test_title');

        $this->assertEquals('Hello', $result);
    }

    /**
     * Test validatePostType returns true for valid post type
     */
    public function testValidatePostTypeReturnsTrueForValidType(): void
    {
        $postId = 123;

        $post = $this->createMockPost($postId, 'course');

        Functions\expect('get_post')
            ->once()
            ->with($postId)
            ->andReturn($post);

        $reflection = new \ReflectionClass(Ajax_Handler::class);
        $method = $reflection->getMethod('validatePostType');
        $method->setAccessible(true);

        $result = $method->invoke(null, $postId, 'course');

        $this->assertTrue($result);
    }

    /**
     * Test validatePostType returns false for invalid post type
     */
    public function testValidatePostTypeReturnsFalseForInvalidType(): void
    {
        $postId = 123;

        $post = $this->createMockPost($postId, 'module');

        Functions\expect('get_post')
            ->once()
            ->with($postId)
            ->andReturn($post);

        $reflection = new \ReflectionClass(Ajax_Handler::class);
        $method = $reflection->getMethod('validatePostType');
        $method->setAccessible(true);

        $result = $method->invoke(null, $postId, 'course');

        $this->assertFalse($result);
    }

    /**
     * Test validatePostType returns false for invalid ID
     */
    public function testValidatePostTypeReturnsFalseForInvalidId(): void
    {
        $reflection = new \ReflectionClass(Ajax_Handler::class);
        $method = $reflection->getMethod('validatePostType');
        $method->setAccessible(true);

        $result = $method->invoke(null, 0, 'course');

        $this->assertFalse($result);
    }
}
