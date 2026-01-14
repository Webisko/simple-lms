<?php
/**
 * Elementor Dynamic Tags Integration
 * Registers Simple LMS category and dynamic tags for Elementor Pro
 *
 * @package SimpleLMS
 */

namespace SimpleLMS\Elementor;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Elementor_Dynamic_Tags
 * Main integration class for Elementor Pro dynamic tags
 */
class Elementor_Dynamic_Tags {

    /**
     * Initialize the integration
     */
    public static function init(): void {
        // Check if Elementor Pro is active and has dynamic tags
        if (!did_action('elementor/loaded')) {
            return;
        }

        // Register dynamic tags (requires Elementor Pro)
        add_action('elementor/dynamic_tags/register', [__CLASS__, 'register_dynamic_tags']);

        // Register widgets (works with free Elementor)
        add_action('elementor/widgets/register', [__CLASS__, 'register_widgets']);
        
        // Register widget category
        add_action('elementor/elements/categories_registered', [__CLASS__, 'register_widget_category']);
    }

    /**
     * Register Simple LMS dynamic tags
     *
     * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager
     */
    public static function register_dynamic_tags($dynamic_tags_manager): void {
        // Register Simple LMS category
        $dynamic_tags_manager->register_group(
            'simple-lms',
            [
                'title' => __('Simple LMS', 'simple-lms')
            ]
        );

        // Include tag classes
        require_once __DIR__ . '/tags/course-title.php';
        require_once __DIR__ . '/tags/module-title.php';
        require_once __DIR__ . '/tags/lesson-title.php';

        // Register individual tags
        $dynamic_tags_manager->register(new Tags\Course_Title_Tag());
        $dynamic_tags_manager->register(new Tags\Module_Title_Tag());
        $dynamic_tags_manager->register(new Tags\Lesson_Title_Tag());
    }

    /**
     * Register Simple LMS widgets
     */
    public static function register_widgets($widgets_manager): void {
        // Check if required classes exist
        if (!class_exists('\Elementor\Widget_Base')) {
            return;
        }

        // Include widget classes
        require_once __DIR__ . '/widgets/lesson-content-widget.php';
        require_once __DIR__ . '/widgets/course-overview-widget.php';
        require_once __DIR__ . '/widgets/course-progress-widget.php';
        require_once __DIR__ . '/widgets/lesson-completion-button-widget.php';
        require_once __DIR__ . '/widgets/lesson-navigation-widget.php';
        require_once __DIR__ . '/widgets/lesson-video-widget.php';
        require_once __DIR__ . '/widgets/lesson-attachments-widget.php';
        require_once __DIR__ . '/widgets/course-info-box-widget.php';
        require_once __DIR__ . '/widgets/continue-learning-button-widget.php';
        require_once __DIR__ . '/widgets/access-status-widget.php';
        require_once __DIR__ . '/widgets/course-purchase-widget.php';
        require_once __DIR__ . '/widgets/breadcrumbs-widget.php';
        require_once __DIR__ . '/widgets/user-courses-grid-widget.php';
        require_once __DIR__ . '/widgets/module-navigation-widget.php';
        require_once __DIR__ . '/widgets/lesson-progress-indicator-widget.php';
        require_once __DIR__ . '/widgets/user-progress-dashboard-widget.php';

        // Register widgets
        $widgets_manager->register(new Widgets\Lesson_Content_Widget());
        $widgets_manager->register(new Widgets\Course_Overview_Widget());
        $widgets_manager->register(new Widgets\Course_Progress_Widget());
        $widgets_manager->register(new Widgets\Lesson_Completion_Button_Widget());
        $widgets_manager->register(new Widgets\Lesson_Navigation_Widget());
        $widgets_manager->register(new Widgets\Lesson_Video_Widget());
        $widgets_manager->register(new Widgets\Lesson_Attachments_Widget());
        $widgets_manager->register(new Widgets\Course_Info_Box_Widget());
        $widgets_manager->register(new Widgets\Continue_Learning_Button_Widget());
        $widgets_manager->register(new Widgets\Access_Status_Widget());
        $widgets_manager->register(new Widgets\Course_Purchase_Widget());
        $widgets_manager->register(new Widgets\Breadcrumbs_Widget());
        $widgets_manager->register(new Widgets\User_Courses_Grid_Widget());
        $widgets_manager->register(new Widgets\Module_Navigation_Widget());
        $widgets_manager->register(new Widgets\Lesson_Progress_Indicator_Widget());
        $widgets_manager->register(new Widgets\User_Progress_Dashboard_Widget());
    }

