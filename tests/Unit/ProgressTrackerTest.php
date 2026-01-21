<?php
/**
 * Tests for Progress_Tracker class
 * 
 * @package SimpleLMS\Tests\Unit
 */

namespace SimpleLMS\Tests\Unit;

use SimpleLMS\Tests\TestCase;
use SimpleLMS\Progress_Tracker;
use Brain\Monkey\Functions;

/**
 * Progress Tracker Test Class
 */
class ProgressTrackerTest extends TestCase
{
    /**
     * Test updateLessonProgress with valid data
     */
    public function testUpdateLessonProgressWithValidData(): void
    {
        $userId = 123;
        $lessonId = 456;
        $moduleId = 789;
        $courseId = 101;

        // Mock get_post_meta for module and course IDs
        Functions\expect('get_post_meta')
            ->once()
            ->with($lessonId, 'parent_module', true)
            ->andReturn($moduleId);

        Functions\expect('get_post_meta')
            ->once()
            ->with($moduleId, 'parent_course', true)
            ->andReturn($courseId);

        // Mock current_time
        Functions\expect('current_time')
            ->once()
            ->with('mysql')
            ->andReturn('2025-11-25 12:00:00');

        // Mock wpdb
        global $wpdb;
        $wpdb = $this->createMockWpdb();
        
        // Mock get_row to return null (no existing record)
        $wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn(null);

        // Mock prepare
        $wpdb->shouldReceive('prepare')
            ->andReturn('SELECT * FROM wp_simple_lms_progress WHERE user_id = 123 AND lesson_id = 456');

        // Mock insert
        $wpdb->shouldReceive('insert')
            ->once()
            ->andReturn(1);

        // Mock wp_cache_delete
        Functions\expect('wp_cache_delete')
            ->times(3);

        // Mock do_action
        Functions\expect('do_action')
            ->once()
            ->with('simple_lms_lesson_progress_updated', $userId, $lessonId, true);

        $result = Progress_Tracker::updateLessonProgress($userId, $lessonId, true, 300);

        $this->assertTrue($result);
    }

    /**
     * Test updateLessonProgress with invalid user ID
     */
    public function testUpdateLessonProgressWithInvalidUserId(): void
    {
        $result = Progress_Tracker::updateLessonProgress(0, 456, true, 0);
        $this->assertFalse($result);
    }

    /**
     * Test updateLessonProgress with invalid lesson ID
     */
    public function testUpdateLessonProgressWithInvalidLessonId(): void
    {
        $result = Progress_Tracker::updateLessonProgress(123, 0, true, 0);
        $this->assertFalse($result);
    }

    /**
     * Test getUserProgress returns cached data
     */
    public function testGetUserProgressReturnsCachedData(): void
    {
        $userId = 123;
        $courseId = 456;
        $cachedProgress = [
            'user_id' => $userId,
            'overall_progress' => [
                [
                    'course_id' => $courseId,
                    'total_lessons' => 10,
                    'completed_lessons' => 3,
                    'completion_percentage' => 30.0,
                    'total_time_spent' => 1800,
                    'last_activity' => '2025-11-25 12:00:00'
                ]
            ],
            'lessons' => [
                ['lesson_id' => 1, 'course_id' => $courseId, 'module_id' => 9, 'completed' => 1, 'completion_date' => '2025-11-25 12:00:00', 'time_spent' => 300],
                ['lesson_id' => 2, 'course_id' => $courseId, 'module_id' => 9, 'completed' => 1, 'completion_date' => '2025-11-25 12:05:00', 'time_spent' => 400],
                ['lesson_id' => 3, 'course_id' => $courseId, 'module_id' => 9, 'completed' => 1, 'completion_date' => '2025-11-25 12:10:00', 'time_spent' => 500]
            ],
            'summary' => [
                'total_courses' => 1,
                'avg_completion' => 30.0
            ]
        ];

        // Mock wp_cache_get with cache group
        Functions\expect('wp_cache_get')
            ->once()
            ->with("simple_lms_progress_{$userId}_{$courseId}", \SimpleLMS\Cache_Handler::CACHE_GROUP)
            ->andReturn($cachedProgress);

        $result = Progress_Tracker::getUserProgress($userId, $courseId);
        $this->assertSame($cachedProgress, $result);
        $this->assertCount(3, $result['lessons']);
    }

    /**
     * Test clearProgressCache deletes correct key
     */
    public function testClearProgressCacheDeletesKeysForCourse(): void
    {
        $userId = 123; $courseId = 999;
        $reflection = new \ReflectionClass(Progress_Tracker::class);
        $method = $reflection->getMethod('clearProgressCache');
        $method->setAccessible(true);
        Functions\expect('wp_cache_delete')->once()->with("simple_lms_progress_{$userId}_0", \SimpleLMS\Cache_Handler::CACHE_GROUP);
        Functions\expect('wp_cache_delete')->once()->with("simple_lms_progress_{$userId}_{$courseId}", \SimpleLMS\Cache_Handler::CACHE_GROUP);
        Functions\expect('wp_cache_delete')->once()->with("simple_lms_course_stats_{$courseId}", \SimpleLMS\Cache_Handler::CACHE_GROUP);
        Functions\expect('do_action')->once()->with('simple_lms_progress_cache_cleared', $userId, $courseId);
        $method->invoke(null, $userId, $courseId);
    }

    /**
     * Test upgradeSchema checks version before upgrading
     */
    public function testUpgradeSchemaChecksVersion(): void
    {
        // Mock wpdb
        global $wpdb;
        $wpdb = $this->createMockWpdb();

        Functions\expect('esc_sql')
            ->once()
            ->andReturnUsing(static fn ($value) => (string) $value);

        // Mock get_results for SHOW INDEX (no indexes)
        $wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]);

        // Mock query for adding indexes (6 possible)
        $wpdb->shouldReceive('query')
            ->times(6)
            ->andReturn(true);

        $reflection = new \ReflectionClass(Progress_Tracker::class);
        $method = $reflection->getMethod('upgradeSchema');
        $method->setAccessible(true);
        $method->invoke(null);
    }

    /**
     * Test upgradeSchema skips if already upgraded
     */
    public function testUpgradeSchemaSkipsIfAlreadyUpgraded(): void
    {
        // wpdb should NOT be called
        global $wpdb;
        $wpdb = $this->createMockWpdb();

        Functions\expect('esc_sql')
            ->once()
            ->andReturnUsing(static fn ($value) => (string) $value);

        // All required indexes already exist
        $wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([
                ['Key_name' => 'user_lesson_completed'],
                ['Key_name' => 'course_stats'],
                ['Key_name' => 'user_course'],
                ['Key_name' => 'updated_at'],
                ['Key_name' => 'user_course_updated'],
                ['Key_name' => 'user_course_completed'],
            ]);

        $wpdb->shouldNotReceive('query');

        $reflection = new \ReflectionClass(Progress_Tracker::class);
        $method = $reflection->getMethod('upgradeSchema');
        $method->setAccessible(true);
        $method->invoke(null);
    }
}
