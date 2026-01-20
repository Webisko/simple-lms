<?php
declare(strict_types=1);

namespace SimpleLMS;

/**
 * AJAX request handlers for Simple LMS
 * 
 * @package SimpleLMS
 * @since 1.0.1
 */

// Import WordPress functions
use function wp_verify_nonce;
use function wp_send_json_error;
use function wp_send_json_success;
use function get_current_user_id;
use function get_post;
use function get_transient;
use function get_user_meta;
use function set_transient;
use function update_user_meta;
use function current_time;
use const MINUTE_IN_SECONDS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX request handler class
 * 
 * Handles all AJAX operations for the LMS functionality
 */
class Ajax_Handler {

    /** @var Logger|null */
    private static ?Logger $logger = null;
    /** @var Security_Service|null */
    private static ?Security_Service $security = null;

    /**
     * Constructor with optional dependencies
     */
    public function __construct(?Logger $logger = null, ?Security_Service $security = null)
    {
        self::$logger = $logger;
        self::$security = $security;
    }
    
    /**
     * Valid AJAX actions
     * 
     * @var array
     */
    private const VALID_ACTIONS = [
        'add_new_module',
        'add_new_lesson_from_module',
        'duplicate_lesson',
        'delete_lesson',
        'duplicate_module',
        'delete_module',
        'save_course_settings',
        'update_lesson_status',
        'update_module_status',
        'bulk_update_tags',
        'simple_lms_complete_lesson',
        'simple_lms_uncomplete_lesson'
    ];

    /**
     * Validate common AJAX request requirements
     * 
     * @param string $capability Required user capability
     * @param bool $check_nonce Verify nonce (default: true)
     * @param bool $check_login Verify user logged in (default: true)
     * @return array|null Error array if validation fails, null if passes
     */
    private static function validateCommonAjaxChecks(
        string $capability = 'edit_posts',
        bool $check_nonce = true,
        bool $check_login = true
    ): ?array {
        if ($check_nonce && !check_ajax_referer('simple_lms_ajax_nonce', 'nonce', false)) {
            return ['message' => __('Invalid security token', 'simple-lms')];
        }
        
        if ($check_login && !is_user_logged_in()) {
            return ['message' => __('You must be logged in', 'simple-lms')];
        }
        
        if ($capability && !current_user_can($capability)) {
            return ['message' => __('No permission to perform this operation', 'simple-lms')];
        }
        
        return null;
    }

    /**
     * Validate post ID and type
     * 
     * @param int $post_id Post ID
     * @param string $expected_type Expected post type
     * @return bool
     */
    private static function validatePostType(int $post_id, string $expected_type): bool {
        if ($post_id <= 0) return false;
        $post = get_post($post_id);
        return $post && $post->post_type === $expected_type;
    }

    /**
     * Instance registration of AJAX hooks
     */
    public function register(): void
    {
        foreach (self::VALID_ACTIONS as $action) {
            add_action("wp_ajax_{$action}", [__CLASS__, 'handleAjaxRequest']);
            // Also allow for logged-out users for completion tracking
            if (in_array($action, ['simple_lms_complete_lesson', 'simple_lms_uncomplete_lesson'])) {
                add_action("wp_ajax_nopriv_{$action}", [__CLASS__, 'handleAjaxRequest']);
            }
        }

        // Update module order
        add_action('wp_ajax_update_modules_order', [__CLASS__, 'handleUpdateModulesOrder']);
        add_action('wp_ajax_update_lessons_order', [__CLASS__, 'handleUpdateLessonsOrder']);
        
        // Elementor editor preview
        add_action('wp_ajax_simple_lms_get_course_preview', [__CLASS__, 'handleGetCoursePreview']);

        if (self::$logger) {
            self::$logger->debug('Ajax_Handler hooks registered');
        }
    }

    /**
     * Initialize the handler (backward compatibility shim)
     * 
     * @return void
     */
    public static function init(): void {
        try {
            $container = ServiceContainer::getInstance();
            if ($container->has(self::class)) {
                $instance = $container->get(self::class);
                if (method_exists($instance, 'register')) {
                    $instance->register();
                    return;
                }
            }
        } catch (\Throwable $e) {
            // fall through
        }
        (new self())->register();
    }