    /**
     * Register Simple LMS widget category
     */
    public static function register_widget_category($elements_manager): void {
        $elements_manager->add_category(
            'simple-lms',
            [
                'title' => __('Simple LMS', 'simple-lms'),
                'icon' => 'fa fa-graduation-cap',
            ]
        );
    }

    /**
     * Get current course ID from context
     * Works in single course, module, lesson pages or from query loop
     *
     * @return int Course ID or 0
     */
    public static function get_current_course_id(): int {
        // Try from explicit course_id parameter (shortcode/manual)
        if (isset($_GET['course_id'])) {
            return absint($_GET['course_id']);
        }

        // Try from current post if it's a course
        if (is_singular('course')) {
            return get_the_ID();
        }

        // Try from current post if it's a module - get parent course
        if (is_singular('module')) {
            $course_id = get_post_meta(get_the_ID(), 'parent_course', true);
            return $course_id ? (int) $course_id : 0;
        }

        // Try from current post if it's a lesson - get parent module's course
        if (is_singular('lesson')) {
            $module_id = get_post_meta(get_the_ID(), 'parent_module', true);
            if ($module_id) {
                $course_id = get_post_meta($module_id, 'parent_course', true);
                return $course_id ? (int) $course_id : 0;
            }
        }

        // Try from Elementor loop
        if (class_exists('\Elementor\Plugin')) {
            $document = \Elementor\Plugin::$instance->documents->get_current();
            if ($document) {
                $post_id = $document->get_main_id();
                $post_type = get_post_type($post_id);
                
                if ($post_type === 'course') {
                    return $post_id;
                } elseif ($post_type === 'module') {
                    $course_id = get_post_meta($post_id, 'parent_course', true);
                    return $course_id ? (int) $course_id : 0;
                } elseif ($post_type === 'lesson') {
                    $module_id = get_post_meta($post_id, 'parent_module', true);
                    if ($module_id) {
                        $course_id = get_post_meta($module_id, 'parent_course', true);
                        return $course_id ? (int) $course_id : 0;
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Get current module ID from context
     *
     * @return int Module ID or 0
     */
    public static function get_current_module_id(): int {
        // Try from explicit module_id parameter
        if (isset($_GET['module_id'])) {
            return absint($_GET['module_id']);
        }

        // Try from current post if it's a module
        if (is_singular('module')) {
            return get_the_ID();
        }

        // Try from current post if it's a lesson - get parent module
        if (is_singular('lesson')) {
            $module_id = get_post_meta(get_the_ID(), 'parent_module', true);
            return $module_id ? (int) $module_id : 0;
        }

        // Try from Elementor loop
        if (class_exists('\Elementor\Plugin')) {
            $document = \Elementor\Plugin::$instance->documents->get_current();
            if ($document) {
                $post_id = $document->get_main_id();
                $post_type = get_post_type($post_id);
                
                if ($post_type === 'module') {
                    return $post_id;
                } elseif ($post_type === 'lesson') {
                    $module_id = get_post_meta($post_id, 'parent_module', true);
                    return $module_id ? (int) $module_id : 0;
                }
            }
        }

        return 0;
    }

    /**
     * Get current lesson ID from context
     *
     * @return int Lesson ID or 0
     */
    public static function get_current_lesson_id(): int {
        // Try from explicit lesson_id parameter
        if (isset($_GET['lesson_id'])) {
            return absint($_GET['lesson_id']);
        }

        // Try from current post if it's a lesson
        if (is_singular('lesson')) {
            return get_the_ID();
        }

        // Try from Elementor loop
        if (class_exists('\Elementor\Plugin')) {
            $document = \Elementor\Plugin::$instance->documents->get_current();
            if ($document) {
                $post_id = $document->get_main_id();
                if (get_post_type($post_id) === 'lesson') {
                    return $post_id;
                }
            }
        }

        return 0;
    }
}

// Initialize
Elementor_Dynamic_Tags::init();
