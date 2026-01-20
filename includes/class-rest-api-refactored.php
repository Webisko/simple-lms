<?php
/**
 * REST API - Refactored with Dependency Injection
 * 
 * @package SimpleLMS
 * @since 1.5.0
 */

declare(strict_types=1);

namespace SimpleLMS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API handler class - Instance-based with DI
 * 
 * Provides REST API endpoints for external integrations and frontend applications
 * All permission checks, logging, and security operations use injected dependencies
 */
class Rest_API {
    
    /**
     * API namespace
     */
    public const NAMESPACE = 'simple-lms/v1';
    
    /**
     * Logger instance
     *
     * @var Logger
     */
    private Logger $logger;
    
    /**
     * Security service
     *
     * @var Security_Service
     */
    private Security_Service $security;
    
    /**
     * Constructor - Initialize with dependencies
     *
     * @param Logger $logger Logger instance
     * @param Security_Service $security Security service
     */
    public function __construct(Logger $logger, Security_Service $security)
    {
        $this->logger = $logger;
        $this->security = $security;
    }
    
    /**
     * Register REST API endpoints
     * 
     * Called via HookManager on 'rest_api_init'
     *
     * @return void
     */
    public function registerEndpoints(): void
    {
        // Courses endpoints
        register_rest_route(self::NAMESPACE, '/courses', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'getCourses'],
                'permission_callback' => [$this, 'checkReadPermission'],
                'args' => $this->getCoursesArgs()
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'createCourse'],
                'permission_callback' => [$this, 'checkCreateCoursePermission'],
                'args' => $this->getCreateCourseArgs()
            ]
        ]);
        
        register_rest_route(self::NAMESPACE, '/courses/(?P<id>\d+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'getCourse'],
                'permission_callback' => [$this, 'checkCourseReadPermission'],
                'args' => ['id' => ['required' => true, 'type' => 'integer']]
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'updateCourse'],
                'permission_callback' => [$this, 'checkUpdateCoursePermission'],
                'args' => $this->getUpdateCourseArgs()
            ]
        ]);
        
        // Modules endpoints
        register_rest_route(self::NAMESPACE, '/courses/(?P<course_id>\d+)/modules', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'getCourseModules'],
                'permission_callback' => [$this, 'checkCourseReadPermission'],
                'args' => ['course_id' => ['required' => true, 'type' => 'integer']]
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'createModule'],
                'permission_callback' => [$this, 'checkCreateModulePermission'],
                'args' => $this->getCreateModuleArgs()
            ]
        ]);
        
        register_rest_route(self::NAMESPACE, '/modules/(?P<id>\d+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'getModule'],
                'permission_callback' => [$this, 'checkModuleReadPermission'],
                'args' => ['id' => ['required' => true, 'type' => 'integer']]
            ]
        ]);
        
        // Lessons endpoints
        register_rest_route(self::NAMESPACE, '/modules/(?P<module_id>\d+)/lessons', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'getModuleLessons'],
                'permission_callback' => [$this, 'checkModuleReadPermission'],
                'args' => ['module_id' => ['required' => true, 'type' => 'integer']]
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'createLesson'],
                'permission_callback' => [$this, 'checkCreateLessonPermission'],
                'args' => $this->getCreateLessonArgs()
            ]
        ]);
        
        register_rest_route(self::NAMESPACE, '/lessons/(?P<id>\d+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'getLesson'],
                'permission_callback' => [$this, 'checkLessonReadPermission'],
                'args' => ['id' => ['required' => true, 'type' => 'integer']]
            ]
        ]);
        
        // Progress endpoints
        register_rest_route(self::NAMESPACE, '/progress/(?P<user_id>\d+)', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'getUserProgress'],
            'permission_callback' => [$this, 'checkProgressPermission'],
            'args' => ['user_id' => ['required' => true, 'type' => 'integer']]
        ]);
        
        register_rest_route(self::NAMESPACE, '/progress/(?P<user_id>\d+)/(?P<lesson_id>\d+)', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => [$this, 'updateLessonProgress'],
            'permission_callback' => [$this, 'checkProgressUpdatePermission'],
            'args' => [
                'user_id' => ['required' => true, 'type' => 'integer'],
                'lesson_id' => ['required' => true, 'type' => 'integer'],
                'completed' => ['type' => 'boolean', 'default' => true]
            ]
        ]);
        
        $this->logger->info('REST API endpoints registered');
    }
    
    // ============================================================================
    // GET ENDPOINTS
    // ============================================================================
    
    /**
     * Get courses with filtering and pagination
     * 
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function getCourses(\WP_REST_Request $request) {
        try {
            $args = [
                'post_type' => 'course',
                'post_status' => 'publish',
                'posts_per_page' => (int) ($request->get_param('per_page') ?: 10),
                'paged' => (int) ($request->get_param('page') ?: 1),
                'orderby' => sanitize_text_field($request->get_param('orderby') ?: 'date'),
                'order' => strtoupper(sanitize_text_field($request->get_param('order') ?: 'DESC'))
            ];
            
            // Add search parameter
            if ($search = $request->get_param('search')) {
                $args['s'] = sanitize_text_field($search);
            }
            
            // Add category filter
            if ($category = $request->get_param('category')) {
                $args['meta_query'] = [
                    [
                        'key' => 'course_category',
                        'value' => sanitize_text_field($category),
                        'compare' => '='
                    ]
                ];
            }
            
            $query = new \WP_Query($args);
            $courses = [];
            
            foreach ($query->posts as $post) {
                $courses[] = $this->prepareCourseData($post);
            }
            
            $response = rest_ensure_response($courses);
            $response->header('X-WP-Total', (string) $query->found_posts);
            $response->header('X-WP-TotalPages', (string) $query->max_num_pages);
            
            return $response;
            
        } catch (\Exception $e) {
            $this->logger->error('REST getCourses failed: {error}', ['error' => $e->getMessage()]);
            return new \WP_Error('api_error', __('Error fetching courses', 'simple-lms'), ['status' => 500]);
        }
    }
    
    /**
     * Get single course with modules and lessons
     * 
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function getCourse(\WP_REST_Request $request) {
        try {
            $courseId = (int) $request->get_param('id');
            $post = get_post($courseId);
            
            if (!$post || $post->post_type !== 'course') {
                return new \WP_Error('course_not_found', __('Course nie znaleziony', 'simple-lms'), ['status' => 404]);
            }
            
            $courseData = $this->prepareCourseData($post, true);
            return rest_ensure_response($courseData);
            
        } catch (\Exception $e) {
            $this->logger->error('REST getCourse failed: {error}', ['error' => $e->getMessage(), 'courseId' => $request->get_param('id')]);
            return new \WP_Error('api_error', __('Error fetching course', 'simple-lms'), ['status' => 500]);
        }
    }
    
    /**
     * Get course modules
     * 
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function getCourseModules(\WP_REST_Request $request) {
        try {
            $courseId = (int) $request->get_param('course_id');
            $modules = Cache_Handler::getCourseModules($courseId);
            
            $moduleData = array_map([$this, 'prepareModuleData'], $modules);
            return rest_ensure_response($moduleData);
            
        } catch (\Exception $e) {
            $this->logger->error('REST getCourseModules failed: {error}', ['error' => $e->getMessage(), 'courseId' => $request->get_param('course_id')]);
            return new \WP_Error('api_error', __('Error fetching modules', 'simple-lms'), ['status' => 500]);
        }
    }
    
    /**
     * Get single module
     * 
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function getModule(\WP_REST_Request $request) {
        try {
            $moduleId = (int) $request->get_param('id');
            $post = get_post($moduleId);
            
            if (!$post || $post->post_type !== 'module') {
                return new \WP_Error('module_not_found', __('ModuĹ‚ nie znaleziony', 'simple-lms'), ['status' => 404]);
            }
            
            $moduleData = $this->prepareModuleData($post, true);
            return rest_ensure_response($moduleData);
            
        } catch (\Exception $e) {
            $this->logger->error('REST getModule failed: {error}', ['error' => $e->getMessage()]);
            return new \WP_Error('api_error', __('Error fetching module', 'simple-lms'), ['status' => 500]);
        }
    }
    
    /**
     * Get module lessons
     * 
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function getModuleLessons(\WP_REST_Request $request) {
        try {
            $moduleId = (int) $request->get_param('module_id');
            $lessons = Cache_Handler::getModuleLessons($moduleId);
            
            $lessonData = array_map([$this, 'prepareLessonData'], $lessons);
            return rest_ensure_response($lessonData);
            
        } catch (\Exception $e) {
            $this->logger->error('REST getModuleLessons failed: {error}', ['error' => $e->getMessage(), 'moduleId' => $request->get_param('module_id')]);
            return new \WP_Error('api_error', __('Error fetching lesson', 'simple-lms'), ['status' => 500]);
        }
    }
    
    /**
     * Get single lesson
     * 
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function getLesson(\WP_REST_Request $request) {
        try {
            $lessonId = (int) $request->get_param('id');
            $post = get_post($lessonId);
            
            if (!$post || $post->post_type !== 'lesson') {
                return new \WP_Error('lesson_not_found', __('Lekcja nie znaleziona', 'simple-lms'), ['status' => 404]);
            }
            
            $lessonData = $this->prepareLessonData($post);
            return rest_ensure_response($lessonData);
            
        } catch (\Exception $e) {
            $this->logger->error('REST getLesson failed: {error}', ['error' => $e->getMessage()]);
            return new \WP_Error('api_error', __('Error fetching lesson', 'simple-lms'), ['status' => 500]);
        }
    }
    
    /**
     * Get user progress across all courses
     * 
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function getUserProgress(\WP_REST_Request $request) {
        try {
            $userId = (int) $request->get_param('user_id');
            $progress = Progress_Tracker::getUserProgress($userId);
            
            return rest_ensure_response($progress);
            
        } catch (\Exception $e) {
            $this->logger->error('REST getUserProgress failed: {error}', ['error' => $e->getMessage(), 'userId' => $userId]);
            return new \WP_Error('api_error', __('Error fetching progress', 'simple-lms'), ['status' => 500]);
        }
    }
    
    // ============================================================================
    // CREATE/UPDATE ENDPOINTS
    // ============================================================================
    
    /**
     * Create course endpoint handler
     * 
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function createCourse(\WP_REST_Request $request) {
        try {
            $title = sanitize_text_field((string) $request->get_param('title'));
            $status = (string) $request->get_param('status');
            $status = in_array($status, ['draft', 'publish'], true) ? $status : 'draft';
            
            $courseId = wp_insert_post([
                'post_type' => 'course',
                'post_status' => $status,
                'post_title' => $title,
            ]);
            
            if (is_wp_error($courseId) || $courseId <= 0) {
                $this->logger->error('Failed to create course: {error}', ['error' => is_wp_error($courseId) ? $courseId->get_error_message() : 'Unknown error']);
                return new \WP_Error('create_failed', __('Failed to create course', 'simple-lms'), ['status' => 500]);
            }
            
            $this->logger->info('Course created: {courseId}', ['courseId' => $courseId]);
            return rest_ensure_response(['id' => $courseId, 'title' => $title]);
            
        } catch (\Exception $e) {
            $this->logger->error('REST createCourse failed: {error}', ['error' => $e->getMessage()]);
            return new \WP_Error('api_error', __('Error creating course', 'simple-lms'), ['status' => 500]);
        }
    }
    
    /**
     * Update course endpoint handler
     * 
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function updateCourse(\WP_REST_Request $request) {
        try {
            $courseId = (int) $request->get_param('id');
            
            if ($courseId <= 0 || get_post_type($courseId) !== 'course') {
                return new \WP_Error('invalid_course', __('Invalid course', 'simple-lms'), ['status' => 404]);
            }
            
            $update = ['ID' => $courseId];
            
            if ($title = $request->get_param('title')) {
                $update['post_title'] = sanitize_text_field((string) $title);
            }
            
            if ($status = $request->get_param('status')) {
                if (in_array($status, ['draft', 'publish'], true)) {
                    $update['post_status'] = (string) $status;
                }
            }
            
            $result = wp_update_post($update, true);
            
            if (is_wp_error($result)) {
                $this->logger->error('Failed to update course: {error}', ['error' => $result->get_error_message(), 'courseId' => $courseId]);
                return new \WP_Error('update_failed', __('Course update failed', 'simple-lms'), ['status' => 500]);
            }
            
            $this->logger->info('Course updated: {courseId}', ['courseId' => $courseId]);
            return rest_ensure_response(['id' => $courseId, 'updated' => true]);
            
        } catch (\Exception $e) {
            $this->logger->error('REST updateCourse failed: {error}', ['error' => $e->getMessage()]);
            return new \WP_Error('api_error', __('Error updating course', 'simple-lms'), ['status' => 500]);
        }
    }
    
    /**
     * Create module endpoint handler
     * 
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function createModule(\WP_REST_Request $request) {
        try {
            $courseId = (int) $request->get_param('course_id');
            
            if ($courseId <= 0 || get_post_type($courseId) !== 'course') {
                return new \WP_Error('invalid_course', __('Invalid course', 'simple-lms'), ['status' => 404]);
            }
            
            $title = sanitize_text_field((string) $request->get_param('title'));
            
            $moduleId = wp_insert_post([
                'post_type' => 'module',
                'post_status' => 'draft',
                'post_title' => $title,
                'meta_input' => [
                    'parent_course' => $courseId
                ]
            ]);
            
            if (is_wp_error($moduleId) || $moduleId <= 0) {
                $this->logger->error('Failed to create module: {error}', ['error' => is_wp_error($moduleId) ? $moduleId->get_error_message() : 'Unknown error']);
                return new \WP_Error('create_failed', __('Failed to create module', 'simple-lms'), ['status' => 500]);
            }
            
            $this->logger->info('Module created: {moduleId}', ['moduleId' => $moduleId]);
            return rest_ensure_response(['id' => $moduleId, 'title' => $title]);
            
        } catch (\Exception $e) {
            $this->logger->error('REST createModule failed: {error}', ['error' => $e->getMessage()]);
            return new \WP_Error('api_error', __('Error creating module', 'simple-lms'), ['status' => 500]);
        }
    }
    
    /**
     * Create lesson endpoint handler
     * 
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function createLesson(\WP_REST_Request $request) {
        try {
            $moduleId = (int) $request->get_param('module_id');
            
            if ($moduleId <= 0 || get_post_type($moduleId) !== 'module') {
                return new \WP_Error('invalid_module', __('Invalid module', 'simple-lms'), ['status' => 404]);
            }
            
            $title = sanitize_text_field((string) $request->get_param('title'));
            $content = wp_kses_post((string) $request->get_param('content'));
            
            $lessonId = wp_insert_post([
                'post_type' => 'lesson',
                'post_status' => 'draft',
                'post_title' => $title,
                'post_content' => $content,
                'meta_input' => [
                    'parent_module' => $moduleId
                ]
            ]);
            
            if (is_wp_error($lessonId) || $lessonId <= 0) {
                $this->logger->error('Failed to create lesson: {error}', ['error' => is_wp_error($lessonId) ? $lessonId->get_error_message() : 'Unknown error']);
                return new \WP_Error('create_failed', __('Failed to create lesson', 'simple-lms'), ['status' => 500]);
            }
            
            $this->logger->info('Lesson created: {lessonId}', ['lessonId' => $lessonId]);
            return rest_ensure_response(['id' => $lessonId, 'title' => $title]);
            
        } catch (\Exception $e) {
            $this->logger->error('REST createLesson failed: {error}', ['error' => $e->getMessage()]);
            return new \WP_Error('api_error', __('Error creating lesson', 'simple-lms'), ['status' => 500]);
        }
    }
    
    /**
     * Update lesson progress for user
     * 
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function updateLessonProgress(\WP_REST_Request $request) {
        try {
            $userId = (int) $request->get_param('user_id');
            $lessonId = (int) $request->get_param('lesson_id');
            $completed = (bool) $request->get_param('completed');
            
            $result = Progress_Tracker::updateLessonProgress($userId, $lessonId, $completed);
            
            if ($result) {
                $this->logger->info('Lesson progress updated: user={userId}, lesson={lessonId}, completed={completed}', 
                    ['userId' => $userId, 'lessonId' => $lessonId, 'completed' => $completed]);
                return rest_ensure_response(['success' => true, 'message' => __('Progress updated', 'simple-lms')]);
            } else {
                return new \WP_Error('update_failed', __('Failed to update progress', 'simple-lms'), ['status' => 500]);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('REST updateLessonProgress failed: {error}', ['error' => $e->getMessage()]);
            return new \WP_Error('api_error', __('Error updating progress', 'simple-lms'), ['status' => 500]);
        }
    }
    
    // ============================================================================
    // PERMISSION CHECKS
    // ============================================================================
    
    /**
     * Check read permission for public endpoints
     * 
     * @return bool
     */
    public function checkReadPermission(): bool
    {
        return true; // Public read access
    }
    
    /**
     * Check course-specific read permission
     * 
     * @param \WP_REST_Request $request Request object
     * @return bool
     */
    public function checkCourseReadPermission(\WP_REST_Request $request): bool
    {
        $courseId = (int) ($request->get_param('id') ?: $request->get_param('course_id'));
        return $this->userCanAccessCourse($courseId);
    }
    
    /**
     * Check course-specific edit permission
     * 
     * @param \WP_REST_Request $request Request object
     * @return bool
     */
    public function checkUpdateCoursePermission(\WP_REST_Request $request): bool
    {
        $courseId = (int) $request->get_param('id');
        return current_user_can('edit_post', $courseId) && $this->verifyRequestNonce($request);
    }
    
    /**
     * Create course permission (nonce + capability)
     * 
     * @param \WP_REST_Request $request Request object
     * @return bool
     */
    public function checkCreateCoursePermission(\WP_REST_Request $request): bool
    {
        return current_user_can('edit_posts') && $this->verifyRequestNonce($request);
    }
    
    /**
     * Check module read permission
     * 
     * @param \WP_REST_Request $request Request object
     * @return bool
     */
    public function checkModuleReadPermission(\WP_REST_Request $request): bool
    {
        $moduleId = (int) ($request->get_param('id') ?: $request->get_param('module_id'));
        $courseId = (int) get_post_meta($moduleId, 'parent_course', true);
        return $this->userCanAccessCourse($courseId);
    }
    
    /**
     * Create module permission (nonce + capability on parent course)
     * 
     * @param \WP_REST_Request $request Request object
     * @return bool
     */
    public function checkCreateModulePermission(\WP_REST_Request $request): bool
    {
        $courseId = (int) $request->get_param('course_id');
        return current_user_can('edit_post', $courseId) && $this->verifyRequestNonce($request);
    }
    
    /**
     * Check lesson read permission
     * 
     * @param \WP_REST_Request $request Request object
     * @return bool
     */
    public function checkLessonReadPermission(\WP_REST_Request $request): bool
    {
        $lessonId = (int) $request->get_param('id');
        $moduleId = (int) get_post_meta($lessonId, 'parent_module', true);
        $courseId = (int) get_post_meta($moduleId, 'parent_course', true);
        return $this->userCanAccessCourse($courseId);
    }
    
    /**
     * Create lesson permission (nonce + capability on parent module)
     * 
     * @param \WP_REST_Request $request Request object
     * @return bool
     */
    public function checkCreateLessonPermission(\WP_REST_Request $request): bool
    {
        $moduleId = (int) $request->get_param('module_id');
        return current_user_can('edit_post', $moduleId) && $this->verifyRequestNonce($request);
    }
    
    /**
     * Check progress read permission
     * 
     * @param \WP_REST_Request $request Request object
     * @return bool
     */
    public function checkProgressPermission(\WP_REST_Request $request): bool
    {
        $userId = (int) $request->get_param('user_id');
        return current_user_can('edit_users') || get_current_user_id() === $userId;
    }
    
    /**
     * Check progress update permission
     * 
     * @param \WP_REST_Request $request Request object
     * @return bool
     */
    public function checkProgressUpdatePermission(\WP_REST_Request $request): bool
    {
        $userId = (int) $request->get_param('user_id');
        return (get_current_user_id() === $userId || current_user_can('edit_users')) && $this->verifyRequestNonce($request);
    }
    
    // ============================================================================
    // HELPERS
    // ============================================================================
    
    /**
     * Check if user can access course
     * 
     * @param int $courseId Course ID
     * @return bool
     */
    private function userCanAccessCourse(int $courseId): bool
    {
        if ($courseId <= 0) {
            return false;
        }
        
        if (current_user_can('edit_posts')) {
            return true; // Admins retain universal access
        }
        
        // Tag-based access check
        $access = (array) get_user_meta(get_current_user_id(), 'simple_lms_course_access', true);
        return in_array($courseId, $access, true);
    }
    
    /**
     * Verify nonce helper (expects 'nonce' param)
     * 
     * @param \WP_REST_Request $request Request object
     * @return bool
     */
    private function verifyRequestNonce(\WP_REST_Request $request): bool
    {
        $nonce = $request->get_param('nonce');
        if (!$nonce) {
            return false;
        }
        
        return $this->security->verifyNonce((string) $nonce, 'rest');
    }
    
    /**
     * Prepare course data for API response
     * 
     * @param \WP_Post $post Course post object
     * @param bool $includeModules Whether to include modules data
     * @return array Formatted course data
     */
    private function prepareCourseData(\WP_Post $post, bool $includeModules = false): array
    {
        $currentUserId = get_current_user_id();
        $userAccess = (array) get_user_meta($currentUserId, 'simple_lms_course_access', true);
        $hasAccess = current_user_can('edit_posts') || in_array($post->ID, $userAccess, true);

        $data = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'status' => $post->post_status,
            'date_created' => $post->post_date,
            'date_modified' => $post->post_modified,
            'featured_image' => get_the_post_thumbnail_url($post->ID, 'large') ?: null,
            'meta' => [
                'allow_comments' => (bool) get_post_meta($post->ID, 'allow_comments', true),
                'user_has_access' => $hasAccess,
            ]
        ];
        
        if ($includeModules) {
            $modules = Cache_Handler::getCourseModules($post->ID);
            $data['modules'] = array_map([$this, 'prepareModuleData'], $modules);
            $data['stats'] = Cache_Handler::getCourseStats($post->ID);
        }
        
        return $data;
    }
    
    /**
     * Prepare module data for API response
     * 
     * @param \WP_Post $post Module post object
     * @param bool $includeLessons Whether to include lessons data
     * @return array Formatted module data
     */
    private function prepareModuleData(\WP_Post $post, bool $includeLessons = false): array
    {
        $data = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'status' => $post->post_status,
            'order' => (int) $post->menu_order,
            'course_id' => (int) get_post_meta($post->ID, 'parent_course', true),
        ];
        
        $lessons = Cache_Handler::getModuleLessons($post->ID);
        $data['lesson_count'] = count($lessons);
        
        if ($includeLessons) {
            $data['lessons'] = array_map([$this, 'prepareLessonData'], $lessons);
        }
        
        return $data;
    }
    
    /**
     * Prepare lesson data for API response
     * 
     * @param \WP_Post $post Lesson post object
     * @return array Formatted lesson data
     */
    private function prepareLessonData(\WP_Post $post): array
    {
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'content' => apply_filters('the_content', $post->post_content),
            'excerpt' => $post->post_excerpt,
            'status' => $post->post_status,
            'order' => (int) $post->menu_order,
            'module_id' => (int) get_post_meta($post->ID, 'parent_module', true),
            'featured_image' => get_the_post_thumbnail_url($post->ID, 'large') ?: null,
            'comments_open' => $post->comment_status === 'open'
        ];
    }
    
    // ============================================================================
    // ARGUMENT DEFINITIONS
    // ============================================================================
    
    /**
     * Get arguments for courses endpoint
     * 
     * @return array
     */
    private function getCoursesArgs(): array
    {
        return [
            'page' => [
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1
            ],
            'per_page' => [
                'type' => 'integer',
                'default' => 10,
                'minimum' => 1,
                'maximum' => 100
            ],
            'search' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'orderby' => [
                'type' => 'string',
                'enum' => ['date', 'title', 'menu_order'],
                'default' => 'date'
            ],
            'order' => [
                'type' => 'string',
                'enum' => ['ASC', 'DESC'],
                'default' => 'DESC'
            ]
        ];
    }
    
    /**
     * Get arguments for create course endpoint
     * 
     * @return array
     */
    private function getCreateCourseArgs(): array
    {
        return [
            'title' => [
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'status' => [
                'type' => 'string',
                'enum' => ['draft', 'publish'],
                'default' => 'draft'
            ],
            'nonce' => [
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ]
        ];
    }
    
    /**
     * Get arguments for update course endpoint
     * 
     * @return array
     */
    private function getUpdateCourseArgs(): array
    {
        return [
            'title' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'status' => [
                'type' => 'string',
                'enum' => ['draft', 'publish']
            ],
            'nonce' => [
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ]
        ];
    }
    
    /**
     * Get arguments for create module endpoint
     * 
     * @return array
     */
    private function getCreateModuleArgs(): array
    {
        return [
            'title' => [
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'course_id' => [
                'type' => 'integer',
                'required' => true
            ],
            'nonce' => [
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ]
        ];
    }
    
    /**
     * Get arguments for create lesson endpoint
     * 
     * @return array
     */
    private function getCreateLessonArgs(): array
    {
        return [
            'title' => [
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'content' => [
                'type' => 'string',
                'sanitize_callback' => 'wp_kses_post'
            ],
            'module_id' => [
                'type' => 'integer',
                'required' => true
            ],
            'nonce' => [
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ]
        ];
    }
}