    /**
     * Handle module order updates
     * 
     * @return void
     */
    public static function handleUpdateModulesOrder(): void {
        try {
            self::verifyAjaxRequest();

            $courseId = self::getPostInt('course_id');
            $moduleOrder = self::getPostArray('module_order', 'int');

            if (!$courseId || empty($moduleOrder)) {
                throw new \InvalidArgumentException(__('Invalid data.', 'simple-lms'));
            }

            foreach ($moduleOrder as $position => $moduleId) {
                wp_update_post([
                    'ID' => $moduleId,
                    'menu_order' => $position
                ]);
            }

            wp_send_json_success();
        } catch (\Exception $e) {
            self::logError('update_modules_order', $e);
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle lesson order updates
     * 
     * @return void
     */
    public static function handleUpdateLessonsOrder(): void {
        try {
            self::verifyAjaxRequest();

            $moduleId = self::getPostInt('module_id');
            $lessonOrder = self::getPostArray('lesson_order', 'int');

            if (!$moduleId || empty($lessonOrder)) {
                throw new \InvalidArgumentException(__('Invalid data.', 'simple-lms'));
            }

            foreach ($lessonOrder as $position => $lessonId) {
                wp_update_post([
                    'ID' => $lessonId,
                    'menu_order' => $position
                ]);
                
                // Update parent module if the lesson was moved
                update_post_meta($lessonId, 'parent_module', $moduleId);
            }

            wp_send_json_success();
        } catch (\Exception $e) {
            self::logError('update_lessons_order', $e);
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Handle all AJAX requests
     * 
     * @return void
     */
    public static function handleAjaxRequest(): void {
        // Clean output buffers to ensure clean JSON
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        try {
            $action = self::getPostString('action');

            
            if (!$action || !in_array($action, self::VALID_ACTIONS)) {
                try {
                    $container = ServiceContainer::getInstance();
                    /** @var Logger $logger */
                    $logger = $container->get(Logger::class);
                    $logger->warning('AJAX invalid action: {action}', ['action' => $action ?: 'NONE']);
                } catch (\Throwable $t) {
                    error_log('Simple LMS AJAX: Invalid action: ' . ($action ?: 'NONE'));
                }
                throw new \InvalidArgumentException(__('Invalid action', 'simple-lms'));
            }

            // Skip verifyAjaxRequest for lesson completion handlers (they have their own verification)
            if (!in_array($action, ['simple_lms_complete_lesson', 'simple_lms_uncomplete_lesson'])) {
                self::verifyAjaxRequest();
            }

            switch ($action) {
                case 'add_new_module':
                    self::addNewModule();
                    break;
                case 'add_new_lesson_from_module':
                    self::add_new_lesson($_POST);
                    break;
                case 'duplicate_lesson':
                    self::duplicate_lesson($_POST);
                    break;
                case 'delete_lesson':
                    self::delete_lesson($_POST);
                    break;
                case 'duplicate_module':
                    self::duplicate_module($_POST);
                    break;
                case 'delete_module':
                    self::delete_module($_POST);
                    break;
                case 'save_course_settings':
                    self::save_course_settings($_POST);
                    break;
                case 'update_lesson_status':
                    self::update_lesson_status($_POST);
                    break;
                case 'update_module_status':
                    self::update_module_status($_POST);
                    break;
                case 'bulk_update_tags':
                    self::bulk_update_tags();
                    break;
                case 'simple_lms_complete_lesson':
                    self::completeLessonHandler($_POST);
                    break;
                case 'simple_lms_uncomplete_lesson':
                    self::uncompleteLessonHandler($_POST);
                    break;
                default:
                    throw new \InvalidArgumentException(__('Unknown action', 'simple-lms'));
            }
        } catch (\Exception $e) {
            error_log('SimpleLMS AJAX ERROR in action ' . ($action ?? 'unknown_action') . ': ' . $e->getMessage());
            error_log('SimpleLMS AJAX ERROR trace: ' . $e->getTraceAsString());
            self::logError($action ?? 'unknown_action', $e);
            
            // Send JSON error directly to ensure it's sent
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Add new module
     * 
     * @return void
     */
    private static function addNewModule(): void {
        $courseId = self::getPostInt('course_id');
        $moduleTitle = self::getPostString('module_title');

        if (!$courseId || !$moduleTitle) {
            throw new \InvalidArgumentException(__('Missing required fields', 'simple-lms'));
        }

        // Validate course exists and is correct post type
        if (!self::validatePostType($courseId, 'course')) {
            throw new \InvalidArgumentException(__('Invalid course', 'simple-lms'));
        }

        if (!self::userHasAccessToCourse($courseId)) {
            throw new \InvalidArgumentException(__('You do not have access to this course', 'simple-lms'));
        }

        // Get the highest menu_order for existing modules using optimized query
        $highestOrder = self::getHighestMenuOrder('module', 'parent_course', $courseId);

        $moduleData = [
            'post_title'  => sanitize_text_field($moduleTitle),
            'post_status' => 'draft',
            'post_type'   => 'module',
            'menu_order'  => $highestOrder + 1
        ];

        $moduleId = wp_insert_post($moduleData);
        if (is_wp_error($moduleId)) {
            throw new \RuntimeException($moduleId->get_error_message());
        }

        update_post_meta($moduleId, 'parent_course', $courseId);
        
        // Auto-tag module with course name
        self::autoTagModule($moduleId);
        
        wp_send_json_success(['module_id' => $moduleId]);
    }

    /**
     * Verify AJAX request security
     * 
     * @return void
     * @throws \Exception
     */
    private static function verifyAjaxRequest(): void {
        // Determine capability based on action (fine-grained control)
        $action = isset($_POST['action']) ? sanitize_key((string) $_POST['action']) : '';
        $capMap = [
            'add_new_module' => 'edit_posts',
            'add_new_lesson_from_module' => 'edit_posts',
            'duplicate_lesson' => 'edit_posts',
            'delete_lesson' => 'delete_posts',
            'duplicate_module' => 'edit_posts',
            'delete_module' => 'delete_posts',
            'save_course_settings' => 'manage_options',
            'update_lesson_status' => 'edit_posts',
            'update_module_status' => 'edit_posts',
            'bulk_update_tags' => 'manage_categories'
        ];
        $requiredCap = $capMap[$action] ?? 'edit_posts';

        // Accept both 'nonce' and legacy 'security'
        $nonce = $_POST['nonce'] ?? $_POST['security'] ?? '';
        
        error_log('SimpleLMS AJAX verifyAjaxRequest: action=' . $action . ', nonce=' . substr((string)$nonce, 0, 10) . '..., has_nonce=' . (!empty($nonce) ? 'yes' : 'no'));
        
        // Check logged in
        if (!is_user_logged_in()) {
            error_log('SimpleLMS AJAX: User not logged in');
            throw new \Exception(__('You must be logged in', 'simple-lms'));
        }

        // Check capability
        if ($requiredCap && !current_user_can($requiredCap)) {
            error_log('SimpleLMS AJAX: User does not have capability: ' . $requiredCap);
            throw new \Exception(__('Insufficient permissions', 'simple-lms'));
        }
        
        error_log('SimpleLMS AJAX verifyAjaxRequest: PASS - user logged in and has capability');
    }

    /**
     * Get POST parameter as integer
     * 
     * @param string $key Parameter key
     * @return int
     */
    private static function getPostInt(string $key): int {
        return isset($_POST[$key]) ? absint($_POST[$key]) : 0;
    }

    /**
     * Get POST parameter as string
     * 
     * @param string $key Parameter key
     * @return string
     */
    private static function getPostString(string $key): string {
        return isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : '';
    }

    /**
     * Get POST parameter as array
     * 
     * @param string $key Parameter key
     * @param string $type Type of array elements ('int' or 'string')
     * @return array
     */
    private static function getPostArray(string $key, string $type = 'string'): array {
        if (!isset($_POST[$key]) || !is_array($_POST[$key])) {
            return [];
        }

        return $type === 'int' 
            ? array_map('intval', $_POST[$key])
            : array_map('sanitize_text_field', $_POST[$key]);
    }

    /**
     * Log error with context
     * 
     * @param string $action Action that caused the error
     * @param \Exception $exception Exception object
     * @return void
     */
    private static function logError(string $action, \Exception $exception): void {
        try {
            $container = ServiceContainer::getInstance();
            /** @var Logger $logger */
            $logger = $container->get(Logger::class);
            $logger->error('AJAX error [{action}]: {error}', ['action' => $action, 'error' => $exception]);
        } catch (\Throwable $t) {
            error_log(sprintf(
                'Simple LMS AJAX Error [%s]: %s in %s:%d',
                $action,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));
        }
    }

    /**
     * Get highest menu order for post type with meta query
     * 
     * @param string $postType Post type
     * @param string $metaKey Meta key
     * @param int $metaValue Meta value
     * @return int
     */
    private static function getHighestMenuOrder(string $postType, string $metaKey, int $metaValue): int {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(p.menu_order) 
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = %s 
             AND pm.meta_key = %s 
             AND pm.meta_value = %d",
            $postType,
            $metaKey,
            $metaValue
        ));
        
        return (int) $result;
    }

    /**
     * Check if current user has admin access to edit course
     * 
     * @param int $courseId Course ID
     * @return bool
     */
    private static function userHasAccessToCourse(int $courseId): bool {
        return current_user_can('edit_post', $courseId);
    }

    /**
     * Add new lesson
     */
    private static function add_new_lesson($data) {
        $module_id = absint($data['module_id'] ?? 0);
        $lesson_title = sanitize_text_field($data['lesson_title'] ?? '');

        if (!$module_id || !$lesson_title) {
            throw new \Exception(__('Missing required fields', 'simple-lms'));
        }

        // Validate module exists and is correct post type
        if (!self::validatePostType($module_id, 'module')) {
            throw new \Exception(__('Invalid module', 'simple-lms'));
        }

        $course_id = get_post_meta($module_id, 'parent_course', true);
        $course_id = absint($course_id);
        if (!$course_id || !self::userHasAccessToCourse($course_id)) {
            throw new \Exception(__('You do not have access to this course', 'simple-lms'));
        }

        // Get the highest menu_order for existing lessons in this module using optimized query
        $highest_order = self::getHighestMenuOrder('lesson', 'parent_module', $module_id);

        $lesson_data = array(
            'post_title'  => $lesson_title,
            'post_status' => 'draft',
            'post_type'   => 'lesson',
            'menu_order'  => $highest_order + 1
        );

        $lesson_id = wp_insert_post($lesson_data);
        if (is_wp_error($lesson_id)) {
            throw new \Exception($lesson_id->get_error_message());
        }

        update_post_meta($lesson_id, 'parent_module', $module_id);
        
        // Auto-tag lesson with course and module names
        self::autoTagLesson($lesson_id);
        
        wp_send_json_success(['lesson_id' => $lesson_id]);
    }

    /**
     * Duplicate lesson
     */
    private static function duplicate_lesson($data) {
        $lesson_id = absint($data['lesson_id'] ?? 0);
        if (!$lesson_id) {
            throw new \Exception(__('Invalid lesson ID', 'simple-lms'));
        }

        $lesson = get_post($lesson_id);
        if (!$lesson || $lesson->post_type !== 'lesson') {
            throw new \Exception(__('Lekcja nie znaleziona', 'simple-lms'));
        }

        // Check user permission - needs publish capability for duplication
        if ($error = self::validateCommonAjaxChecks('publish_posts', false, false)) {
            throw new \Exception($error['message']);
        }

        // Check user permission for the original lesson's course
        $parent_module_id = absint(get_post_meta($lesson_id, 'parent_module', true));
        if ($parent_module_id) {
            $course_id = absint(get_post_meta($parent_module_id, 'parent_course', true));
            if (!$course_id || !self::userHasAccessToCourse($course_id)) {
                throw new \Exception(__('You do not have permission to duplicate lessons in this course.', 'simple-lms'));
            }
        } else {
            // If lesson has no parent module, it's an orphaned lesson, prevent duplication or handle as error
            throw new \Exception(__('Cannot duplicate lesson without parent module.', 'simple-lms'));
        }

        // Get the highest menu_order for existing lessons in this module using optimized query
        $highest_order = self::getHighestMenuOrder('lesson', 'parent_module', $parent_module_id);

        $new_lesson_data = array(
            'post_title'   => $lesson->post_title . ' ' . __('(Kopia)', 'simple-lms'),
            'post_content' => $lesson->post_content,
            'post_excerpt' => $lesson->post_excerpt,
            'post_status'  => 'draft',
            'post_type'    => 'lesson',
            'menu_order'   => $highest_order + 1
        );

        $new_lesson_id = wp_insert_post($new_lesson_data);
        if (is_wp_error($new_lesson_id)) {
            throw new \Exception($new_lesson_id->get_error_message());
        }

        // Copy meta data
        update_post_meta($new_lesson_id, 'parent_module', $parent_module_id); // Use $parent_module_id

        $meta_keys = get_post_custom_keys($lesson_id);
        if ($meta_keys) {
            foreach ($meta_keys as $key) {
                if ($key !== 'parent_module') {
                    $values = get_post_custom_values($key, $lesson_id);
                    foreach ($values as $value) {
                        add_post_meta($new_lesson_id, $key, maybe_unserialize($value));
                    }
                }
            }
        }

        wp_send_json_success(['lesson_id' => $new_lesson_id]);
    }

    /**
     * Delete lesson
     */
    private static function delete_lesson($data) {
        error_log('[SimpleLMS AJAX] delete_lesson called');
        
        $lesson_id = absint($data['lesson_id'] ?? 0);
        error_log('[SimpleLMS AJAX] lesson_id: ' . $lesson_id);
        
        if (!$lesson_id) {
            error_log('[SimpleLMS AJAX] Invalid lesson ID');
            throw new \Exception(__('Invalid lesson ID', 'simple-lms'));
        }

        // Verify post type before capability check
        if (!self::validatePostType($lesson_id, 'lesson')) {
            error_log('[SimpleLMS AJAX] Invalid post type for lesson_id: ' . $lesson_id);
            throw new \Exception(__('Invalid lesson', 'simple-lms'));
        }

        if (!current_user_can('delete_post', $lesson_id)) {
            error_log('[SimpleLMS AJAX] User cannot delete lesson_id: ' . $lesson_id);
            throw new \Exception(__('You do not have permission to delete this lesson', 'simple-lms'));
        }

        $module_id = get_post_meta($lesson_id, 'parent_module', true);
        error_log('[SimpleLMS AJAX] module_id: ' . $module_id);

        // Delete the post permanently (force_delete = true)
        $delete_result = wp_delete_post($lesson_id, true);
        error_log('[SimpleLMS AJAX] wp_delete_post result: ' . var_export($delete_result, true));
        
        // Verify the post is actually deleted
        $deleted_post = get_post($lesson_id);
        error_log('[SimpleLMS AJAX] get_post after delete: ' . var_export($deleted_post, true));
        
        if ($deleted_post !== null) {
            error_log('[SimpleLMS AJAX] Post still exists after delete!');
            throw new \Exception(__('Failed to delete lesson', 'simple-lms'));
        }

        error_log('[SimpleLMS AJAX] About to send json success');
        wp_send_json_success([
            'success' => true,
            'module_id' => $module_id
        ]);
    }

    /**
     * Duplicate module
     */
    private static function duplicate_module($data) {
        $module_id = absint($data['module_id'] ?? 0);
        if (!$module_id) {
            throw new \Exception(__('Invalid module ID', 'simple-lms'));
        }

        $module = get_post($module_id);
        if (!$module || $module->post_type !== 'module') {
            throw new \Exception(__('Module nie znaleziony', 'simple-lms'));
        }

        // Check user permission - needs publish capability for duplication
        if ($error = self::validateCommonAjaxChecks('publish_posts', false, false)) {
            throw new \Exception($error['message']);
        }

        // Check user permission for the original module's course
        $parent_course_id = get_post_meta($module_id, 'parent_course', true);
        $parent_course_id = absint($parent_course_id);
        if (!$parent_course_id || !self::userHasAccessToCourse($parent_course_id)) {
            throw new \Exception(__('You do not have permission to duplicate modules in this course.', 'simple-lms'));
        }
        
        // Get the highest menu_order for existing modules using optimized query
        $highest_order = self::getHighestMenuOrder('module', 'parent_course', $parent_course_id);
        
        // Create new module
        $new_module_data = array(
            'post_title'   => $module->post_title . ' ' . __('(Kopia)', 'simple-lms'),
            'post_content' => $module->post_content,
            'post_excerpt' => $module->post_excerpt,
            'post_status'  => 'draft',
            'post_type'    => 'module',
            'menu_order'   => $highest_order + 1
        );

        $new_module_id = wp_insert_post($new_module_data);
        if (is_wp_error($new_module_id)) {
            throw new \Exception($new_module_id->get_error_message());
        }

        update_post_meta($new_module_id, 'parent_course', $parent_course_id); // Use $parent_course_id

        // Copy module meta
        self::copy_post_meta($module_id, $new_module_id, ['parent_course']);
        
        // Copy featured image if exists
        $thumbnail_id = get_post_thumbnail_id($module_id);
        if ($thumbnail_id) {
            set_post_thumbnail($new_module_id, $thumbnail_id);
        }
        
        // Auto-tag duplicated module with course name
        self::autoTagModule($new_module_id);

        // Copy associated lessons with proper ordering (include all statuses)
        $lessons = get_posts([
            'post_type'      => 'lesson',
            'posts_per_page' => -1,
            'meta_key'       => 'parent_module',
            'meta_value'     => $module_id,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'post_status'    => ['publish', 'draft', 'private', 'pending']
        ]);

        foreach ($lessons as $lesson) {
            $new_lesson_data = array(
                'post_title'   => ($lesson instanceof \WP_Post ? $lesson->post_title : '') . ' ' . __('(Kopia)', 'simple-lms'),
                'post_content' => $lesson instanceof \WP_Post ? $lesson->post_content : '',
                'post_excerpt' => $lesson instanceof \WP_Post ? $lesson->post_excerpt : '',
                'post_status'  => 'draft',
                'post_type'    => 'lesson',
                'menu_order'   => $lesson instanceof \WP_Post ? $lesson->menu_order : 0
            );

            $new_lesson_id = wp_insert_post($new_lesson_data);
            if (!is_wp_error($new_lesson_id)) {
                // Set parent module first
                update_post_meta($new_lesson_id, 'parent_module', $new_module_id);
                
                // Copy all other meta fields (excluding parent_module to avoid overwriting)
                self::copy_post_meta($lesson->ID, $new_lesson_id, ['parent_module']);
                
                // Copy featured image if exists
                $thumbnail_id = get_post_thumbnail_id($lesson->ID);
                if ($thumbnail_id) {
                    set_post_thumbnail($new_lesson_id, $thumbnail_id);
                }
                
                // Auto-tag duplicated lesson with course and module names
                self::autoTagLesson($new_lesson_id);
            }
        }

        wp_send_json_success(['module_id' => $new_module_id]);
    }

    /**
     * Delete module
     */
    private static function delete_module($data) {
        $module_id = absint($data['module_id'] ?? 0);
        if (!$module_id) {
            throw new \Exception(__('Invalid module ID', 'simple-lms'));
        }

        // Verify post type before capability check
        if (!self::validatePostType($module_id, 'module')) {
            throw new \Exception(__('Invalid module', 'simple-lms'));
        }

        if (!current_user_can('delete_post', $module_id)) {
            throw new \Exception(__('You do not have permission to delete this module', 'simple-lms'));
        }
        
        // Delete associated lessons (verify user can delete each one)
        $lessons = get_posts([
            'post_type'      => 'lesson',
            'posts_per_page' => -1,
            'meta_key'       => 'parent_module',
            'meta_value'     => $module_id
        ]);

        foreach ($lessons as $lesson) {
            if (!$lesson instanceof \WP_Post) {
                continue;
            }

            // Verify user can delete each lesson
            if (current_user_can('delete_post', $lesson->ID)) {
                wp_delete_post($lesson->ID, true);
            }
        }

        $course_id = get_post_meta($module_id, 'parent_course', true);
        
        // Delete the module permanently
        wp_delete_post($module_id, true);
        
        // Verify the module is actually deleted
        $deleted_module = get_post($module_id);
        if ($deleted_module !== null) {
            throw new \Exception(__('Failed to delete module', 'simple-lms'));
        }

        wp_send_json_success([
            'success' => true,
            'course_id' => $course_id
        ]);
    }

    /**
     * Save course settings
     * Note: course_roles removed - access is now managed via user_meta tags by WooCommerce integration
     */
    private static function save_course_settings($data) {
        $course_id = absint($data['course_id'] ?? 0);
        $allow_comments = isset($data['allow_comments']) ? rest_sanitize_boolean($data['allow_comments']) : false;

        if (!$course_id) {
            throw new \Exception(__('Invalid course ID', 'simple-lms'));
        }

        if (!current_user_can('edit_post', $course_id)) {
            throw new \Exception(__('You do not have permission to edit this course', 'simple-lms'));
        }

        // Save allow_comments setting
        update_post_meta($course_id, 'allow_comments', $allow_comments);

        wp_send_json_success(['message' => __('Settings saved successfully!', 'simple-lms')]);
    }

    /**
     * Update lesson status
     */
    private static function update_lesson_status($data) {
        $lesson_id = absint($data['lesson_id'] ?? 0);
        $status = sanitize_text_field($data['status'] ?? '');

        if (!$lesson_id || !$status) {
            throw new \Exception(__('Missing required fields', 'simple-lms'));
        }

        if (!current_user_can('edit_post', $lesson_id)) {
            throw new \Exception(__('You do not have permission to edit this lesson', 'simple-lms'));
        }

        // Sprawd� czy modu� nadrz�dny jest opublikowany gdy pr�bujemy opublikowa� lekcj�
        if ($status === 'publish') {
            $parent_module_id = get_post_meta($lesson_id, 'parent_module', true);
            if ($parent_module_id) {
                $parent_module_status = get_post_status($parent_module_id);
                if ($parent_module_status !== 'publish') {
                    throw new \Exception(__('To publish this lesson, first publish the module it belongs to.', 'simple-lms'));
                }
            }
        }

        $update = wp_update_post(array(
            'ID' => $lesson_id,
            'post_status' => $status
        ), true);

        if (is_wp_error($update)) {
            throw new \Exception($update->get_error_message());
        }

        wp_send_json_success(array(
            'status' => get_post_status($lesson_id)
        ));
        die();
    }

    /**
     * Update module status and its lessons
     */
    private static function update_module_status($data) {
        try {
            $module_id = absint($data['module_id'] ?? 0);
            $status = sanitize_text_field($data['status'] ?? '');

            error_log('SimpleLMS: update_module_status called with module_id=' . $module_id . ', status=' . $status);

            if (!$module_id || !$status) {
                error_log('SimpleLMS: Missing required fields module_id=' . $module_id . ', status=' . $status);
                throw new \Exception(__('Missing required fields', 'simple-lms'));
            }

            if (!current_user_can('edit_post', $module_id)) {
                error_log('SimpleLMS: Brak uprawnie� dla module_id=' . $module_id);
                throw new \Exception(__('You do not have permission to edit this module', 'simple-lms'));
            }

            // Update module status
            $update = wp_update_post(array(
                'ID' => $module_id,
                'post_status' => $status
            ), true);

            if (is_wp_error($update)) {
                error_log('SimpleLMS: wp_update_post error: ' . $update->get_error_message());
                throw new \Exception($update->get_error_message());
            }

            error_log('SimpleLMS: Module status updated to ' . $status . ', now updating lessons...');

            $updated_lessons = [];
            
            // If status is 'draft', update all lessons in the module to 'draft'
            if ($status === 'draft') {
                try {
                    // Use direct database query to avoid hooks
                    global $wpdb;
                    
                    $lesson_ids = $wpdb->get_col($wpdb->prepare(
                        "SELECT p.ID FROM {$wpdb->posts} p 
                        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
                        WHERE p.post_type = 'lesson' 
                        AND pm.meta_key = 'parent_module' 
                        AND pm.meta_value = %d
                        AND p.post_status IN ('publish', 'draft', 'pending', 'future')",
                        $module_id
                    ));

                    if ($lesson_ids && is_array($lesson_ids) && count($lesson_ids) > 0) {
                        foreach ($lesson_ids as $lesson_id) {
                            try {
                                // Check if user can edit this lesson
                                if (!current_user_can('edit_post', $lesson_id)) {
                                    continue;
                                }
                                
                                // Use direct wp_update_post with minimal data
                                $result = wp_update_post([
                                    'ID' => $lesson_id,
                                    'post_status' => 'draft'
                                ], true);
                                
                                if (!is_wp_error($result) && $result !== 0) {
                                    $updated_lessons[] = [
                                        'id' => $lesson_id,
                                        'status' => 'draft'
                                    ];
                                }
                            } catch (\Throwable $e) {
                                // Continue with next lesson
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // Continue anyway - module status is already updated
                }
            }

            wp_send_json_success([
                'status' => get_post_status($module_id),
                'lessons' => $updated_lessons
            ]);
            die();
        } catch (\Throwable $e) {
            error_log('SimpleLMS: FATAL ERROR in update_module_status: ' . $e->getMessage());
            error_log('SimpleLMS: Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }



    /**
     * Auto-tag module with course name
     * 
     * @param int $module_id Module ID
     * @return void
     */
    public static function autoTagModule(int $module_id): void {
        $course_id = get_post_meta($module_id, 'parent_course', true);
        if (!$course_id) {
            return;
        }
        
        $course_title = get_the_title($course_id);
        if ($course_title) {
            wp_set_post_tags($module_id, [$course_title], false);
        }
    }
    
    /**
     * Auto-tag lesson with course and module names
     * 
     * @param int $lesson_id Lesson ID
     * @return void
     */
    public static function autoTagLesson(int $lesson_id): void {
        $module_id = get_post_meta($lesson_id, 'parent_module', true);
        if (!$module_id) {
            return;
        }
        
        $module_title = get_the_title($module_id);
        $course_id = get_post_meta($module_id, 'parent_course', true);
        $course_title = $course_id ? get_the_title($course_id) : '';
        
        $tags = [];
        if ($course_title) {
            $tags[] = $course_title;
        }
        if ($module_title) {
            $tags[] = $module_title;
        }
        
        if (!empty($tags)) {
            wp_set_post_tags($lesson_id, $tags, false);
        }
    }

    /**
     * Bulk update all tags for LMS hierarchy
     * Useful for fixing tags after manual changes or initial setup
     * 
     * @return void
     */
    public static function bulkUpdateAllTags(): void {
        // Get all courses
        $courses = get_posts([
            'post_type' => 'course',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        
        foreach ($courses as $course) {
            if (!$course instanceof \WP_Post) {
                continue;
            }

            // Get all modules in this course
            $modules = get_posts([
                'post_type' => 'module',
                'posts_per_page' => -1,
                'meta_key' => 'parent_course',
                'meta_value' => $course->ID,
                'post_status' => 'any'
            ]);
            
            foreach ($modules as $module) {
                if (!$module instanceof \WP_Post) {
                    continue;
                }

                // Update module tags
                self::autoTagModule($module->ID);
                
                // Get all lessons in this module
                $lessons = get_posts([
                    'post_type' => 'lesson',
                    'posts_per_page' => -1,
                    'meta_key' => 'parent_module',
                    'meta_value' => $module->ID,
                    'post_status' => 'any'
                ]);
                
                foreach ($lessons as $lesson) {
                    if (!$lesson instanceof \WP_Post) {
                        continue;
                    }
                    // Update lesson tags
                    self::autoTagLesson($lesson->ID);
                }
            }
        }
    }

    /**
     * AJAX handler for bulk tag update
     * 
     * @return void
     */
    private static function bulk_update_tags(): void {
        self::bulkUpdateAllTags();
        wp_send_json_success(['message' => __('All tags have been updated', 'simple-lms')]);
    }

    /**
     * Copy post meta from one post to another
     * 
     * @param int $from_id Source post ID
     * @param int $to_id Target post ID
     * @param array $exclude_keys Meta keys to exclude from copying
     * @return void
     */
    private static function copy_post_meta($from_id, $to_id, $exclude_keys = []) {
        $meta_keys = get_post_custom_keys($from_id);
        if (!$meta_keys) {
            return;
        }

        foreach ($meta_keys as $key) {
            if (!in_array($key, $exclude_keys)) {
                $values = get_post_custom_values($key, $from_id);
                foreach ($values as $value) {
                    add_post_meta($to_id, $key, maybe_unserialize($value));
                }
            }
        }
    }

    /**
     * Handle lesson completion
     * 
     * @param array $data POST data
     * @return void
     */
    private static function completeLessonHandler(array $data): void {
        // Verify nonce for lesson completion
        if (!check_ajax_referer('simple-lms-nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Invalid security token', 'simple-lms')]);
            return;
        }

        // Check if user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('You must be logged in', 'simple-lms')]);
            return;
        }

        // Rate limiting: max 20 completions per minute per user
        $rate_key = 'slms_completion_rate_' . $user_id;
        $attempts = (int) get_transient($rate_key);
        if ($attempts >= 20) {
            wp_send_json_error(['message' => __('Too many attempts. Try again in a moment.', 'simple-lms')]);
            return;
        }
        set_transient($rate_key, $attempts + 1, MINUTE_IN_SECONDS);

        $lesson_id = intval($data['lesson_id'] ?? 0);
        
        if (!$lesson_id) {
            wp_send_json_error(['message' => __('Invalid lesson ID', 'simple-lms')]);
        }

        // Verify lesson exists
        $lesson = get_post($lesson_id);
        if (!$lesson || $lesson->post_type !== 'lesson') {
            wp_send_json_error(['message' => __('Lesson not found', 'simple-lms')]);
        }

        // Get current completed lessons
        $completed_lessons = get_user_meta($user_id, 'simple_lms_completed_lessons', true);
        if (!is_array($completed_lessons)) {
            $completed_lessons = [];
        }

        // Add lesson to completed if not already there
        if (!in_array($lesson_id, $completed_lessons)) {
            $completed_lessons[] = $lesson_id;
            update_user_meta($user_id, 'simple_lms_completed_lessons', $completed_lessons);
            
            // Also store completion date
            update_user_meta($user_id, 'simple_lms_lesson_completed_' . $lesson_id, current_time('mysql'));
        }

        wp_send_json_success([
            'message' => __('Lesson marked as completed', 'simple-lms'),
            'lesson_id' => $lesson_id,
            'completed_count' => count($completed_lessons)
        ]);
        die();
    }

    /**
     * Handle lesson un-completion
     * 
     * @param array $data POST data
     * @return void
     */
    private static function uncompleteLessonHandler(array $data): void {
        // Verify nonce for lesson un-completion
        if (!check_ajax_referer('simple-lms-nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Invalid security token', 'simple-lms')]);
            return;
        }

        // Check if user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('You must be logged in', 'simple-lms')]);
            return;
        }

        // Rate limiting: max 20 operations per minute per user
        $rate_key = 'slms_uncompletion_rate_' . $user_id;
        $attempts = (int) get_transient($rate_key);
        if ($attempts >= 20) {
            wp_send_json_error(['message' => __('Too many attempts. Try again in a moment.', 'simple-lms')]);
            return;
        }
        set_transient($rate_key, $attempts + 1, MINUTE_IN_SECONDS);

        $lesson_id = intval($data['lesson_id'] ?? 0);
        
        if (!$lesson_id) {
            wp_send_json_error(['message' => __('Invalid lesson ID', 'simple-lms')]);
            return;
        }

        // Verify lesson exists
        $lesson = get_post($lesson_id);
        if (!$lesson || $lesson->post_type !== 'lesson') {
            wp_send_json_error(['message' => __('Lesson not found', 'simple-lms')]);
            return;
        }

        // Get current completed lessons
        $completed_lessons = get_user_meta($user_id, 'simple_lms_completed_lessons', true);
        if (!is_array($completed_lessons)) {
            $completed_lessons = [];
        }

        // Remove lesson from completed if it's there
        $lesson_key = array_search($lesson_id, $completed_lessons);
        if ($lesson_key !== false) {
            unset($completed_lessons[$lesson_key]);
            $completed_lessons = array_values($completed_lessons); // Reindex array
            update_user_meta($user_id, 'simple_lms_completed_lessons', $completed_lessons);
            
            // Also remove completion date
            delete_user_meta($user_id, 'simple_lms_lesson_completed_' . $lesson_id);
            

        }

        wp_send_json_success([
            'message' => __('Lesson marked as incomplete', 'simple-lms'),
            'lesson_id' => $lesson_id,
            'completed_count' => count($completed_lessons)
        ]);
        die();
    }
    
    /**
     * Handle course preview for Elementor editor
     * 
     * @return void
     */
    public static function handleGetCoursePreview(): void {
        // This is for Elementor editor, so check for edit permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('No permission', 'simple-lms')]);
            return;
        }
        
        $course_id = absint($_POST['course_id'] ?? 0);
        if (!$course_id) {
            wp_send_json_error(['message' => __('No course ID provided', 'simple-lms')]);
            return;
        }
        
        // Parse settings
        $settings_json = sanitize_text_field($_POST['settings'] ?? '{}');
        $settings = json_decode($settings_json, true);
        if (!is_array($settings)) {
            $settings = [];
        }
        
        $display_mode = $settings['display_mode'] ?? 'accordion';
        $show_progress = ($settings['show_progress'] ?? 'yes') === 'yes';
        $show_lesson_count = ($settings['show_lesson_count'] ?? 'yes') === 'yes';
        $grid_columns = $settings['grid_columns'] ?? '2';
        
        // Get modules for the course
        $modules = \SimpleLMS\Cache_Handler::getCourseModules($course_id);
        
        if (empty($modules)) {
            wp_send_json_error(['message' => __('This course has no modules', 'simple-lms')]);
            return;
        }
        
        // Build HTML
        ob_start();
        
        $current_user_id = get_current_user_id();
        
        if ($display_mode === 'accordion') {
            echo '<div class="simple-lms-course-overview-accordion">';
            $module_index = 0;
            foreach ($modules as $module) {
                $module_index++;
                $lessons = \SimpleLMS\Cache_Handler::getModuleLessons((int)$module->ID);
                $is_open = $module_index === 1 ? 'open' : '';
                
                echo '<div class="simple-lms-accordion-item ' . esc_attr($is_open) . '">';
                echo '<div class="accordion-header">';
                echo '<span class="accordion-icon"></span>';
                echo '<h3 class="module-title">' . esc_html($module->post_title) . '</h3>';
                
                if ($show_lesson_count) {
                    echo '<span class="lessons-count">(' . count($lessons) . ' ' . _n('lesson', 'lessons', count($lessons), 'simple-lms') . ')</span>';
                }
                
                echo '</div>';
                echo '<div class="accordion-content">';
                
                if (!empty($lessons)) {
                    echo '<ul class="lessons-list">';
                    foreach ($lessons as $lesson) {
                        $is_completed = \SimpleLMS\Progress_Tracker::isLessonCompleted($current_user_id, $lesson->ID);
                        
                        echo '<li class="lesson-item' . ($is_completed ? ' completed-lesson' : '') . '">';
                        echo '<a href="' . esc_url(get_permalink($lesson->ID)) . '" class="lesson-link">';
                        
                        if ($show_progress) {
                            if ($is_completed) {
                                echo '<span class="completion-status completed">✓</span>';
                            } else {
                                echo '<span class="completion-status incomplete"></span>';
                            }
                        }
                        
                        echo '<span class="lesson-title">' . esc_html($lesson->post_title) . '</span>';
                        echo '</a></li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p class="no-lessons">' . esc_html__('No lessons in this module', 'simple-lms') . '</p>';
                }
                
                echo '</div></div>';
            }
            echo '</div>';
        } else {
            // List or Grid mode
            $container_classes = ['simple-lms-course-overview-list-grid', 'mode-' . $display_mode];
            if ($display_mode === 'grid') {
                $container_classes[] = 'columns-' . $grid_columns;
            }
            
            echo '<div class="' . esc_attr(implode(' ', $container_classes)) . '">';
            
            foreach ($modules as $module) {
                $lessons = \SimpleLMS\Cache_Handler::getModuleLessons((int)$module->ID);
                
                echo '<div class="simple-lms-accordion-item">';
                echo '<div class="accordion-header">';
                echo '<h3 class="module-title">' . esc_html($module->post_title) . '</h3>';
                
                if ($show_lesson_count) {
                    echo '<span class="lessons-count">(' . count($lessons) . ' ' . _n('lesson', 'lessons', count($lessons), 'simple-lms') . ')</span>';
                }
                
                echo '</div>';
                echo '<div class="accordion-content">';
                
                if (!empty($lessons)) {
                    echo '<ul class="lessons-list">';
                    foreach ($lessons as $lesson) {
                        $is_completed = \SimpleLMS\Progress_Tracker::isLessonCompleted($current_user_id, $lesson->ID);
                        
                        echo '<li class="lesson-item' . ($is_completed ? ' completed-lesson' : '') . '">';
                        echo '<a href="' . esc_url(get_permalink($lesson->ID)) . '" class="lesson-link">';
                        
                        if ($show_progress) {
                            if ($is_completed) {
                                echo '<span class="completion-status completed">✓</span>';
                            } else {
                                echo '<span class="completion-status incomplete"></span>';
                            }
                        }
                        
                        echo '<span class="lesson-title">' . esc_html($lesson->post_title) . '</span>';
                        echo '</a></li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p class="no-lessons">' . esc_html__('No lessons in this module', 'simple-lms') . '</p>';
                }
                
                echo '</div></div>';
            }
            
            echo '</div>';
            
            // Add inline styles
            $grid_styles = '';
            if ($display_mode === 'grid') {
                $grid_styles = '
.simple-lms-course-overview-list-grid.mode-grid{display:grid;gap:16px}
.simple-lms-course-overview-list-grid.mode-grid.columns-1{grid-template-columns:1fr}
.simple-lms-course-overview-list-grid.mode-grid.columns-2{grid-template-columns:repeat(2,1fr)}
.simple-lms-course-overview-list-grid.mode-grid.columns-3{grid-template-columns:repeat(3,1fr)}
.simple-lms-course-overview-list-grid.mode-grid.columns-4{grid-template-columns:repeat(4,1fr)}
@media(max-width:768px){.simple-lms-course-overview-list-grid.mode-grid{grid-template-columns:1fr}}
';
            }
            
            echo '<style>
.simple-lms-course-overview-list-grid{display:flex;flex-direction:column;gap:16px}
'.$grid_styles.'
.simple-lms-course-overview-list-grid .simple-lms-accordion-item{border:1px solid #e5e5e5;border-radius:8px;overflow:hidden}
.simple-lms-course-overview-list-grid .accordion-header{background-color:#f5f5f5;padding:15px 20px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;cursor:default}
.simple-lms-course-overview-list-grid .accordion-content{display:block!important;opacity:1!important;max-height:none!important;background-color:#ffffff;padding:15px 20px}
.simple-lms-course-overview-list-grid .lessons-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px}
.simple-lms-course-overview-list-grid .lesson-item{display:flex;align-items:center;gap:10px;padding:10px;border-radius:6px;transition:background-color 0.2s}
.simple-lms-course-overview-list-grid .lesson-item.completed-lesson{background-color:#edf7ed}
.simple-lms-course-overview-list-grid .lesson-item .lesson-link{display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;width:100%}
.simple-lms-course-overview-list-grid .completion-status{display:inline-flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.simple-lms-course-overview-list-grid .lesson-title{word-break:break-word}
.simple-lms-course-overview-list-grid .no-lessons{margin:0;font-size:0.9em;opacity:0.75}
</style>';
        }
        
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html]);
    }
}

/**
 * Auto-tagging functionality for modules and lessons
 */

/**
 * Auto-tag module when saved
 * 
 * @param int $post_id Post ID
 * @param \WP_Post $post Post object
 * @param bool $update Whether this is an update
 * @return void
 */
function autoTagModuleOnSave(int $post_id, \WP_Post $post, bool $update): void {
    // Skip Elementor templates and library posts
    if (in_array($post->post_type, ['elementor_library', 'elementor_snippet', 'e-landing-page'], true)) {
        return;
    }
    
    if ($post->post_type !== 'module' || wp_is_post_revision($post_id)) {
        return;
    }
    
    Ajax_Handler::autoTagModule($post_id);
}

/**
 * Auto-tag lesson when saved
 * 
 * @param int $post_id Post ID
 * @param \WP_Post $post Post object
 * @param bool $update Whether this is an update
 * @return void
 */
function autoTagLessonOnSave(int $post_id, \WP_Post $post, bool $update): void {
    // Skip Elementor templates and library posts
    if (in_array($post->post_type, ['elementor_library', 'elementor_snippet', 'e-landing-page'], true)) {
        return;
    }
    
    if ($post->post_type !== 'lesson' || wp_is_post_revision($post_id)) {
        return;
    }
    
    Ajax_Handler::autoTagLesson($post_id);
}

/**
 * Update tags in related posts when course title changes
 * 
 * @param int $post_id Course ID
 * @param \WP_Post $post_after Updated post object
 * @param \WP_Post $post_before Original post object
 * @return void
 */
function updateTagsOnCourseUpdate(int $post_id, \WP_Post $post_after, \WP_Post $post_before): void {
    // Skip Elementor templates and library posts
    if (in_array($post_after->post_type, ['elementor_library', 'elementor_snippet', 'e-landing-page'], true)) {
        return;
    }
    
    if ($post_after->post_type !== 'course' || wp_is_post_revision($post_id)) {
        return;
    }
    
    // Check if title actually changed
    if ($post_after->post_title === $post_before->post_title) {
        return;
    }
    
    $old_title = sanitize_text_field($post_before->post_title);
    $new_title = sanitize_text_field($post_after->post_title);
    
    // Get all modules in this course
    $modules = get_posts([
        'post_type' => 'module',
        'posts_per_page' => -1,
        'meta_key' => 'parent_course',
        'meta_value' => $post_id,
        'post_status' => 'any'
    ]);
    
    foreach ($modules as $module) {
        if (!$module instanceof \WP_Post) {
            continue;
        }

        // Update module tags
        updatePostTagName($module->ID, $old_title, $new_title);
        
        // Get all lessons in this module and update their tags
        $lessons = get_posts([
            'post_type' => 'lesson',
            'posts_per_page' => -1,
            'meta_key' => 'parent_module',
            'meta_value' => $module->ID,
            'post_status' => 'any'
        ]);
        
        foreach ($lessons as $lesson) {
            if (!$lesson instanceof \WP_Post) {
                continue;
            }
            updatePostTagName($lesson->ID, $old_title, $new_title);
        }
    }
}

/**
 * Update tags in related posts when module title changes
 * 
 * @param int $post_id Module ID
 * @param \WP_Post $post_after Updated post object
 * @param \WP_Post $post_before Original post object
 * @return void
 */
function updateTagsOnModuleUpdate(int $post_id, \WP_Post $post_after, \WP_Post $post_before): void {
    // Skip Elementor templates and library posts
    if (in_array($post_after->post_type, ['elementor_library', 'elementor_snippet', 'e-landing-page'], true)) {
        return;
    }
    
    if ($post_after->post_type !== 'module' || wp_is_post_revision($post_id)) {
        return;
    }
    
    // Check if title actually changed
    if ($post_after->post_title === $post_before->post_title) {
        return;
    }
    
    $old_title = sanitize_text_field($post_before->post_title);
    $new_title = sanitize_text_field($post_after->post_title);
    
    // Get all lessons in this module and update their tags
    $lessons = get_posts([
        'post_type' => 'lesson',
        'posts_per_page' => -1,
        'meta_key' => 'parent_module',
        'meta_value' => $post_id,
        'post_status' => 'any'
    ]);
    
    foreach ($lessons as $lesson) {
        if (!$lesson instanceof \WP_Post) {
            continue;
        }
        updatePostTagName($lesson->ID, $old_title, $new_title);
    }
}

/**
 * Update specific tag name in a post
 * 
 * @param int $post_id Post ID
 * @param string $old_tag_name Old tag name
 * @param string $new_tag_name New tag name
 * @return void
 */
function updatePostTagName(int $post_id, string $old_tag_name, string $new_tag_name): void {
    if (empty($old_tag_name) || empty($new_tag_name) || $old_tag_name === $new_tag_name) {
        return;
    }
    
    $current_tags = (array) wp_get_post_tags($post_id, ['fields' => 'names']);
    
    if (empty($current_tags)) {
        return;
    }
    
    $updated_tags = [];
    $tag_updated = false;
    
    foreach ($current_tags as $tag_name) {
        if ($tag_name === $old_tag_name) {
            $updated_tags[] = $new_tag_name;
            $tag_updated = true;
        } else {
            $updated_tags[] = $tag_name;
        }
    }
    
    // Only update if we actually found and replaced the tag
    if ($tag_updated) {
        wp_set_post_tags($post_id, $updated_tags, false);
    }
}

// Hook auto-tagging to post save
add_action('save_post', __NAMESPACE__ . '\autoTagModuleOnSave', 20, 3);
add_action('save_post', __NAMESPACE__ . '\autoTagLessonOnSave', 20, 3);

// Hook tag updates to post updates
add_action('post_updated', __NAMESPACE__ . '\updateTagsOnCourseUpdate', 20, 3);
add_action('post_updated', __NAMESPACE__ . '\updateTagsOnModuleUpdate', 20, 3);

// Ajax_Handler is now managed by ServiceContainer
// and instantiated in Plugin::registerLateServices()
?>