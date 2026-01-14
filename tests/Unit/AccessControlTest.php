<?php
/**
 * Tests for Access Control functionality
 * 
 * @package SimpleLMS\Tests\Unit
 */

namespace SimpleLMS\Tests\Unit;

use SimpleLMS\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Access Control Test Class
 */
class AccessControlTest extends TestCase
{
    /**
     * Test userHasAccessToCourse returns true when course ID is in user meta
     */
    public function testUserHasAccessToCourseWithValidTag(): void
    {
        $userId = 123;
        $courseId = 456;

        Functions\expect('get_current_user_id')->once()->andReturn($userId);
        Functions\expect('get_user_meta')
            ->once()
            ->with($userId, 'simple_lms_course_access', true)
            ->andReturn([$courseId, 999]);

        // Expiration meta not set (unlimited)
        Functions\expect('get_user_meta')
            ->once()
            ->with($userId, 'simple_lms_course_access_expiration_' . $courseId, true)
            ->andReturn(0);

        Functions\expect('get_transient')->once()->andReturn(false);
        Functions\expect('set_transient')->once()->andReturn(true);

        require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/access-control.php';
        $this->assertTrue(\SimpleLMS\Access_Control::userHasAccessToCourse($courseId));
    }

    /**
     * Test userHasAccessToCourse returns false when course not assigned
     */
    public function testUserHasAccessToCourseMissingTag(): void
    {
        $userId = 321;
        $courseId = 654;
        Functions\expect('get_current_user_id')->once()->andReturn($userId);
        Functions\expect('get_user_meta')
            ->once()
            ->with($userId, 'simple_lms_course_access', true)
            ->andReturn([111, 222]);
        Functions\expect('get_transient')->once()->andReturn(false);
        Functions\expect('set_transient')->once()->andReturn(true);
        // Expiration check only if initially has access; not called here.
        require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/access-control.php';
        $this->assertFalse(\SimpleLMS\Access_Control::userHasAccessToCourse($courseId));
    }

    /**
     * Test simple_lms_assign_course_access_tag adds course and sets expiration when configured
     */
    public function testAssignCourseAccessTagStoresCourse(): void
    {
        $userId = 777;
        $courseId = 888;
        // Existing meta empty
        Functions\expect('get_user_meta')
            ->once()
            ->with($userId, 'simple_lms_course_access', true)
            ->andReturn([]);
        // Course duration meta
        Functions\expect('get_post_meta')
            ->once()->with($courseId, '_access_duration_value', true)->andReturn(0);
        Functions\expect('get_post_meta')
            ->once()->with($courseId, '_access_duration_unit', true)->andReturn('');
        // Legacy duration meta check
        Functions\expect('get_post_meta')
            ->once()->with($courseId, '_access_duration_days', true)->andReturn(0);
        // Access schedule mode
        Functions\expect('get_post_meta')
            ->once()->with($courseId, '_access_schedule_mode', true)->andReturn('purchase');
        Functions\expect('update_user_meta')
            ->once()->with($userId, 'simple_lms_course_access', [$courseId])->andReturn(true);
        Functions\expect('delete_transient')->once()->andReturn(true);
        require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/access-control.php';
        $this->assertTrue(\SimpleLMS\simple_lms_assign_course_access_tag($userId, $courseId));
    }

    /**
     * Test assigning duplicate does not create extra entries
     */
    public function testAssignCourseAccessTagNoDuplicate(): void
    {
        $userId = 12;
        $courseId = 34;
        Functions\expect('get_user_meta')
            ->once()->with($userId, 'simple_lms_course_access', true)->andReturn([$courseId]);
        // Since already present update_user_meta should not be called
        Functions\expect('update_user_meta')->never();
        Functions\expect('delete_transient')->once()->andReturn(true);
        // Duration meta lookups still occur
        Functions\expect('get_post_meta')->once()->with($courseId, '_access_duration_value', true)->andReturn(0);
        Functions\expect('get_post_meta')->once()->with($courseId, '_access_duration_unit', true)->andReturn('');
        Functions\expect('get_post_meta')->once()->with($courseId, '_access_duration_days', true)->andReturn(0);
        Functions\expect('get_post_meta')->once()->with($courseId, '_access_schedule_mode', true)->andReturn('purchase');
        require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/access-control.php';
        $this->assertTrue(\SimpleLMS\simple_lms_assign_course_access_tag($userId, $courseId));
    }

    /**
     * Test removal of course access updates user meta
     */
    public function testRemoveCourseAccessTag(): void
    {
        $userId = 55; $courseId = 66;
        Functions\expect('get_user_meta')->once()->with($userId, 'simple_lms_course_access', true)->andReturn([$courseId, 77]);
        Functions\expect('update_user_meta')->once()->with($userId, 'simple_lms_course_access', [77])->andReturn(true);
        Functions\expect('delete_transient')->once()->andReturn(true);
        require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/access-control.php';
        $this->assertTrue(\SimpleLMS\simple_lms_remove_course_access_tag($userId, $courseId));
    }
}
