<?php
/**
 * AJAX request handlers for Simple LMS
 * 
 * @package SimpleLMS
 * @since 1.0.1
 */

declare(strict_types=1);

namespace SimpleLMS;

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
            return ['message' => __('Nieprawidłowy token bezpieczeństwa', 'simple-lms')];
        }
        
        if ($check_login && !is_user_logged_in()) {
            return ['message' => __('Musisz być zalogowany', 'simple-lms')];
        }
        
        if ($capability && !current_user_can($capability)) {
            return ['message' => __('Brak uprawnień do wykonania tej operacji', 'simple-lms')];
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
                throw new \InvalidArgumentException(__('Nieprawidłowe dane.', 'simple-lms'));
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
                throw new \InvalidArgumentException(__('Nieprawidłowe dane.', 'simple-lms'));
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
                throw new \InvalidArgumentException(__('Nieprawidłowa akcja', 'simple-lms'));
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
                    throw new \InvalidArgumentException(__('Nieznana akcja', 'simple-lms'));
            }
        } catch (\Exception $e) {
            error_log('SimpleLMS AJAX ERROR in action ' . ($action ?? 'unknown_action') . ': ' . $e->getMessage());
            error_log('SimpleLMS AJAX ERROR trace: ' . $e->getTraceAsString());
            self::logError($action ?? 'unknown_action', $e);
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
            throw new \InvalidArgumentException(__('Brak wymaganych pól', 'simple-lms'));
        }

        // Validate course exists and is correct post type
        if (!self::validatePostType($courseId, 'course')) {
            throw new \InvalidArgumentException(__('Nieprawidłowy kurs', 'simple-lms'));
        }

        if (!self::userHasAccessToCourse($courseId)) {
            throw new \InvalidArgumentException(__('Nie masz dostępu do tego kursu', 'simple-lms'));
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
        $valid = false;
        
        // Debug logging
        error_log('SimpleLMS AJAX: action=' . $action . ', nonce_present=' . (!empty($nonce) ? 'yes' : 'no'));
        
        if ($nonce) {
            // Try multiple verification methods
            
            // Method 1: Security_Service if injected
            if (self::$security) {
                $valid = self::$security->verifyNonce((string)$nonce, 'ajax');
                error_log('SimpleLMS AJAX: Security_Service->verifyNonce result=' . ($valid ? 'true' : 'false'));
            }
            
            // Method 2: Container Security_Service
            if (!$valid) {
                try {
                    $container = ServiceContainer::getInstance();
                    if ($container->has(Security_Service::class)) {
                        /** @var Security_Service $sec */
                        $sec = $container->get(Security_Service::class);
                        $valid = $sec->verifyNonce((string)$nonce, 'ajax');
                        error_log('SimpleLMS AJAX: Container Security_Service->verifyNonce result=' . ($valid ? 'true' : 'false'));
                    }
                } catch (\Throwable $e) {
                    error_log('SimpleLMS AJAX: Container Security_Service failed: ' . $e->getMessage());
                }
            }
            
            // Method 3: Direct wp_verify_nonce with filter
            if (!$valid) {
                $nonce_action = apply_filters('simple_lms_ajax_nonce_action', 'simple-lms-nonce');
                $valid = (bool) wp_verify_nonce((string) $nonce, $nonce_action);
                error_log('SimpleLMS AJAX: wp_verify_nonce with action=' . $nonce_action . ' result=' . ($valid ? 'true' : 'false'));
            }
            
            // Method 4: Try simple-lms-nonce_ajax directly
            if (!$valid) {
                $valid = (bool) wp_verify_nonce((string) $nonce, 'simple-lms-nonce_ajax');
                error_log('SimpleLMS AJAX: wp_verify_nonce with simple-lms-nonce_ajax result=' . ($valid ? 'true' : 'false'));
            }
        }
        
        if (!$valid) {
            error_log('SimpleLMS AJAX: All nonce verification methods failed');
            if (self::$logger) {
                self::$logger->warning('AJAX nonce verification failed', ['action' => $action]);
            }
            throw new \Exception(__('Błąd weryfikacji bezpieczeństwa', 'simple-lms'));
        }
        
        error_log('SimpleLMS AJAX: Nonce verification SUCCESS');

        if (!is_user_logged_in()) {
            if (self::$logger) {
                self::$logger->warning('AJAX request from logged-out user', ['action' => $action]);
            }
            throw new \Exception(__('Musisz być zalogowany', 'simple-lms'));
        }

        if ($requiredCap && !current_user_can($requiredCap)) {
            if (self::$logger) {
                self::$logger->warning('AJAX insufficient capability', ['action' => $action, 'required' => $requiredCap]);
            }
            throw new \Exception(__('Niewystarczające uprawnienia', 'simple-lms'));
        }
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
            throw new \Exception(__('Brak wymaganych pól', 'simple-lms'));
        }

        // Validate module exists and is correct post type
        if (!self::validatePostType($module_id, 'module')) {
            throw new \Exception(__('Nieprawidłowy moduł', 'simple-lms'));
        }

        $course_id = get_post_meta($module_id, 'parent_course', true);
        $course_id = absint($course_id);
        if (!$course_id || !self::userHasAccessToCourse($course_id)) {
            throw new \Exception(__('Nie masz dostępu do tego kursu', 'simple-lms'));
        }

        // Get the current order of lessons dynamically
        $current_order = isset($_POST['current_order']) ? array_map('intval', $_POST['current_order']) : [];

        if (!empty($current_order)) {
            $highest_order = max($current_order);
        } else {
            // Fallback to fetching the highest menu_order from the database
            $existing_lessons = get_posts([
                'post_type' => 'lesson',
                'posts_per_page' => -1,
                'meta_key' => 'parent_module',
                'meta_value' => $module_id,
                'orderby' => 'menu_order',
                'order' => 'DESC'
            ]);

            $highest_order = 0;
            if (!empty($existing_lessons)) {
                $highest_order = get_post_field('menu_order', $existing_lessons[0]);
            }
        }

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
            throw new \Exception(__('Nieprawidłowy identyfikator lekcji', 'simple-lms'));
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
                throw new \Exception(__('Nie masz uprawnień do duplikowania lekcji w tym kursie.', 'simple-lms'));
            }
        } else {
            // If lesson has no parent module, it's an orphaned lesson, prevent duplication or handle as error
            throw new \Exception(__('Nie można zduplikować lekcji bez modułu nadrzędnego.', 'simple-lms'));
        }

        // Get parent module (already fetched as $parent_module_id)
        // $parent_module = get_post_meta($lesson_id, 'parent_module', true);

        // Get the highest menu_order for existing lessons in this module
        $existing_lessons = get_posts([
            'post_type' => 'lesson',
            'posts_per_page' => -1,
            'meta_key' => 'parent_module',
            'meta_value' => $parent_module_id, // Use $parent_module_id
            'orderby' => 'menu_order',
            'order' => 'DESC'
        ]);

        $highest_order = 0;
        if (!empty($existing_lessons)) {
            $highest_order = get_post_field('menu_order', $existing_lessons[0]);
        }

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
        $lesson_id = absint($data['lesson_id'] ?? 0);
        if (!$lesson_id) {
            throw new \Exception(__('Nieprawidłowy identyfikator lekcji', 'simple-lms'));
        }

        // Verify post type before capability check
        if (!self::validatePostType($lesson_id, 'lesson')) {
            throw new \Exception(__('Nieprawidłowa lekcja', 'simple-lms'));
        }

        if (!current_user_can('delete_post', $lesson_id)) {
            throw new \Exception(__('Nie masz uprawnień do usunięcia tej lekcji', 'simple-lms'));
        }

        $module_id = get_post_meta($lesson_id, 'parent_module', true);

        if (!wp_delete_post($lesson_id, true)) {
            throw new \Exception(__('Nie udało się usunąć lekcji', 'simple-lms'));
        }

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
            throw new \Exception(__('Nieprawidłowy identyfikator modułu', 'simple-lms'));
        }

        $module = get_post($module_id);
        if (!$module || $module->post_type !== 'module') {
            throw new \Exception(__('Moduł nie znaleziony', 'simple-lms'));
        }

        // Check user permission - needs publish capability for duplication
        if ($error = self::validateCommonAjaxChecks('publish_posts', false, false)) {
            throw new \Exception($error['message']);
        }

        // Check user permission for the original module's course
        $parent_course_id = get_post_meta($module_id, 'parent_course', true);
        $parent_course_id = absint($parent_course_id);
        if (!$parent_course_id || !self::userHasAccessToCourse($parent_course_id)) {
            throw new \Exception(__('Nie masz uprawnień do duplikowania modułów w tym kursie.', 'simple-lms'));
        }
        
        // Get the highest menu_order for existing modules
        $existing_modules = get_posts([
            'post_type' => 'module',
            'posts_per_page' => -1,
            'meta_key' => 'parent_course',
            'meta_value' => $parent_course_id,
            'orderby' => 'menu_order',
            'order' => 'DESC'
        ]);

        $highest_order = 0;
        if (!empty($existing_modules)) {
            $highest_order = get_post_field('menu_order', $existing_modules[0]);
        }
        
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
                'post_title'   => $lesson->post_title . ' ' . __('(Kopia)', 'simple-lms'),
                'post_content' => $lesson->post_content,
                'post_excerpt' => $lesson->post_excerpt,
                'post_status'  => 'draft',
                'post_type'    => 'lesson',
                'menu_order'   => $lesson->menu_order
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
            throw new \Exception(__('Nieprawidłowy identyfikator modułu', 'simple-lms'));
        }

        // Verify post type before capability check
        if (!self::validatePostType($module_id, 'module')) {
            throw new \Exception(__('Nieprawidłowy moduł', 'simple-lms'));
        }

        if (!current_user_can('delete_post', $module_id)) {
            throw new \Exception(__('Nie masz uprawnień do usunięcia tego modułu', 'simple-lms'));
        }
        
        // Delete associated lessons (verify user can delete each one)
        $lessons = get_posts([
            'post_type'      => 'lesson',
            'posts_per_page' => -1,
            'meta_key'       => 'parent_module',
            'meta_value'     => $module_id
        ]);

        foreach ($lessons as $lesson) {
            // Verify user can delete each lesson
            if (current_user_can('delete_post', $lesson->ID)) {
                wp_delete_post($lesson->ID, true);
            }
        }

        $course_id = get_post_meta($module_id, 'parent_course', true);
        
        if (!wp_delete_post($module_id, true)) {
            throw new \Exception(__('Nie udało się usunąć modułu', 'simple-lms'));
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
            throw new \Exception(__('Nieprawidłowy identyfikator kursu', 'simple-lms'));
        }

        if (!current_user_can('edit_post', $course_id)) {
            throw new \Exception(__('Nie masz uprawnień do edycji tego kursu', 'simple-lms'));
        }

        // Save allow_comments setting
        update_post_meta($course_id, 'allow_comments', $allow_comments);

        wp_send_json_success(['message' => __('Ustawienia zapisane pomyślnie!', 'simple-lms')]);
    }

    /**
     * Update lesson status
     */
    private static function update_lesson_status($data) {
        $lesson_id = absint($data['lesson_id'] ?? 0);
        $status = sanitize_text_field($data['status'] ?? '');

        if (!$lesson_id || !$status) {
            throw new \Exception(__('Brak wymaganych pól', 'simple-lms'));
        }

        if (!current_user_can('edit_post', $lesson_id)) {
            throw new \Exception(__('Nie masz uprawnień do edycji tej lekcji', 'simple-lms'));
        }

        // Sprawdź czy moduł nadrzędny jest opublikowany gdy próbujemy opublikować lekcję
        if ($status === 'publish') {
            $parent_module_id = get_post_meta($lesson_id, 'parent_module', true);
            if ($parent_module_id) {
                $parent_module_status = get_post_status($parent_module_id);
                if ($parent_module_status !== 'publish') {
                    throw new \Exception(__('Aby opublikować tę lekcję, najpierw opublikuj moduł, w którym się znajduje.', 'simple-lms'));
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
                error_log('SimpleLMS: Brak wymaganych pól module_id=' . $module_id . ', status=' . $status);
                throw new \Exception(__('Brak wymaganych pól', 'simple-lms'));
            }

            if (!current_user_can('edit_post', $module_id)) {
                error_log('SimpleLMS: Brak uprawnień dla module_id=' . $module_id);
                throw new \Exception(__('Nie masz uprawnień do edycji tego modułu', 'simple-lms'));
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
            // Get all modules in this course
            $modules = get_posts([
                'post_type' => 'module',
                'posts_per_page' => -1,
                'meta_key' => 'parent_course',
                'meta_value' => $course->ID,
                'post_status' => 'any'
            ]);
            
            foreach ($modules as $module) {
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
        wp_send_json_success(['message' => __('Wszystkie tagi zostały zaktualizowane', 'simple-lms')]);
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
            wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa', 'simple-lms')]);
            return;
        }

        // Check if user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('Musisz być zalogowany', 'simple-lms')]);
            return;
        }

        // Rate limiting: max 20 completions per minute per user
        $rate_key = 'slms_completion_rate_' . $user_id;
        $attempts = (int) get_transient($rate_key);
        if ($attempts >= 20) {
            wp_send_json_error(['message' => __('Zbyt wiele prób. Spróbuj ponownie za chwilę.', 'simple-lms')]);
            return;
        }
        set_transient($rate_key, $attempts + 1, MINUTE_IN_SECONDS);

        $lesson_id = intval($data['lesson_id'] ?? 0);
        
        if (!$lesson_id) {
            wp_send_json_error(['message' => __('Nieprawidłowy ID lekcji', 'simple-lms')]);
        }

        // Verify lesson exists
        $lesson = get_post($lesson_id);
        if (!$lesson || $lesson->post_type !== 'lesson') {
            wp_send_json_error(['message' => __('Lekcja nie została znaleziona', 'simple-lms')]);
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
            'message' => __('Lekcja oznaczona jako ukończona', 'simple-lms'),
            'lesson_id' => $lesson_id,
            'completed_count' => count($completed_lessons)
        ]);
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
            wp_send_json_error(['message' => __('Nieprawidłowy token bezpieczeństwa', 'simple-lms')]);
            return;
        }

        // Check if user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('Musisz być zalogowany', 'simple-lms')]);
            return;
        }

        // Rate limiting: max 20 operations per minute per user
        $rate_key = 'slms_uncompletion_rate_' . $user_id;
        $attempts = (int) get_transient($rate_key);
        if ($attempts >= 20) {
            wp_send_json_error(['message' => __('Zbyt wiele prób. Spróbuj ponownie za chwilę.', 'simple-lms')]);
            return;
        }
        set_transient($rate_key, $attempts + 1, MINUTE_IN_SECONDS);

        $lesson_id = intval($data['lesson_id'] ?? 0);
        
        if (!$lesson_id) {
            wp_send_json_error(['message' => __('Nieprawidłowy ID lekcji', 'simple-lms')]);
            return;
        }

        // Verify lesson exists
        $lesson = get_post($lesson_id);
        if (!$lesson || $lesson->post_type !== 'lesson') {
            wp_send_json_error(['message' => __('Lekcja nie została znaleziona', 'simple-lms')]);
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
            'message' => __('Lekcja oznaczona jako nieukończona', 'simple-lms'),
            'lesson_id' => $lesson_id,
            'completed_count' => count($completed_lessons)
        ]);
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
    
    $current_tags = wp_get_post_tags($post_id, ['fields' => 'names']);
    
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