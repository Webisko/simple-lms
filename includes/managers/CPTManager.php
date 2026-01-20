<?php
namespace SimpleLMS\Managers;

/**
 * Custom Post Type Manager
 *
 * @package SimpleLMS
 * @since 1.4.0
 */
use SimpleLMS\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CPT Manager Class
 *
 * Manages registration of custom post types for Simple LMS.
 */
class CPTManager
{
    /**
     * Registered post types
     *
     * @var array<string, array>
     */
    private array $postTypes = [];

    /**
     * Logger instance
     *
     * @var Logger|null
     */
    private ?Logger $logger = null;

    /**
     * Constructor
     */
    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Register all Simple LMS custom post types
     *
     * @return void
     */
    public function registerPostTypes(): void
    {
        $this->registerCoursePostType();
        $this->registerModulePostType();
        $this->registerLessonPostType();
    }

    /**
     * Register Course post type
     *
     * @return void
     */
    private function registerCoursePostType(): void
    {
        $labels = [
            'name' => \_x('Courses', 'Post Type General Name', 'simple-lms'),
            'singular_name' => \_x('Course', 'Post Type Singular Name', 'simple-lms'),
            'menu_name' => \__('Simple LMS', 'simple-lms'),
            'name_admin_bar' => \__('Course', 'simple-lms'),
            'archives' => \__('Course Archives', 'simple-lms'),
            'attributes' => \__('Course Attributes', 'simple-lms'),
            'parent_item_colon' => \__('Parent Course:', 'simple-lms'),
            'all_items' => \__('All Courses', 'simple-lms'),
            'add_new_item' => \__('Add New Course', 'simple-lms'),
            'add_new' => \__('Add New', 'simple-lms'),
            'new_item' => \__('New Course', 'simple-lms'),
            'edit_item' => \__('Edit Course', 'simple-lms'),
            'update_item' => \__('Update Course', 'simple-lms'),
            'view_item' => \__('View Course', 'simple-lms'),
            'view_items' => \__('View Courses', 'simple-lms'),
            'search_items' => \__('Search Course', 'simple-lms'),
            'not_found' => \__('Not found', 'simple-lms'),
            'not_found_in_trash' => \__('Not found in Trash', 'simple-lms'),
            'featured_image' => \__('Featured Image', 'simple-lms'),
            'set_featured_image' => \__('Set featured image', 'simple-lms'),
            'remove_featured_image' => \__('Remove featured image', 'simple-lms'),
            'use_featured_image' => \__('Use as featured image', 'simple-lms'),
            'insert_into_item' => \__('Insert into course', 'simple-lms'),
            'uploaded_to_this_item' => \__('Uploaded to this course', 'simple-lms'),
            'items_list' => \__('Courses list', 'simple-lms'),
            'items_list_navigation' => \__('Courses list navigation', 'simple-lms'),
            'filter_items_list' => \__('Filter courses list', 'simple-lms'),
        ];

        $args = [
            'label' => \__('Course', 'simple-lms'),
            'description' => \__('LMS Courses', 'simple-lms'),
            'labels' => $labels,
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'comments', 'revisions', 'page-attributes'],
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-welcome-learn-more',
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => 'post',
            'show_in_rest' => true,
            'rest_base' => 'courses',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'rewrite' => ['slug' => 'courses', 'with_front' => false],
        ];

