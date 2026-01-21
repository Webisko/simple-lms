<?php
/**
 * Integration Tests for Simple LMS REST API
 *
 * @package SimpleLMS\Tests\Integration
 * @since 1.3.3
 */

declare(strict_types=1);

namespace SimpleLMS\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;

/**
 * REST API Integration Tests
 *
 * Tests the complete REST API functionality including:
 * - Endpoint registration
 * - Authentication and permissions
 * - CRUD operations for courses, modules, lessons
 * - Progress tracking
 * - Error handling
 * - Response formatting
 */
class RestAPITest extends TestCase
{
    /**
     * Set up test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!\defined('WP_TESTS_DIR')) {
            $this->markTestSkipped('Requires WordPress integration test suite (run with phpunit-integration.xml / wp-env).');
        }

        Monkey\setUp();
    }

    /**
     * Clean up test environment after each test
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // Endpoint Registration Tests
    // ==========================================

    /**
     * Test that all required REST endpoints are registered
     */
    public function testAllEndpointsAreRegistered(): void
    {
        $expectedRoutes = [
            '/simple-lms/v1/courses',
            '/simple-lms/v1/courses/(?P<id>\d+)',
            '/simple-lms/v1/courses/(?P<course_id>\d+)/modules',
            '/simple-lms/v1/modules/(?P<id>\d+)',
            '/simple-lms/v1/modules/(?P<module_id>\d+)/lessons',
            '/simple-lms/v1/lessons/(?P<id>\d+)',
            '/simple-lms/v1/progress/(?P<user_id>\d+)',
            '/simple-lms/v1/progress/(?P<user_id>\d+)/(?P<lesson_id>\d+)',
        ];

        $registeredRoutes = [];

        Functions\expect('register_rest_route')
            ->times(count($expectedRoutes))
            ->with(
                Mockery::anyOf('simple-lms/v1'),
                Mockery::type('string'),
                Mockery::type('array')
            )
            ->andReturnUsing(function ($namespace, $route, $args) use (&$registeredRoutes) {
                $registeredRoutes[] = "/$namespace/$route";
                return true;
            });

        // Trigger endpoint registration
        do_action('rest_api_init');

        foreach ($expectedRoutes as $route) {
            $this->assertContains($route, $registeredRoutes, "Route $route should be registered");
        }
    }

    /**
     * Test that namespace constant is correctly defined
     */
    public function testNamespaceConstantIsDefined(): void
    {
        $this->assertEquals('simple-lms/v1', \SimpleLMS\Rest_API::NAMESPACE);
    }

    // ==========================================
    // Courses Endpoint Tests
    // ==========================================

