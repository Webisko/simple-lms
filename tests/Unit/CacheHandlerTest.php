<?php
/**
 * Tests for Cache_Handler class
 * 
 * @package SimpleLMS\Tests\Unit
 */

namespace SimpleLMS\Tests\Unit;

use SimpleLMS\Tests\TestCase;
use SimpleLMS\Cache_Handler;
use Brain\Monkey\Functions;

/**
 * Cache Handler Test Class
 */
class CacheHandlerTest extends TestCase
{
    /**
     * Test cache key generation includes version
     */
    public function testGenerateCacheKeyIncludesVersion(): void
    {
        // Mock get_post_modified_time to return a fixed timestamp
        Functions\expect('get_post_modified_time')
            ->once()
            ->with('U', true, 123)
            ->andReturn(1732550400);

        // Use reflection to access private method
        $reflection = new \ReflectionClass(Cache_Handler::class);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $key = $method->invoke(null, 'course_modules', 123);

        $this->assertStringContainsString('course_modules_123_v', $key);
        $this->assertStringContainsString('1732550400', $key);
    }

    /**
     * Test getCourseModules returns cached data
     */
    public function testGetCourseModulesReturnsCachedData(): void
    {
        $courseId = 123;
        $cachedModules = [
            $this->createMockPost(1, 'module'),
            $this->createMockPost(2, 'module'),
        ];

        // Mock wp_cache_get to return cached data
        Functions\expect('wp_cache_get')
            ->once()
            ->andReturn($cachedModules);

        $result = Cache_Handler::getCourseModules($courseId);

        $this->assertCount(2, $result);
        $this->assertEquals('module', $result[0]->post_type);
    }

    /**
     * Test getCourseModules with invalid course ID
     */
    public function testGetCourseModulesWithInvalidId(): void
    {
        $result = Cache_Handler::getCourseModules(0);
        $this->assertEmpty($result);
    }

    /**
     * Test cache invalidation on post save
     */
    public function testFlushCourseCacheOnPostSave(): void
    {
        $moduleId = 456;
        $courseId = 123;

        // Mock post object
        $post = $this->createMockPost($moduleId, 'module');

        // Mock get_post_type
        Functions\expect('get_post_type')
            ->once()
            ->with($moduleId)
            ->andReturn('module');

        // Mock get_post_meta to return parent course
        Functions\expect('get_post_meta')
            ->once()
            ->with($moduleId, 'parent_course', true)
            ->andReturn($courseId);

        // Expect cache to be deleted
        Functions\expect('wp_cache_delete')
            ->atLeast()
            ->once();

        Cache_Handler::flushCourseCache($moduleId, $post, true);
    }

    /**
     * Test incrementCacheVersion updates option
     */
    public function testIncrementCacheVersionUpdatesOption(): void
    {
        // Mock get_option to return current version
        Functions\expect('get_option')
            ->once()
            ->with('simple_lms_cache_version', 1)
            ->andReturn(5);

        // Expect update_option to be called with incremented version
        Functions\expect('update_option')
            ->once()
            ->with('simple_lms_cache_version', 6, false)
            ->andReturn(true);

        Cache_Handler::incrementCacheVersion();
    }

    /**
     * Test getCourseStats returns proper structure
     */
    public function testGetCourseStatsReturnsProperStructure(): void
    {
        $courseId = 123;

        // Mock wp_cache_get to return false (no cache)
        Functions\expect('wp_cache_get')
            ->once()
            ->andReturn(false);

        // Mock getCourseModules (it's called internally)
        Functions\expect('wp_cache_get')
            ->once()
            ->andReturn([]); // No modules

        Functions\expect('get_posts')
            ->once()
            ->andReturn([]);

        Functions\expect('wp_cache_set')
            ->atLeast()
            ->once();

        $stats = Cache_Handler::getCourseStats($courseId);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('module_count', $stats);
        $this->assertArrayHasKey('lesson_count', $stats);
        $this->assertEquals(0, $stats['module_count']);
        $this->assertEquals(0, $stats['lesson_count']);
    }
}
