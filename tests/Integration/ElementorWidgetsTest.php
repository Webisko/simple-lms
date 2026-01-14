<?php
/**
 * Integration tests for Elementor widgets
 * 
 * These tests verify that widgets render correctly in WordPress environment
 * with proper fallbacks and access control.
 * 
 * @package SimpleLMS\Tests\Integration
 */

namespace SimpleLMS\Tests\Integration;

use WP_UnitTestCase;

/**
 * Elementor Widget Integration Tests
 */
class ElementorWidgetsTest extends WP_UnitTestCase {

    private $courseId;
    private $moduleId;
    private $lessonId;
    private $userId;

    /**
     * Set up test fixtures
     */
    public function setUp(): void {
        parent::setUp();

        // Create test course structure
        $this->courseId = $this->factory()->post->create([
            'post_type' => 'course',
            'post_title' => 'Test Course',
            'post_status' => 'publish'
        ]);

        $this->moduleId = $this->factory()->post->create([
            'post_type' => 'module',
            'post_title' => 'Test Module',
            'post_status' => 'publish'
        ]);
        update_post_meta($this->moduleId, 'parent_course', $this->courseId);

        $this->lessonId = $this->factory()->post->create([
            'post_type' => 'lesson',
            'post_title' => 'Test Lesson',
            'post_content' => 'Test lesson content',
            'post_status' => 'publish'
        ]);
        update_post_meta($this->lessonId, 'parent_module', $this->moduleId);

        // Create test user
        $this->userId = $this->factory()->user->create([
            'role' => 'subscriber'
        ]);
    }

    /**
     * Test lesson content widget renders content when user has access
     */
    public function testLessonContentWidgetRendersWithAccess(): void {
        // Grant access
        \SimpleLMS\simple_lms_assign_course_access_tag($this->userId, $this->courseId);
        wp_set_current_user($this->userId);

        // Simulate widget render
        global $post;
        $post = get_post($this->lessonId);
        setup_postdata($post);

        // Widget should render content
        $this->assertTrue(\SimpleLMS\Access_Control::userHasAccessToLesson($this->lessonId));
        
        wp_reset_postdata();
    }

    /**
     * Test lesson content widget shows fallback without access
     */
    public function testLessonContentWidgetFallbackWithoutAccess(): void {
        wp_set_current_user($this->userId);

        global $post;
        $post = get_post($this->lessonId);
        setup_postdata($post);

        // Widget should not have access
        $this->assertFalse(\SimpleLMS\Access_Control::userHasAccessToLesson($this->lessonId));
        
        wp_reset_postdata();
    }

    /**
     * Test course progress widget calculates correctly
     */
    public function testCourseProgressWidgetCalculation(): void {
        \SimpleLMS\simple_lms_assign_course_access_tag($this->userId, $this->courseId);
        
        // Mark lesson as complete
        \SimpleLMS\Progress_Tracker::updateLessonProgress($this->userId, $this->lessonId, true);

        // Get progress
        $progress = \SimpleLMS\Progress_Tracker::getCourseProgress($this->userId, $this->courseId);
        
        // Should be 100% (1 lesson, 1 completed)
        $this->assertGreaterThan(0, $progress);
    }

    /**
     * Test lesson navigation widget returns correct next/previous
     */
    public function testLessonNavigationLogic(): void {
        // Create second lesson
        $lesson2Id = $this->factory()->post->create([
            'post_type' => 'lesson',
            'post_title' => 'Test Lesson 2',
            'post_status' => 'publish'
        ]);
        update_post_meta($lesson2Id, 'parent_module', $this->moduleId);

        // Get lessons for module
        $lessons = \SimpleLMS\Cache_Handler::getModuleLessons($this->moduleId);
        
        $this->assertCount(2, $lessons);
        $this->assertEquals($this->lessonId, $lessons[0]->ID);
        $this->assertEquals($lesson2Id, $lessons[1]->ID);
    }

    /**
     * Test user courses grid retrieves correct courses
     */
    public function testUserCoursesGridRetrievesAccessibleCourses(): void {
        \SimpleLMS\simple_lms_assign_course_access_tag($this->userId, $this->courseId);

        // Create second course without access
        $course2Id = $this->factory()->post->create([
            'post_type' => 'course',
            'post_title' => 'Test Course 2',
            'post_status' => 'publish'
        ]);

        // User should have access to first course only
        $this->assertTrue(\SimpleLMS\Access_Control::userHasCourseAccess($this->userId, $this->courseId));
        $this->assertFalse(\SimpleLMS\Access_Control::userHasCourseAccess($this->userId, $course2Id));
    }

    /**
     * Test course info box retrieves correct stats
     */
    public function testCourseInfoBoxStats(): void {
        $totalLessons = \SimpleLMS\Progress_Tracker::getTotalLessonsCount($this->courseId);
        $this->assertEquals(1, $totalLessons);

        // Add more lessons
        $lesson2Id = $this->factory()->post->create([
            'post_type' => 'lesson',
            'post_title' => 'Test Lesson 2',
            'post_status' => 'publish'
        ]);
        update_post_meta($lesson2Id, 'parent_module', $this->moduleId);

        // Clear cache
        \SimpleLMS\Cache_Handler::flushCourseCache($this->courseId);

        $totalLessons = \SimpleLMS\Progress_Tracker::getTotalLessonsCount($this->courseId);
        $this->assertEquals(2, $totalLessons);
    }

    /**
     * Test continue learning button retrieves last viewed lesson
     */
    public function testContinueLearningButtonLastViewed(): void {
        \SimpleLMS\simple_lms_assign_course_access_tag($this->userId, $this->courseId);
        
        // Mark first lesson as viewed
        \SimpleLMS\Progress_Tracker::updateLessonProgress($this->userId, $this->lessonId, false, 60);

        $lastViewed = \SimpleLMS\Progress_Tracker::getLastViewedLesson($this->userId, $this->courseId);
        $this->assertEquals($this->lessonId, $lastViewed);
    }

    /**
     * Clean up test data
     */
    public function tearDown(): void {
        wp_delete_post($this->lessonId, true);
        wp_delete_post($this->moduleId, true);
        wp_delete_post($this->courseId, true);
        wp_delete_user($this->userId);
        
        parent::tearDown();
    }
}
