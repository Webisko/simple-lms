<?php
/**
 * Bricks Builder Integration
 * Registers all Simple LMS widgets for Bricks Builder
 *
 * @package SimpleLMS
 */

namespace SimpleLMS\Bricks;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Bricks_Integration
 * Main integration class for Bricks Builder
 */
class Bricks_Integration {

    /**
     * Initialize the integration
     */
    public static function init(): void {
        // Check if Bricks is active
        if (!class_exists('Bricks\Database')) {
            return;
        }

        // Register Bricks elements on proper hook
        // Use bricks_init which fires after Bricks is fully loaded
        add_action('bricks_init', [__CLASS__, 'register_elements'], 15);
        
        // Register custom category
        add_filter('bricks/builder/i18n', [__CLASS__, 'register_category']);
    }

    /**
     * Register all Simple LMS elements
     */
    public static function register_elements(): void {
        if (!class_exists('Bricks\Elements')) {
            return;
        }

        $element_files = [
            'lesson-content',
            'course-overview',
            'course-progress',
            'lesson-completion-button',
            'lesson-navigation',
            'lesson-video',
            'lesson-attachments',
            'course-info-box',
            'continue-learning-button',
            'access-status',
            'course-purchase',
            'breadcrumbs-navigation',
            'user-courses-grid',
            'module-navigation',
            'lesson-progress-indicator',
            'user-progress-dashboard',
        ];

        foreach ($element_files as $file) {
            $file_path = __DIR__ . '/elements/' . $file . '.php';
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }

        // Register elements with Bricks
        $elements = [
            'SimpleLMS\Bricks\Elements\Lesson_Content',
            'SimpleLMS\Bricks\Elements\Course_Overview',
            'SimpleLMS\Bricks\Elements\Course_Progress',
            'SimpleLMS\Bricks\Elements\Lesson_Completion_Button',
            'SimpleLMS\Bricks\Elements\Lesson_Navigation',
            'SimpleLMS\Bricks\Elements\Lesson_Video',
            'SimpleLMS\Bricks\Elements\Lesson_Attachments',
            'SimpleLMS\Bricks\Elements\Course_Info_Box',
            'SimpleLMS\Bricks\Elements\Continue_Learning_Button',
            'SimpleLMS\Bricks\Elements\Access_Status',
            'SimpleLMS\Bricks\Elements\Course_Purchase',
            'SimpleLMS\Bricks\Elements\Breadcrumbs',
            'SimpleLMS\Bricks\Elements\User_Courses_Grid',
            'SimpleLMS\Bricks\Elements\Module_Navigation',
            'SimpleLMS\Bricks\Elements\Lesson_Progress_Indicator',
            'SimpleLMS\Bricks\Elements\User_Progress_Dashboard',
        ];

        foreach ($elements as $element) {
            if (class_exists($element)) {
                \Bricks\Elements::register_element($element);
            }
        }
    }

    /**
     * Register Simple LMS category
     */
    public static function register_category($i18n): array {
        $i18n['simple-lms'] = esc_html__('Simple LMS', 'simple-lms');
        return $i18n;
    }

    /**
     * Get current course ID from context
     */
    public static function get_current_course_id(): int {
        $post_id = get_queried_object_id();
        $post_type = get_post_type($post_id);

        if ($post_type === 'course') {
            return $post_id;
        }

        if ($post_type === 'module') {
            return (int) get_post_meta($post_id, 'module_course', true);
        }

        if ($post_type === 'lesson') {
            $module_id = (int) get_post_meta($post_id, 'lesson_module', true);
            if ($module_id) {
                return (int) get_post_meta($module_id, 'module_course', true);
            }
        }

        return 0;
    }

    /**
     * Get current lesson ID from context
     */
    public static function get_current_lesson_id(): int {
        $post_id = get_queried_object_id();
        if (get_post_type($post_id) === 'lesson') {
            return $post_id;
        }
        return 0;
    }
}

// Initialize
Bricks_Integration::init();