        try {
            \register_post_type('course', $args);
            if ($this->logger) {
                $this->logger->info('Registered post type course');
            }
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('Failed to register post type course: {error}', ['error' => $e]);
            }
        }
        $this->postTypes['course'] = $args;
    }

    /**
     * Register Module post type
     *
     * @return void
     */
    private function registerModulePostType(): void
    {
        $labels = [
            'name' => \_x('Modules', 'Post Type General Name', 'simple-lms'),
            'singular_name' => \_x('Module', 'Post Type Singular Name', 'simple-lms'),
            'menu_name' => \__('Modules', 'simple-lms'),
            'name_admin_bar' => \__('Module', 'simple-lms'),
            'archives' => \__('Module Archives', 'simple-lms'),
            'attributes' => \__('Module Attributes', 'simple-lms'),
            'parent_item_colon' => \__('Parent Module:', 'simple-lms'),
            'all_items' => \__('All Modules', 'simple-lms'),
            'add_new_item' => \__('Add New Module', 'simple-lms'),
            'add_new' => \__('Add New', 'simple-lms'),
            'new_item' => \__('New Module', 'simple-lms'),
            'edit_item' => \__('Edit Module', 'simple-lms'),
            'update_item' => \__('Update Module', 'simple-lms'),
            'view_item' => \__('View Module', 'simple-lms'),
            'view_items' => \__('View Modules', 'simple-lms'),
            'search_items' => \__('Search Module', 'simple-lms'),
            'not_found' => \__('Not found', 'simple-lms'),
            'not_found_in_trash' => \__('Not found in Trash', 'simple-lms'),
            'featured_image' => \__('Featured Image', 'simple-lms'),
            'set_featured_image' => \__('Set featured image', 'simple-lms'),
            'remove_featured_image' => \__('Remove featured image', 'simple-lms'),
            'use_featured_image' => \__('Use as featured image', 'simple-lms'),
            'insert_into_item' => \__('Insert into module', 'simple-lms'),
            'uploaded_to_this_item' => \__('Uploaded to this module', 'simple-lms'),
            'items_list' => \__('Modules list', 'simple-lms'),
            'items_list_navigation' => \__('Modules list navigation', 'simple-lms'),
            'filter_items_list' => \__('Filter modules list', 'simple-lms'),
        ];

        $args = [
            'label' => \__('Module', 'simple-lms'),
            'description' => \__('Course Modules', 'simple-lms'),
            'labels' => $labels,
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes'],
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=course',
            'menu_position' => 5,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => false,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => 'post',
            'show_in_rest' => true,
            'rest_base' => 'modules',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'rewrite' => ['slug' => 'modules', 'with_front' => false],
        ];

        try {
            \register_post_type('module', $args);
            if ($this->logger) {
                $this->logger->info('Registered post type module');
            }
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('Failed to register post type module: {error}', ['error' => $e]);
            }
        }
        $this->postTypes['module'] = $args;
    }

    /**
     * Register Lesson post type
     *
     * @return void
     */
    private function registerLessonPostType(): void
    {
        $labels = [
            'name' => \_x('Lessons', 'Post Type General Name', 'simple-lms'),
            'singular_name' => \_x('Lesson', 'Post Type Singular Name', 'simple-lms'),
            'menu_name' => \__('Lessons', 'simple-lms'),
            'name_admin_bar' => \__('Lesson', 'simple-lms'),
            'archives' => \__('Lesson Archives', 'simple-lms'),
            'attributes' => \__('Lesson Attributes', 'simple-lms'),
            'parent_item_colon' => \__('Parent Lesson:', 'simple-lms'),
            'all_items' => \__('All Lessons', 'simple-lms'),
            'add_new_item' => \__('Add New Lesson', 'simple-lms'),
            'add_new' => \__('Add New', 'simple-lms'),
            'new_item' => \__('New Lesson', 'simple-lms'),
            'edit_item' => \__('Edit Lesson', 'simple-lms'),
            'update_item' => \__('Update Lesson', 'simple-lms'),
            'view_item' => \__('View Lesson', 'simple-lms'),
            'view_items' => \__('View Lessons', 'simple-lms'),
            'search_items' => \__('Search Lesson', 'simple-lms'),
            'not_found' => \__('Not found', 'simple-lms'),
            'not_found_in_trash' => \__('Not found in Trash', 'simple-lms'),
            'featured_image' => \__('Featured Image', 'simple-lms'),
            'set_featured_image' => \__('Set featured image', 'simple-lms'),
            'remove_featured_image' => \__('Remove featured image', 'simple-lms'),
            'use_featured_image' => \__('Use as featured image', 'simple-lms'),
            'insert_into_item' => \__('Insert into lesson', 'simple-lms'),
            'uploaded_to_this_item' => \__('Uploaded to this lesson', 'simple-lms'),
            'items_list' => \__('Lessons list', 'simple-lms'),
            'items_list_navigation' => \__('Lessons list navigation', 'simple-lms'),
            'filter_items_list' => \__('Filter lessons list', 'simple-lms'),
        ];

        $args = [
            'label' => \__('Lesson', 'simple-lms'),
            'description' => \__('Course Lessons', 'simple-lms'),
            'labels' => $labels,
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'comments', 'revisions', 'page-attributes'],
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=course',
            'menu_position' => 5,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => false,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => 'post',
            'show_in_rest' => true,
            'rest_base' => 'lessons',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'rewrite' => ['slug' => 'lessons', 'with_front' => false],
        ];

        try {
            \register_post_type('lesson', $args);
            if ($this->logger) {
                $this->logger->info('Registered post type lesson');
            }
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('Failed to register post type lesson: {error}', ['error' => $e]);
            }
        }
        $this->postTypes['lesson'] = $args;
    }

    /**
     * Get all registered post types
     *
     * @return array<string, array>
     */
    public function getPostTypes(): array
    {
        return $this->postTypes;
    }

    /**
     * Check if a post type is registered
     *
     * @param string $postType Post type slug
     * @return bool
     */
    public function hasPostType(string $postType): bool
    {
        return isset($this->postTypes[$postType]);
    }

    /**
     * Flush rewrite rules (should be called on activation)
     *
     * @return void
     */
    public function flushRewrites(): void
    {
        try {
            $this->registerPostTypes();
            \flush_rewrite_rules();
            if ($this->logger) {
                $this->logger->notice('Flushed rewrite rules after CPT registration');
            }
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('Failed to flush rewrite rules: {error}', ['error' => $e]);
            }
        }
    }
}