    /**
     * Test GET /courses returns list of courses with pagination
     */
    public function testGetCoursesReturnsListWithPagination(): void
    {
        // Mock WP_Query
        $mockQuery = Mockery::mock('overload:WP_Query');
        $mockQuery->shouldReceive('__construct')->once();
        $mockQuery->posts = [
            $this->createMockPost(1, 'course', 'Course 1'),
            $this->createMockPost(2, 'course', 'Course 2'),
        ];
        $mockQuery->found_posts = 25;
        $mockQuery->max_num_pages = 3;

        // Mock WordPress functions
        Functions\expect('get_current_user_id')->andReturn(42);
        Functions\expect('get_user_meta')->andReturn([]);
        Functions\expect('current_user_can')->with('edit_posts')->andReturn(false);
        Functions\expect('get_the_post_thumbnail_url')->andReturn('https://example.com/image.jpg');
        Functions\expect('get_post_meta')->andReturn(false);

        // Mock REST functions
        Functions\expect('rest_ensure_response')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturnUsing(function ($data) {
                $response = Mockery::mock('WP_REST_Response');
                $response->shouldReceive('header')->times(2);
                return $response;
            });

        // Create mock request
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('per_page')
            ->andReturn(10);
        $request->shouldReceive('get_param')
            ->with('page')
            ->andReturn(1);
        $request->shouldReceive('get_param')
            ->with('orderby')
            ->andReturn('date');
        $request->shouldReceive('get_param')
            ->with('order')
            ->andReturn('DESC');
        $request->shouldReceive('get_param')
            ->with('search')
            ->andReturn(null);
        $request->shouldReceive('get_param')
            ->with('category')
            ->andReturn(null);

        $response = \SimpleLMS\Rest_API::getCourses($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
    }

    /**
     * Test GET /courses supports search parameter
     */
    public function testGetCoursesSupportsSearch(): void
    {
        $mockQuery = Mockery::mock('overload:WP_Query');
        $mockQuery->shouldReceive('__construct')
            ->once()
            ->with(Mockery::on(function ($args) {
                return isset($args['s']) && $args['s'] === 'wordpress';
            }));
        $mockQuery->posts = [];
        $mockQuery->found_posts = 0;
        $mockQuery->max_num_pages = 0;

        Functions\expect('sanitize_text_field')->with('wordpress')->andReturn('wordpress');
        Functions\expect('get_current_user_id')->andReturn(42);
        Functions\expect('rest_ensure_response')->andReturn(Mockery::mock('WP_REST_Response'));

        $request = $this->createMockRequest(['search' => 'wordpress']);
        $response = \SimpleLMS\Rest_API::getCourses($request);

        $this->assertNotNull($response);
    }

    /**
     * Test GET /courses/{id} returns single course with modules
     */
    public function testGetCourseReturnsSingleCourseWithModules(): void
    {
        $courseId = 123;
        $coursePost = $this->createMockPost($courseId, 'course', 'Test Course');

        Functions\expect('get_post')->with($courseId)->andReturn($coursePost);
        Functions\expect('get_current_user_id')->andReturn(42);
        Functions\expect('get_user_meta')->andReturn([$courseId]); // User has access
        Functions\expect('current_user_can')->andReturn(false);
        Functions\expect('get_the_post_thumbnail_url')->andReturn(null);
        Functions\expect('get_post_meta')->andReturn(false);

        // Mock Cache_Handler::getCourseModules
        Functions\expect('SimpleLMS\Cache_Handler::getCourseModules')
            ->once()
            ->with($courseId)
            ->andReturn([
                $this->createMockPost(456, 'module', 'Module 1'),
            ]);

        Functions\expect('SimpleLMS\Cache_Handler::getCourseStats')
            ->once()
            ->with($courseId)
            ->andReturn([
                'total_lessons' => 10,
                'completed_lessons' => 5,
                'progress_percentage' => 50,
            ]);

        Functions\expect('rest_ensure_response')
            ->once()
            ->andReturnUsing(function ($data) {
                $this->assertArrayHasKey('modules', $data);
                $this->assertArrayHasKey('stats', $data);
                $this->assertEquals(50, $data['stats']['progress_percentage']);
                return Mockery::mock('WP_REST_Response');
            });

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('id')->andReturn($courseId);

        $response = \SimpleLMS\Rest_API::getCourse($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
    }

    /**
     * Test GET /courses/{id} returns 404 for non-existent course
     */
    public function testGetCourseReturns404ForNonExistentCourse(): void
    {
        $courseId = 999;

        Functions\expect('get_post')->with($courseId)->andReturn(null);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('id')->andReturn($courseId);

        $response = \SimpleLMS\Rest_API::getCourse($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('course_not_found', $response->get_error_code());
    }

    /**
     * Test GET /courses/{id} returns 404 for wrong post type
     */
    public function testGetCourseReturns404ForWrongPostType(): void
    {
        $postId = 123;
        $wrongPost = $this->createMockPost($postId, 'post', 'Regular Post');

        Functions\expect('get_post')->with($postId)->andReturn($wrongPost);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('id')->andReturn($postId);

        $response = \SimpleLMS\Rest_API::getCourse($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('course_not_found', $response->get_error_code());
    }

    // ==========================================
    // Modules Endpoint Tests
    // ==========================================

    /**
     * Test GET /courses/{id}/modules returns course modules
     */
    public function testGetCourseModulesReturnsModulesList(): void
    {
        $courseId = 123;

        Functions\expect('SimpleLMS\Cache_Handler::getCourseModules')
            ->once()
            ->with($courseId)
            ->andReturn([
                $this->createMockPost(456, 'module', 'Module 1'),
                $this->createMockPost(457, 'module', 'Module 2'),
            ]);

        Functions\expect('SimpleLMS\Cache_Handler::getModuleLessons')
            ->times(2)
            ->andReturn([]);

        Functions\expect('get_post_meta')
            ->with(Mockery::anyOf(456, 457), 'parent_course', true)
            ->andReturn($courseId);

        Functions\expect('rest_ensure_response')
            ->once()
            ->andReturnUsing(function ($data) {
                $this->assertCount(2, $data);
                $this->assertEquals('Module 1', $data[0]['title']);
                $this->assertEquals('Module 2', $data[1]['title']);
                return Mockery::mock('WP_REST_Response');
            });

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('course_id')->andReturn($courseId);

        $response = \SimpleLMS\Rest_API::getCourseModules($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
    }

    /**
     * Test GET /modules/{id}/lessons returns module lessons
     */
    public function testGetModuleLessonsReturnsLessonsList(): void
    {
        $moduleId = 456;

        Functions\expect('SimpleLMS\Cache_Handler::getModuleLessons')
            ->once()
            ->with($moduleId)
            ->andReturn([
                $this->createMockPost(789, 'lesson', 'Lesson 1'),
                $this->createMockPost(790, 'lesson', 'Lesson 2'),
            ]);

        Functions\expect('get_post_meta')
            ->with(Mockery::anyOf(789, 790), 'parent_module', true)
            ->andReturn($moduleId);

        Functions\expect('get_the_post_thumbnail_url')->andReturn(null);
        Functions\expect('apply_filters')->andReturnUsing(function ($tag, $content) {
            return $content;
        });

        Functions\expect('rest_ensure_response')
            ->once()
            ->andReturnUsing(function ($data) {
                $this->assertCount(2, $data);
                $this->assertEquals('Lesson 1', $data[0]['title']);
                $this->assertEquals('Lesson 2', $data[1]['title']);
                return Mockery::mock('WP_REST_Response');
            });

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('module_id')->andReturn($moduleId);

        $response = \SimpleLMS\Rest_API::getModuleLessons($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
    }

    // ==========================================
    // Progress Tracking Tests
    // ==========================================

    /**
     * Test GET /progress/{user_id} returns user progress
     */
    public function testGetUserProgressReturnsProgressData(): void
    {
        $userId = 42;

        Functions\expect('SimpleLMS\Progress_Tracker::getUserProgress')
            ->once()
            ->with($userId)
            ->andReturn([
                'user_id' => $userId,
                'courses' => [
                    [
                        'course_id' => 123,
                        'progress_percentage' => 50,
                        'completed_lessons' => 5,
                        'total_lessons' => 10,
                    ],
                ],
                'overall_stats' => [
                    'total_courses' => 3,
                    'completed_courses' => 1,
                    'overall_progress_percentage' => 33,
                ],
            ]);

        Functions\expect('rest_ensure_response')
            ->once()
            ->andReturnUsing(function ($data) use ($userId) {
                $this->assertEquals($userId, $data['user_id']);
                $this->assertArrayHasKey('overall_stats', $data);
                $this->assertEquals(33, $data['overall_stats']['overall_progress_percentage']);
                return Mockery::mock('WP_REST_Response');
            });

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('user_id')->andReturn($userId);

        $response = \SimpleLMS\Rest_API::getUserProgress($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
    }

    /**
     * Test POST /progress/{user_id}/{lesson_id} updates lesson progress
     */
    public function testUpdateLessonProgressMarksLessonAsCompleted(): void
    {
        $userId = 42;
        $lessonId = 789;

        Functions\expect('SimpleLMS\Progress_Tracker::updateLessonProgress')
            ->once()
            ->with($userId, $lessonId, true)
            ->andReturn(true);

        Functions\expect('rest_ensure_response')
            ->once()
            ->andReturnUsing(function ($data) {
                $this->assertTrue($data['success']);
                return Mockery::mock('WP_REST_Response');
            });

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('user_id')->andReturn($userId);
        $request->shouldReceive('get_param')->with('lesson_id')->andReturn($lessonId);
        $request->shouldReceive('get_param')->with('completed')->andReturn(true);

        $response = \SimpleLMS\Rest_API::updateLessonProgress($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
    }

    /**
     * Test POST /progress updates returns error when update fails
     */
    public function testUpdateLessonProgressReturnsErrorOnFailure(): void
    {
        $userId = 42;
        $lessonId = 789;

        Functions\expect('SimpleLMS\Progress_Tracker::updateLessonProgress')
            ->once()
            ->with($userId, $lessonId, true)
            ->andReturn(false);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('user_id')->andReturn($userId);
        $request->shouldReceive('get_param')->with('lesson_id')->andReturn($lessonId);
        $request->shouldReceive('get_param')->with('completed')->andReturn(true);

        $response = \SimpleLMS\Rest_API::updateLessonProgress($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('update_failed', $response->get_error_code());
    }

    // ==========================================
    // Permission Tests
    // ==========================================

    /**
     * Test checkReadPermission allows public access
     */
    public function testCheckReadPermissionAllowsPublicAccess(): void
    {
        $result = \SimpleLMS\Rest_API::checkReadPermission();

        $this->assertTrue($result, 'Public read access should be allowed');
    }

    /**
     * Test checkEditPermission requires edit_posts capability
     */
    public function testCheckEditPermissionRequiresEditPosts(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(true);

        $result = \SimpleLMS\Rest_API::checkEditPermission();

        $this->assertTrue($result);
    }

    /**
     * Test checkEditPermission denies without edit_posts capability
     */
    public function testCheckEditPermissionDeniesWithoutCapability(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(false);

        $result = \SimpleLMS\Rest_API::checkEditPermission();

        $this->assertFalse($result);
    }

    /**
     * Test checkCourseReadPermission allows admin access
     */
    public function testCheckCourseReadPermissionAllowsAdminAccess(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(true);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('id')->andReturn(123);

        $result = \SimpleLMS\Rest_API::checkCourseReadPermission($request);

        $this->assertTrue($result, 'Admins should have access to all courses');
    }

    /**
     * Test checkCourseReadPermission allows tag-based access
     */
    public function testCheckCourseReadPermissionAllowsTagBasedAccess(): void
    {
        $courseId = 123;

        Functions\expect('current_user_can')->with('edit_posts')->andReturn(false);
        Functions\expect('get_current_user_id')->andReturn(42);
        Functions\expect('get_user_meta')
            ->once()
            ->with(42, 'simple_lms_course_access', true)
            ->andReturn([$courseId, 456]); // User has access to courses 123 and 456

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('id')->andReturn($courseId);

        $result = \SimpleLMS\Rest_API::checkCourseReadPermission($request);

        $this->assertTrue($result, 'User with tag-based access should be allowed');
    }

    /**
     * Test checkCourseReadPermission denies without access
     */
    public function testCheckCourseReadPermissionDeniesWithoutAccess(): void
    {
        $courseId = 123;

        Functions\expect('current_user_can')->with('edit_posts')->andReturn(false);
        Functions\expect('get_current_user_id')->andReturn(42);
        Functions\expect('get_user_meta')
            ->once()
            ->with(42, 'simple_lms_course_access', true)
            ->andReturn([456, 789]); // User does NOT have access to course 123

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('id')->andReturn($courseId);

        $result = \SimpleLMS\Rest_API::checkCourseReadPermission($request);

        $this->assertFalse($result, 'User without access should be denied');
    }

    /**
     * Test checkProgressPermission allows self access
     */
    public function testCheckProgressPermissionAllowsSelfAccess(): void
    {
        $userId = 42;

        Functions\expect('get_current_user_id')->andReturn($userId);
        Functions\expect('current_user_can')->with('edit_users')->andReturn(false);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('user_id')->andReturn($userId);

        $result = \SimpleLMS\Rest_API::checkProgressPermission($request);

        $this->assertTrue($result, 'User should be able to view their own progress');
    }

    /**
     * Test checkProgressPermission denies access to other users' progress
     */
    public function testCheckProgressPermissionDeniesOtherUsersProgress(): void
    {
        $currentUserId = 42;
        $targetUserId = 99;

        Functions\expect('get_current_user_id')->andReturn($currentUserId);
        Functions\expect('current_user_can')->with('edit_users')->andReturn(false);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('user_id')->andReturn($targetUserId);

        $result = \SimpleLMS\Rest_API::checkProgressPermission($request);

        $this->assertFalse($result, 'User should not see other users\' progress');
    }

    /**
     * Test checkProgressPermission allows admin access to all users
     */
    public function testCheckProgressPermissionAllowsAdminAccessToAllUsers(): void
    {
        $currentUserId = 42;
        $targetUserId = 99;

        Functions\expect('get_current_user_id')->andReturn($currentUserId);
        Functions\expect('current_user_can')->with('edit_users')->andReturn(true);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('user_id')->andReturn($targetUserId);

        $result = \SimpleLMS\Rest_API::checkProgressPermission($request);

        $this->assertTrue($result, 'Admin should view all users\' progress');
    }

    // ==========================================
    // Error Handling Tests
    // ==========================================

    /**
     * Test API returns WP_Error on exception
     */
    public function testApiReturnsWpErrorOnException(): void
    {
        // Force an exception by mocking a throwing function
        Functions\expect('SimpleLMS\Cache_Handler::getCourseModules')
            ->once()
            ->andThrow(new \Exception('Database error'));

        Functions\expect('error_log')->once();

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')->with('course_id')->andReturn(123);

        $response = \SimpleLMS\Rest_API::getCourseModules($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('api_error', $response->get_error_code());
    }

    // ==========================================
    // Data Formatting Tests
    // ==========================================

    /**
     * Test prepareCourseData formats course correctly
     */
    public function testPrepareCourseDataFormatsCorrectly(): void
    {
        $post = $this->createMockPost(123, 'course', 'Test Course');
        $post->post_date = '2025-01-15 10:30:00';
        $post->post_modified = '2025-01-20 14:22:00';

        Functions\expect('get_current_user_id')->andReturn(42);
        Functions\expect('get_user_meta')->andReturn([123]);
        Functions\expect('current_user_can')->with('edit_posts')->andReturn(false);
        Functions\expect('get_the_post_thumbnail_url')->with(123, 'large')->andReturn('https://example.com/img.jpg');
        Functions\expect('get_post_meta')->with(123, 'allow_comments', true)->andReturn('1');

        // Use reflection to test private method
        $reflection = new \ReflectionClass(\SimpleLMS\Rest_API::class);
        $method = $reflection->getMethod('prepareCourseData');
        $method->setAccessible(true);

        $result = $method->invokeArgs(null, [$post, false]);

        $this->assertEquals(123, $result['id']);
        $this->assertEquals('Test Course', $result['title']);
        $this->assertEquals('test-course', $result['slug']);
        $this->assertEquals('publish', $result['status']);
        $this->assertEquals('https://example.com/img.jpg', $result['featured_image']);
        $this->assertTrue($result['meta']['allow_comments']);
        $this->assertTrue($result['meta']['user_has_access']);
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Create a mock WP_Post object
     *
     * @param int    $id        Post ID
     * @param string $postType  Post type
     * @param string $title     Post title
     * @return \stdClass Mock post object
     */
    private function createMockPost(int $id, string $postType, string $title): \stdClass
    {
        $post = new \stdClass();
        $post->ID = $id;
        $post->post_type = $postType;
        $post->post_title = $title;
        $post->post_name = strtolower(str_replace(' ', '-', $title));
        $post->post_content = "Content for $title";
        $post->post_excerpt = "Excerpt for $title";
        $post->post_status = 'publish';
        $post->post_date = '2025-01-15 10:00:00';
        $post->post_modified = '2025-01-20 15:00:00';
        $post->menu_order = 0;
        $post->comment_status = 'open';

        return $post;
    }

    /**
     * Create a mock WP_REST_Request object with parameters
     *
     * @param array $params Request parameters
     * @return Mockery\MockInterface
     */
    private function createMockRequest(array $params = []): Mockery\MockInterface
    {
        $request = Mockery::mock('WP_REST_Request');

        foreach ($params as $key => $value) {
            $request->shouldReceive('get_param')->with($key)->andReturn($value);
        }

        // Default values for common parameters
        $defaults = [
            'per_page' => 10,
            'page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'search' => null,
            'category' => null,
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($params[$key])) {
                $request->shouldReceive('get_param')->with($key)->andReturn($value);
            }
        }

        return $request;
    }
}
