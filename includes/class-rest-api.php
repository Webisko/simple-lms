<?php
/**
 * REST API endpoints for Simple LMS
 * 
 * @package SimpleLMS
 * @since 1.1.0
 */

declare(strict_types=1);

namespace SimpleLMS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API handler class
 * 
 * Provides REST API endpoints for external integrations and frontend applications
 */
class Rest_API {
    
    /**
     * API namespace
     */
    public const NAMESPACE = 'simple-lms/v1';
    
    /**
     * Initialize REST API endpoints
     * 
     * @return void
     */
    public static function init(): void {
        add_action('rest_api_init', [__CLASS__, 'registerEndpoints']);
    }
    
    /**
     * Register all REST API endpoints
     * 
     * @return void
     */
    public static function registerEndpoints(): void {
        // Courses endpoints
        register_rest_route(self::NAMESPACE, '/courses', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'getCourses'],
                'permission_callback' => [__CLASS__, 'checkReadPermission'],
                'args' => self::getCoursesArgs()
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'createCourse'],
                'permission_callback' => [__CLASS__, 'checkCreateCoursePermission'],
                'args' => self::getCreateCourseArgs()
            ]
        ]);
        
        register_rest_route(self::NAMESPACE, '/courses/(?P<id>\d+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'getCourse'],
                'permission_callback' => [__CLASS__, 'checkCourseReadPermission'],
                'args' => ['id' => ['required' => true, 'type' => 'integer']]
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [__CLASS__, 'updateCourse'],
                'permission_callback' => [__CLASS__, 'checkUpdateCoursePermission'],
                'args' => self::getUpdateCourseArgs()
            ]
        ]);
        
        // Modules endpoints
        register_rest_route(self::NAMESPACE, '/courses/(?P<course_id>\d+)/modules', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'getCourseModules'],
                'permission_callback' => [__CLASS__, 'checkCourseReadPermission'],
                'args' => ['course_id' => ['required' => true, 'type' => 'integer']]
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'createModule'],
                'permission_callback' => [__CLASS__, 'checkCreateModulePermission'],
                'args' => self::getCreateModuleArgs()
            ]
        ]);
        
        register_rest_route(self::NAMESPACE, '/modules/(?P<id>\d+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'getModule'],
                'permission_callback' => [__CLASS__, 'checkModuleReadPermission'],
                'args' => ['id' => ['required' => true, 'type' => 'integer']]
            ]
        ]);
        
        // Lessons endpoints
        register_rest_route(self::NAMESPACE, '/modules/(?P<module_id>\d+)/lessons', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'getModuleLessons'],
                'permission_callback' => [__CLASS__, 'checkModuleReadPermission'],
                'args' => ['module_id' => ['required' => true, 'type' => 'integer']]
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [__CLASS__, 'createLesson'],
                'permission_callback' => [__CLASS__, 'checkCreateLessonPermission'],
                'args' => self::getCreateLessonArgs()
            ]
        ]);
        
        register_rest_route(self::NAMESPACE, '/lessons/(?P<id>\d+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [__CLASS__, 'getLesson'],
                'permission_callback' => [__CLASS__, 'checkLessonReadPermission'],
                'args' => ['id' => ['required' => true, 'type' => 'integer']]
            ]
        ]);
        
        // Progress endpoints
        register_rest_route(self::NAMESPACE, '/progress/(?P<user_id>\d+)', [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [__CLASS__, 'getUserProgress'],
            'permission_callback' => [__CLASS__, 'checkProgressPermission'],
            'args' => ['user_id' => ['required' => true, 'type' => 'integer']]
        ]);
        
        register_rest_route(self::NAMESPACE, '/progress/(?P<user_id>\d+)/(?P<lesson_id>\d+)', [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => [__CLASS__, 'updateLessonProgress'],
            'permission_callback' => [__CLASS__, 'checkProgressUpdatePermission'],
            'args' => [
                'user_id' => ['required' => true, 'type' => 'integer'],
                'lesson_id' => ['required' => true, 'type' => 'integer'],
                'completed' => ['type' => 'boolean', 'default' => true]
            ]
        ]);
    }
    
    /**
     * Get courses with filtering and pagination
     * 
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public static function getCourses(\WP_REST_Request $request) {
        try {
            $args = [
                'post_type' => 'course',
                'post_status' => 'publish',
                'posts_per_page' => $request->get_param('per_page') ?: 10,
                'paged' => $request->get_param('page') ?: 1,
                'orderby' => $request->get_param('orderby') ?: 'date',
                'order' => $request->get_param('order') ?: 'DESC'
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
                $courses[] = self::prepareCourseData($post);
            }
            
            $response = rest_ensure_response($courses);
            $response->header('X-WP-Total', (string) $query->found_posts);
            $response->header('X-WP-TotalPages', (string) $query->max_num_pages);
            
            return $response;
            
        } catch (\Exception $e) {
            try {
                $container = ServiceContainer::getInstance();
                /** @var Logger $logger */
                $logger = $container->get(Logger::class);
                $logger->error('REST getCourses failed: {error}', ['error' => $e]);
            } catch (\Throwable $t) {
                // Fallback to error_log
                error_log('Simple LMS REST API Error (getCourses): ' . $e->getMessage());
            }
            return new \WP_Error('api_error', __('Błąd podczas pobierania kursów', 'simple-lms'), ['status' => 500]);
        }
    }
    
    /**
     * Get single course with modules and lessons
     * 
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public static function getCourse(\WP_REST_Request $request) {
        try {
            $courseId = (int) $request->get_param('id');
            $post = get_post($courseId);
            
            if (!$post || $post->post_type !== 'course') {
                return new \WP_Error('course_not_found', __('Kurs nie znaleziony', 'simple-lms'), ['status' => 404]);
            }
            
            $courseData = self::prepareCourseData($post, true);
            return rest_ensure_response($courseData);
            
        } catch (\Exception $e) {
            try {
                $container = ServiceContainer::getInstance();
                /** @var Logger $logger */
                $logger = $container->get(Logger::class);
                $logger->error('REST getCourse failed: {error}', ['error' => $e, 'courseId' => $request->get_param('id')]);
            } catch (\Throwable $t) {
                error_log('Simple LMS REST API Error (getCourse): ' . $e->getMessage());
            }
            return new \WP_Error('api_error', __('Błąd podczas pobierania kursu', 'simple-lms'), ['status' => 500]);
        }
    }
    
    /**
     * Get course modules
     * 
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public static function getCourseModules(\WP_REST_Request $request) {
        try {
            $courseId = (int) $request->get_param('course_id');
            $modules = Cache_Handler::getCourseModules($courseId);
            
            $moduleData = array_map([__CLASS__, 'prepareModuleData'], $modules);
            return rest_ensure_response($moduleData);
            
        } catch (\Exception $e) {
            try {
                $container = ServiceContainer::getInstance();
                /** @var Logger $logger */
                $logger = $container->get(Logger::class);
                $logger->error('REST getCourseModules failed: {error}', ['error' => $e, 'courseId' => $request->get_param('course_id')]);
            } catch (\Throwable $t) {
                error_log('Simple LMS REST API Error (getCourseModules): ' . $e->getMessage());
            }
            return new \WP_Error('api_error', __('Błąd podczas pobierania modułów', 'simple-lms'), ['status' => 500]);
        }
    }
    
    /**
     * Get module lessons
     * 
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public static function getModuleLessons(\WP_REST_Request $request) {
        try {
            $moduleId = (int) $request->get_param('module_id');
            $lessons = Cache_Handler::getModuleLessons($moduleId);
            
            $lessonData = array_map([__CLASS__, 'prepareLessonData'], $lessons);
            return rest_ensure_response($lessonData);
            
        } catch (\Exception $e) {
            try {
                $container = ServiceContainer::getInstance();
                /** @var Logger $logger */
                $logger = $container->get(Logger::class);
                $logger->error('REST getModuleLessons failed: {error}', ['error' => $e, 'moduleId' => $request->get_param('module_id')]);
            } catch (\Throwable $t) {
                error_log('Simple LMS REST API Error (getModuleLessons): ' . $e->getMessage());
            }
            return new \WP_Error('api_error', __('Błąd podczas pobierania lekcji', 'simple-lms'), ['status' => 500]);
        }
    }
    
    /**
     * Get user progress across all courses
     * 
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public static function getUserProgress(\WP_REST_Request $request) {
        try {
            $userId = (int) $request->get_param('user_id');
            $progress = Progress_Tracker::getUserProgress($userId);
            
            return rest_ensure_response($progress);
            
        } catch (\Exception $e) {
            error_log('Simple LMS REST API Error (getUserProgress): ' . $e->getMessage());
            return new \WP_Error('api_error', __('Błąd podczas pobierania postępów', 'simple-lms'), ['status' => 500]);
        }
    }
    
    /**
     * Update lesson progress for user
     * 
     * @param \WP_REST_Request $request Request object
     * @return \WP_REST_Response|\WP_Error
     */
    public static function updateLessonProgress(\WP_REST_Request $request) {
        try {
            $userId = (int) $request->get_param('user_id');
            $lessonId = (int) $request->get_param('lesson_id');
            $completed = (bool) $request->get_param('completed');
            
            $result = Progress_Tracker::updateLessonProgress($userId, $lessonId, $completed);
            
            if ($result) {
                return rest_ensure_response(['success' => true, 'message' => __('Postęp zaktualizowany', 'simple-lms')]);
            } else {
                return new \WP_Error('update_failed', __('Nie udało się zaktualizować postępu', 'simple-lms'), ['status' => 500]);
            }
            
        } catch (\Exception $e) {
            error_log('Simple LMS REST API Error (updateLessonProgress): ' . $e->getMessage());
            return new \WP_Error('api_error', __('Błąd podczas aktualizacji postępu', 'simple-lms'), ['status' => 500]);
        }
    }
    
    /**
     * Prepare course data for API response
     * 
     * @param \WP_Post $post Course post object
     * @param bool $includeModules Whether to include modules data
     * @return array Formatted course data
     */
    private static function prepareCourseData(\WP_Post $post, bool $includeModules = false): array {
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
                // course_roles removed – tag-based access via user_meta
                'allow_comments' => (bool) get_post_meta($post->ID, 'allow_comments', true),
                'user_has_access' => $hasAccess,
            ]
        ];
        
        if ($includeModules) {
            $modules = Cache_Handler::getCourseModules($post->ID);
            $data['modules'] = array_map([__CLASS__, 'prepareModuleData'], $modules);
            $data['stats'] = Cache_Handler::getCourseStats($post->ID);
        }
        
        return $data;
    }
    
    /**
     * Prepare module data for API response
     * 
     * @param \WP_Post $post Module post object
     * @return array Formatted module data
     */
    private static function prepareModuleData(\WP_Post $post): array {
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'status' => $post->post_status,
            'order' => (int) $post->menu_order,
            'course_id' => (int) get_post_meta($post->ID, 'parent_course', true),
            'lesson_count' => count(Cache_Handler::getModuleLessons($post->ID))
        ];
    }
    
    /**
     * Prepare lesson data for API response
     * 
     * @param \WP_Post $post Lesson post object
     * @return array Formatted lesson data
     */
    private static function prepareLessonData(\WP_Post $post): array {
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
    
    /**
     * Check read permission for public endpoints
     * 
     * @return bool
     */
    public static function checkReadPermission(): bool {
        return true; // Public read access
    }
    
    /**
     * Check edit permission
     * 
     * @return bool
     */
    public static function checkEditPermission(): bool {
        return current_user_can('edit_posts');
    }

    /**
     * Create course permission (nonce + capability)
     */
    public static function checkCreateCoursePermission(\WP_REST_Request $request): bool {
        return current_user_can('edit_posts') && self::verifyRequestNonce($request);
    }

    /**
     * Update course permission (nonce + capability)
     */
    public static function checkUpdateCoursePermission(\WP_REST_Request $request): bool {
        $courseId = (int) $request->get_param('id');
        return current_user_can('edit_post', $courseId) && self::verifyRequestNonce($request);
    }
    
    /**
     * Check course-specific read permission
     * 
     * @param \WP_REST_Request $request Request object
     * @return bool
     */
    public static function checkCourseReadPermission(\WP_REST_Request $request): bool {
        $courseId = (int) $request->get_param('id') ?: (int) $request->get_param('course_id');
        return self::userCanAccessCourse($courseId);
    }
    
    /**
     * Check course-specific edit permission
     * 
     * @param \WP_REST_Request $request Request object
     * @return bool
     */
    public static function checkCourseEditPermission(\WP_REST_Request $request): bool {
        $courseId = (int) $request->get_param('id') ?: (int) $request->get_param('course_id');
        return current_user_can('edit_post', $courseId) && self::verifyRequestNonce($request);
    }
    
    /**
     * Check if user can access course
     * 
     * @param int $courseId Course ID
     * @return bool
     */
    private static function userCanAccessCourse(int $courseId): bool {
        if (current_user_can('edit_posts')) {
            return true; // Admins retain universal access
        }
        // Tag-based access check
        $access = (array) get_user_meta(get_current_user_id(), 'simple_lms_course_access', true);
        return in_array($courseId, $access, true);
    }
    
    /**
     * Get arguments for courses endpoint
     * 
     * @return array
     */
    private static function getCoursesArgs(): array {
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
    private static function getCreateCourseArgs(): array {
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
    private static function getUpdateCourseArgs(): array {
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
     * Additional permission check methods for modules and lessons...
     */
    public static function checkModuleReadPermission(\WP_REST_Request $request): bool {
        $moduleId = (int) $request->get_param('id') ?: (int) $request->get_param('module_id');
        $courseId = (int) get_post_meta($moduleId, 'parent_course', true);
        return self::userCanAccessCourse($courseId);
    }
    
    public static function checkModuleEditPermission(\WP_REST_Request $request): bool {
        $moduleId = (int) $request->get_param('id') ?: (int) $request->get_param('module_id');
        return current_user_can('edit_post', $moduleId) && self::verifyRequestNonce($request);
    }
    
    public static function checkLessonReadPermission(\WP_REST_Request $request): bool {
        $lessonId = (int) $request->get_param('id');
        $moduleId = (int) get_post_meta($lessonId, 'parent_module', true);
        $courseId = (int) get_post_meta($moduleId, 'parent_course', true);
        return self::userCanAccessCourse($courseId);
    }
    
    public static function checkProgressPermission(\WP_REST_Request $request): bool {
        $userId = (int) $request->get_param('user_id');
        return current_user_can('edit_users') || get_current_user_id() === $userId;
    }
    
    public static function checkProgressUpdatePermission(\WP_REST_Request $request): bool {
        $userId = (int) $request->get_param('user_id');
        return (get_current_user_id() === $userId || current_user_can('edit_users')) && self::verifyRequestNonce($request);
    }

    /**
     * Create module permission (nonce + capability on parent course)
     */
    public static function checkCreateModulePermission(\WP_REST_Request $request): bool {
        $courseId = (int) $request->get_param('course_id');
        return current_user_can('edit_post', $courseId) && self::verifyRequestNonce($request);
    }

    /**
     * Create lesson permission (nonce + capability on parent module)
     */
    public static function checkCreateLessonPermission(\WP_REST_Request $request): bool {
        $moduleId = (int) $request->get_param('module_id');
        return current_user_can('edit_post', $moduleId) && self::verifyRequestNonce($request);
    }

    /**
     * Verify nonce helper (expects 'nonce' param)
     */
    private static function verifyRequestNonce(\WP_REST_Request $request): bool {
        $nonce = $request->get_param('nonce');
        if (!$nonce) { return false; }
        // Prefer Security_Service if available
        try {
            $container = ServiceContainer::getInstance();
            if ($container->has(Security_Service::class)) {
                /** @var Security_Service $sec */
                $sec = $container->get(Security_Service::class);
                return $sec->verifyNonce((string)$nonce, 'rest');
            }
        } catch (\Throwable $e) {
            // fall back
        }
        return (bool) wp_verify_nonce((string) $nonce, apply_filters('simple_lms_rest_nonce_action', 'simple_lms_rest'));
    }
    
    /**
     * Additional argument definitions for other endpoints...
     */
    private static function getCreateModuleArgs(): array {
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
    
    private static function getCreateLessonArgs(): array {
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

    /**
     * Create course endpoint handler
     */
    public static function createCourse(\WP_REST_Request $request) {
        if (!self::verifyRequestNonce($request)) {
            return new \WP_Error('invalid_nonce', __('Nieprawidłowy nonce', 'simple-lms'), ['status' => 403]);
        }
        $title = sanitize_text_field((string) $request->get_param('title'));
        $status = (string) $request->get_param('status');
        $status = in_array($status, ['draft','publish'], true) ? $status : 'draft';
        $courseId = wp_insert_post([
            'post_type' => 'course',
            'post_status' => $status,
            'post_title' => $title,
        ]);
        if (is_wp_error($courseId) || $courseId <= 0) {
            return new \WP_Error('create_failed', __('Nie udało się utworzyć kursu', 'simple-lms'), ['status' => 500]);
        }
        return rest_ensure_response(['id' => $courseId]);
    }

    /**
     * Update course endpoint handler
     */
    public static function updateCourse(\WP_REST_Request $request) {
        if (!self::verifyRequestNonce($request)) {
            return new \WP_Error('invalid_nonce', __('Nieprawidłowy nonce', 'simple-lms'), ['status' => 403]);
        }
        $courseId = (int) $request->get_param('id');
        if ($courseId <= 0 || get_post_type($courseId) !== 'course') {
            return new \WP_Error('invalid_course', __('Nieprawidłowy kurs', 'simple-lms'), ['status' => 404]);
        }
        $update = ['ID' => $courseId];
        if ($title = $request->get_param('title')) {
            $update['post_title'] = sanitize_text_field((string) $title);
        }
        if ($status = $request->get_param('status')) {
            if (in_array($status, ['draft','publish'], true)) {
                $update['post_status'] = (string) $status;
            }
        }
        $result = wp_update_post($update, true);
        if (is_wp_error($result)) {
            return new \WP_Error('update_failed', __('Aktualizacja kursu nie powiodła się', 'simple-lms'), ['status' => 500]);
        }
        return rest_ensure_response(['id' => $courseId, 'updated' => true]);
    }

    /**
     * Create module endpoint handler
     */
    public static function createModule(\WP_REST_Request $request) {
        if (!self::verifyRequestNonce($request)) {
            return new \WP_Error('invalid_nonce', __('Nieprawidłowy nonce', 'simple-lms'), ['status' => 403]);
        }
        $courseId = (int) $request->get_param('course_id');
        if ($courseId <= 0 || get_post_type($courseId) !== 'course') {
            return new \WP_Error('invalid_course', __('Nieprawidłowy kurs', 'simple-lms'), ['status' => 404]);
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
            return new \WP_Error('create_failed', __('Nie udało się utworzyć modułu', 'simple-lms'), ['status' => 500]);
        }
        return rest_ensure_response(['id' => $moduleId]);
    }

    /**
     * Create lesson endpoint handler
     */
    public static function createLesson(\WP_REST_Request $request) {
        if (!self::verifyRequestNonce($request)) {
            return new \WP_Error('invalid_nonce', __('Nieprawidłowy nonce', 'simple-lms'), ['status' => 403]);
        }
        $moduleId = (int) $request->get_param('module_id');
        if ($moduleId <= 0 || get_post_type($moduleId) !== 'module') {
            return new \WP_Error('invalid_module', __('Nieprawidłowy moduł', 'simple-lms'), ['status' => 404]);
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
            return new \WP_Error('create_failed', __('Nie udało się utworzyć lekcji', 'simple-lms'), ['status' => 500]);
        }
        return rest_ensure_response(['id' => $lessonId]);
    }
}

// Rest_API is initialized via Plugin::initLegacyServices()
