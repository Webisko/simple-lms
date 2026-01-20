<?php
namespace SimpleLMS;

/**
 * Performance optimization: Uses batch loading to reduce database queries.
 * For a course with 10 modules and 10 lessons each:
 * - Before: 1 + 10 + 100 = 111 queries (1 for modules, 1 per module for lessons)
 * - After: 1 + 1 = 2 queries (1 for modules, 1 for all lessons with IN clause)
 * This represents a 98% reduction in database queries.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Meta boxes handler class
 */
class Meta_Boxes {
    /**
     * Hook manager
     *
     * @var Managers\HookManager
     */
    private Managers\HookManager $hookManager;

    /**
     * Constructor - register hooks via HookManager
     *
     * @param Managers\HookManager $hookManager Hook manager instance
     */
    public function __construct(Managers\HookManager $hookManager)
    {
        $this->hookManager = $hookManager;
        $this->registerHooks();
    }

    /**
     * Register WordPress hooks
     *
     * @return void
     */
    private function registerHooks(): void
    {
        $this->hookManager
            ->addAction('add_meta_boxes', [$this, 'register_meta_boxes'])
            ->addAction('init', [$this, 'register_post_meta'])
            ->addAction('save_post', [$this, 'save_post_meta'])
            ->addAction('add_meta_boxes', [$this, 'control_discussion_meta_box'], 10, 2)
            ->addAction('save_post', [$this, 'update_lesson_comment_status'])
            ->addAction('add_meta_boxes', [$this, 'hide_tags_metabox'], 999)
            ->addAction('add_meta_boxes', [$this, 'customize_course_editor'], 1)
            ->addAction('add_meta_boxes', [$this, 'remove_lesson_metaboxes'], 2)
            ->addAction('wp_ajax_generate_video_preview', [$this, 'ajax_generate_video_preview'])
            ->addAction('wp_ajax_save_lesson_attachments', [$this, 'ajax_save_lesson_attachments'])
            ->addAction('edit_form_after_title', [$this, 'render_fixed_course_elements'])
            ->addAction('edit_form_after_title', [$this, 'render_fixed_module_elements']);
    }

    /**
     * Legacy static init for backward compatibility
     * 
     * @deprecated Use dependency injection instead
     */
    public function init(): void
    {
        // No-op - left for backward compatibility
        // Initialization now handled via constructor
    }

    /**
     * Register meta boxes
     */
    public function register_meta_boxes(): void
    {
        // Course basic information
        // Course basic info (sidebar)
        add_meta_box(
            'course_basic_info',
            __('Settings kursu', 'simple-lms'),
            [$this, 'render_course_basic_info_meta_box'],
            'course',
            'side',
            'default'
        );

        // Note: Course information and structure are now rendered as fixed elements, not metaboxes
        
        // Add CSS and JS to make metaboxes non-collapsible
        add_action('admin_footer', [$this, 'add_metabox_styling']);

        // Module parent course
        add_meta_box(
            'module_parent_course',
            __('Parent Course', 'simple-lms'),
            [$this, 'render_parent_course_meta_box'],
            'module',
            'side',
            'default'
        );

        // Module drip schedule (sidebar)
        add_meta_box(
            'module_drip_schedule',
            __('Access Schedule', 'simple-lms'),
            [$this, 'render_module_drip_schedule_meta_box'],
            'module',
            'side',
            'default'
        );

        // Note: Lesson video and attachments are now rendered as fixed elements, not metaboxes

        // Lesson parent module (metabox in sidebar)
        add_meta_box(
            'zzz_lesson_parent_module',
            __('Parent Module', 'simple-lms'),
            [$this, 'render_parent_module_meta_box'],
            'lesson',
            'side',
            'default'
        );
        
        // Lesson details (metabox in main area)
        add_meta_box(
            'lesson_details',
            __('Lesson details', 'simple-lms'),
            [$this, 'render_lesson_details_meta_box'],
            'lesson',
            'normal',
            'high'
        );

        // Force course metabox order
        add_action('add_meta_boxes_course', [$this, 'reorder_course_metaboxes'], 999);
        add_action('admin_head', [$this, 'force_course_metabox_order']);
        
        // Add fixed course elements (non-metabox sections)
        add_action('edit_form_after_title', [$this, 'render_fixed_course_elements']);
        
        // Add fixed module elements (non-metabox sections)
        add_action('edit_form_after_title', [$this, 'render_fixed_module_elements']);
        
        // Save lesson data
        add_action('save_post', [$this, 'save_lesson_data'], 20);

        // Note: Module hierarchy is now rendered as fixed element, not metabox

        // Remove the 'lesson_video_and_files' meta box
        remove_meta_box('lesson_video_and_files', 'lesson', 'normal');
    }

    /**
     * Hide tags metabox for our custom post types
     * Tags are managed automatically by the plugin
     */
    public function hide_tags_metabox() {
        // Remove tags metabox for course, module, and lesson post types
        remove_meta_box('tagsdiv-post_tag', 'course', 'side');
        remove_meta_box('tagsdiv-post_tag', 'module', 'side');
        remove_meta_box('tagsdiv-post_tag', 'lesson', 'side');
        
        // Also remove from normal and advanced contexts just in case
        remove_meta_box('tagsdiv-post_tag', 'course', 'normal');
        remove_meta_box('tagsdiv-post_tag', 'module', 'normal');
        remove_meta_box('tagsdiv-post_tag', 'lesson', 'normal');
        
        remove_meta_box('tagsdiv-post_tag', 'course', 'advanced');
        remove_meta_box('tagsdiv-post_tag', 'module', 'advanced');
        remove_meta_box('tagsdiv-post_tag', 'lesson', 'advanced');
        
        // Remove featured image metabox for lessons (now handled in fixed elements)
        remove_meta_box('postimagediv', 'lesson', 'side');
    }

    /**
     * Register post meta
     */
    public function register_post_meta() {
        register_post_meta('module', 'parent_course', [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'integer',
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);

        register_post_meta('lesson', 'parent_module', [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'integer',
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);

        // Legacy meta 'course_roles' removed – tag-based access now via user_meta.

        // Lesson video meta
        register_post_meta('lesson', 'lesson_video_type', [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);

        register_post_meta('lesson', 'lesson_video_url', [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);

        register_post_meta('lesson', 'lesson_video_file_id', [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'integer',
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);

        // Lesson attachments meta
        register_post_meta('lesson', 'lesson_attachments', [
            'show_in_rest'  => [
                'schema' => [
                    'type'  => 'array',
                    'items' => [
                        'type' => 'integer'
                    ]
                ]
            ],
            'single'        => true,
            'type'          => 'array',
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);

        // Drip schedule meta (course)
        register_post_meta('course', '_access_schedule_mode', [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'auth_callback' => function() { return current_user_can('edit_posts'); }
        ]);
        register_post_meta('course', '_access_fixed_date', [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'auth_callback' => function() { return current_user_can('edit_posts'); }
        ]);
        register_post_meta('course', '_drip_strategy', [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'auth_callback' => function() { return current_user_can('edit_posts'); }
        ]);
        register_post_meta('course', '_drip_interval_days', [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'integer',
            'auth_callback' => function() { return current_user_can('edit_posts'); }
        ]);

        // Drip schedule meta (module)
        register_post_meta('module', '_module_drip_days', [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'integer',
            'auth_callback' => function() { return current_user_can('edit_posts'); }
        ]);
        register_post_meta('module', '_module_drip_mode', [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'auth_callback' => function() { return current_user_can('edit_posts'); }
        ]);
        register_post_meta('module', '_module_manual_unlocked', [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'boolean',
            'auth_callback' => function() { return current_user_can('edit_posts'); }
        ]);
    }

    /**
     * Render parent course meta box
     */
    public function render_parent_course_meta_box($post) {
        $parent_course = get_post_meta($post->ID, 'parent_course', true);
        if ($parent_course) {
            $course = get_post($parent_course);
            if ($course) {
                $course_edit_link = get_edit_post_link($parent_course);
                ?>
                <p><?php esc_html_e('This module belongs to course:', 'simple-lms'); ?></p>
                <p><a href="<?php echo esc_url($course_edit_link); ?>" class="parent-course-link">
                    <?php echo esc_html($course->post_title); ?>
                </a></p>
                <?php
            }
        } else {
            ?>
            <p><?php esc_html_e('This module is not assigned to any course.', 'simple-lms'); ?></p>
            <?php
        }
    }

    /**
     * Render parent module meta box
     */
    public function render_parent_module_meta_box($post) {
        $parent_module = get_post_meta($post->ID, 'parent_module', true);
        if ($parent_module) {
            $module = get_post($parent_module);
            if ($module) {
                $module_edit_link = get_edit_post_link($parent_module);
                ?>
                <p><?php esc_html_e('This lesson belongs to module:', 'simple-lms'); ?></p>
                <p><a href="<?php echo esc_url($module_edit_link); ?>" class="parent-module-link">
                    <?php echo esc_html($module->post_title); ?>
                </a></p>
                <?php
            }
        } else {
            ?>
            <p><?php esc_html_e('This lesson is not assigned to any module.', 'simple-lms'); ?></p>
            <?php
        }
    }

    /**
     * Render module drip schedule metabox (sidebar)
     */
    public function render_module_drip_schedule_meta_box($post) {
        $parent_course = get_post_meta($post->ID, 'parent_course', true);
        if (!$parent_course) {
            echo '<p>' . esc_html__('Set parent course first.', 'simple-lms') . '</p>';
            return;
        }
        $mode = get_post_meta((int)$parent_course, '_access_schedule_mode', true);
        $strategy = get_post_meta((int)$parent_course, '_drip_strategy', true);
        if ($mode !== 'drip' || $strategy !== 'per_module') {
            echo '<p>' . esc_html__('Module schedule is available when course has: Gradually → Each module independently.', 'simple-lms') . '</p>';
            return;
        }
        $drip_days = (int) get_post_meta($post->ID, '_module_drip_days', true);
        $saved_mode = get_post_meta($post->ID, '_module_drip_mode', true);
        $mode = $saved_mode ?: (($drip_days === 0) ? 'now' : 'days');
        $manual_unlocked = (bool) get_post_meta($post->ID, '_module_manual_unlocked', true);

        echo '<p style="margin-bottom:8px;">' . esc_html__('Days counted from the moment of gaining access to the course (purchase/role).', 'simple-lms') . '</p>';
        echo '<label style="display:block; margin-bottom:6px;">'
            . '<input type="radio" name="_module_drip_mode" value="now" ' . checked($mode, 'now', false) . '> '
            . esc_html__('Available immediately', 'simple-lms') . '</label>';
        echo '<label style="display:block; margin-bottom:6px;">'
            . '<input type="radio" name="_module_drip_mode" value="days" ' . checked($mode, 'days', false) . '> '
            . esc_html__('After number of days', 'simple-lms') . '</label>';
        echo '<div id="simple-lms-module-days" style="margin:6px 0 8px 22px; ' . ($mode === 'days' ? 'display:block;' : 'display:none;') . '">';
        echo '<input type="number" min="0" step="1" style="width:90px;" name="_module_drip_days" value="' . esc_attr($drip_days ?: 0) . '" /> ' . esc_html__('days', 'simple-lms');
        echo '</div>';

        echo '<label style="display:block; margin-bottom:6px;">'
            . '<input type="radio" name="_module_drip_mode" value="manual" ' . checked($mode, 'manual', false) . '> '
            . esc_html__('Manually', 'simple-lms') . '</label>';
        echo '<div id="simple-lms-module-manual" style="margin:6px 0 0 22px; ' . ($mode === 'manual' ? 'display:block;' : 'display:none;') . '">';
        echo '<label style="display:inline-block; margin-right:10px;">'
            . '<input type="radio" name="_module_manual_unlocked" value="0" ' . checked(!$manual_unlocked, true, false) . '> ' . esc_html__('locked', 'simple-lms') . '</label>';
        echo '<label style="display:inline-block;">'
            . '<input type="radio" name="_module_manual_unlocked" value="1" ' . checked($manual_unlocked, true, false) . '> ' . esc_html__('unlocked', 'simple-lms') . '</label>';
        echo '</div>';
        // JS for toggle moved to admin-script.js
    }

    /**
     * Render course settings meta box
     */
    public function render_course_settings_meta_box($post) {
        wp_nonce_field('course_settings_nonce', 'course_settings_nonce');
        
        $allow_comments = get_post_meta($post->ID, 'allow_comments', true);

        echo '<h3>' . esc_html__('Options', 'simple-lms') . '</h3>';
        echo '<label>
                <input type="checkbox" name="allow_comments" value="1" ' . checked($allow_comments, true, false) . '>
                ' . esc_html__('Allow comments in lessons', 'simple-lms') . '
              </label><br><br>';

        echo '<h3>' . esc_html__('Course Access', 'simple-lms') . '</h3>';
        echo '<p>' . esc_html__('Course access is managed automatically by WooCommerce integration.', 'simple-lms') . '</p>';
        echo '<p>' . esc_html__('Users receive access after purchasing a product linked to this course.', 'simple-lms') . '</p>';
        
        // Show users with access (tag-based)
        echo '<h4>' . esc_html__('Users with access:', 'simple-lms') . '</h4>';
        $users_with_access = self::get_users_with_course_access($post->ID);
        if (!empty($users_with_access)) {
            echo '<ul style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; Padding: 10px;">';
            foreach ($users_with_access as $user) {
                echo '<li>' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</li>';
            }
            echo '</ul>';
        } else {
            echo '<p><em>' . esc_html__('No users with access to this course.', 'simple-lms') . '</em></p>';
        }
    }
    
    /**
     * Get users with access to a course via user_meta tags
     */
    private static function get_users_with_course_access($course_id) {
        $meta_key = 'simple_lms_course_access';
        $users = get_users([
            'meta_key' => $meta_key,
            'meta_compare' => 'EXISTS',
        ]);
        
        $users_with_access = [];
        foreach ($users as $user) {
            $access = (array) get_user_meta($user->ID, $meta_key, true);
            if (in_array($course_id, $access, true)) {
                $users_with_access[] = $user;
            }
        }
        
        return $users_with_access;
    }

    /**
     * Render course hierarchy
     */
    public function render_course_hierarchy($post) {
        // Delegate to unified renderer to avoid duplication
        self::render_course_structure_content($post);
    }

    /**
     * Render module hierarchy
     */
    public function render_module_hierarchy($post) {
        // Delegate to unified renderer to avoid duplication
        self::render_module_hierarchy_content($post);
    }

    /**
     * Render modules list
     */
    private static function render_modules_list($modules, $lessons_by_module = []) {
        echo '<ul class="course-modules-list">';
        foreach ($modules as $module) {
            $module_lessons = isset($lessons_by_module[$module->ID]) ? $lessons_by_module[$module->ID] : [];
            self::render_module_item($module, $module_lessons);
        }
        echo '</ul>';
    }

    /**
     * Render module item
     */
    private static function render_module_item($module, $lessons = []) {
        $module_id = $module->ID;
        $lesson_count = count($lessons);
        
        echo '<li class="module-item" id="module-item-' . esc_attr($module_id) . '" data-module-id="' . esc_attr($module_id) . '">';
        echo '<div class="module-header">';
        echo '<span class="module-drag-handle"></span>';
        echo '<span class="module-title">';
        echo '<span class="module-toggle-container">';
        echo '<span class="module-toggle chevron-down" data-module-id="' . esc_attr($module_id) . '"></span>';
        echo '</span>';
        echo '<a href="' . esc_url(get_edit_post_link($module_id)) . '" class="module-title-link">' . esc_html($module->post_title) . '</a>';
        echo '<span class="module-lesson-count"> (' . esc_html(LmsShortcodes::getLessonsCountText($lesson_count)) . ')</span>';
        echo '</span>';
        self::render_module_actions((int) $module_id, $module->post_status === 'publish');
        echo '</div>';

        self::render_module_lessons_container($lessons, (int) $module_id);

        self::render_add_lesson_form($module_id);
        echo '</li>';
    }

    /**
     * Render lessons list
     */
    private static function render_lessons_list($lessons) {
        echo '<ul class="module-lessons-list">';
        foreach ($lessons as $lesson) {
            self::render_lesson_item($lesson);
        }
        echo '</ul>';
    }

    /**
     * Render lesson item
     */
    private static function render_lesson_item($lesson) {
        echo '<li class="lesson-item" id="lesson-item-' . esc_attr($lesson->ID) . '" data-lesson-id="' . esc_attr($lesson->ID) . '">';
        echo '<span class="lesson-drag-handle"></span>';
        echo '<a href="' . esc_url(get_edit_post_link($lesson->ID)) . '" class="lesson-title-link">' . 
             esc_html($lesson->post_title) . '</a>';
           self::render_lesson_actions((int) $lesson->ID, $lesson->post_status === 'publish');
        echo '</li>';
    }

    /**
     * Render add lesson form
     */
    private static function render_add_lesson_form($module_id) {
        echo '<div class="add-lesson-form">';
        echo '<h3 class="add-lesson-heading">' . esc_html__('Add Lesson', 'simple-lms') . '</h3>';
        echo '<input type="text" name="new_lesson_title_' . esc_attr($module_id) . 
             '" placeholder="' . esc_attr__('Lesson Title', 'simple-lms') . '" class="widefat" />';
        echo '<button type="button" class="button button-primary add-lessons-btn" data-module-id="' . 
             esc_attr($module_id) . '">' . esc_html__('Add Lesson', 'simple-lms') . '</button>';
        echo '</div>';
    }

    /**
     * Render module actions (status toggle, duplicate, delete)
     */
    private static function render_module_actions(int $module_id, bool $isPublished): void {
        echo '<div class="module-actions">';
        echo '<div class="module-status-toggle">';
        self::render_status_toggle(
            __('Opublikowany', 'simple-lms'),
            __('Wersja robocza', 'simple-lms'),
            $isPublished,
            $module_id,
            'module'
        );
        echo '</div>';
        echo '<a href="#" class="duplicate-module" data-module-id="' . esc_attr($module_id) . '">' . 
             esc_html__('Duplicate', 'simple-lms') . '</a>';
        echo '<a href="#" class="delete-module delete-button" data-module-id="' . esc_attr($module_id) . '">' . 
             esc_html__('Delete', 'simple-lms') . '</a>';
        echo '</div>';
    }

    /**
     * Render lessons container for a module (UL with items or empty state)
     */
    private static function render_module_lessons_container(array $lessons, int $module_id): void {
        if ($lessons) {
            echo '<ul class="module-lessons-list visible" id="module-lessons-' . esc_attr($module_id) . '">';
            foreach ($lessons as $lesson) {
                self::render_lesson_item($lesson);
            }
            echo '</ul>';
        } else {
            echo '<ul class="module-lessons-list visible" id="module-lessons-' . esc_attr($module_id) . '" style="display: none;">';
            echo '<li class="lessons-empty">' . esc_html__('No lessons in this module.', 'simple-lms') . '</li>';
            echo '</ul>';
        }
    }

    /**
     * Render lesson actions (status toggle, duplicate, delete)
     */
    private static function render_lesson_actions(int $lesson_id, bool $isPublished): void {
        echo '<div class="lesson-actions">';
        
        // Preview button
        $lesson_url = get_permalink($lesson_id);
        if ($lesson_url) {
            echo '<a href="' . esc_url($lesson_url) . '" class="button lesson-preview-button" target="_blank" style="font-weight: bold;">' . 
                 esc_html__('Podgląd', 'simple-lms') . '</a>';
        }
        
        echo '<div class="lesson-status-toggle">';
        self::render_status_toggle(
            __('Opublikowany', 'simple-lms'),
            __('Wersja robocza', 'simple-lms'),
            $isPublished,
            $lesson_id,
            'lesson'
        );
        echo '</div>';
        echo '<a href="#" class="duplicate-lesson" data-lesson-id="' . esc_attr($lesson_id) . '">' . 
             esc_html__('Duplicate', 'simple-lms') . '</a>';
        echo '<a href="#" class="delete-lesson delete-button" data-lesson-id="' . esc_attr($lesson_id) . '">' . 
             esc_html__('Delete', 'simple-lms') . '</a>';
        echo '</div>';
    }

    /**
     * Render a standardized status toggle
     *
     * @param string $labelPublished Label when published
     * @param string $labelDraft Label when draft
     * @param bool $isPublished Current published state
     * @param int $objectId Post ID for data attribute
     * @param string $type Type identifier for data-type attribute (module|lesson)
     * @return void
     */
    private static function render_status_toggle(string $labelPublished, string $labelDraft, bool $isPublished, int $objectId, string $type): void {
        echo '<span class="status-label" data-status="' . ($isPublished ? 'published' : 'draft') . '">' . esc_html($isPublished ? $labelPublished : $labelDraft) . '</span>';
        echo '<label class="toggle-switch">';
        echo '<input type="checkbox" ' . checked($isPublished, true, false) . ' class="toggle-input" data-id="' . esc_attr((string) $objectId) . '" data-type="' . esc_attr($type) . '">';
        echo '<span class="slider"></span>';
        echo '</label>';
    }

    /**
     * Save post meta
     */
    public function save_post_meta($post_id) {
        static $recursion_guard = false;
        if ($recursion_guard) {
            error_log('SimpleLMS save_post_meta: Recursion guard triggered for post_id ' . $post_id);
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            error_log('SimpleLMS save_post_meta: Autosave detected for post_id ' . $post_id);
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            error_log('SimpleLMS save_post_meta: User cannot edit post ' . $post_id);
            return;
        }

        // Skip Elementor templates and library posts
        $post_type = get_post_type($post_id);
        error_log('SimpleLMS save_post_meta: Processing post_id ' . $post_id . ' of type: ' . $post_type);
        
        if (in_array($post_type, ['elementor_library', 'elementor_snippet', 'e-landing-page'], true)) {
            error_log('SimpleLMS save_post_meta: Skipping Elementor template/library post ' . $post_id);
            return;
        }

        // Skip if this is an Elementor Ajax save
        if (defined('ELEMENTOR_VERSION') && !empty($_POST['actions'])) {
            error_log('SimpleLMS save_post_meta: Skipping Elementor Ajax save for post ' . $post_id);
            return;
        }

        error_log('SimpleLMS save_post_meta: Proceeding with save for post ' . $post_id . ' (' . $post_type . ')');
        $recursion_guard = true;

        // Handle course basic info
        if ($post_type === 'course' && 
            isset($_POST['course_basic_info_nonce']) && 
            wp_verify_nonce($_POST['course_basic_info_nonce'], 'course_basic_info_nonce')) {
            
            if (isset($_POST['course_short_description'])) {
                $short_description = wp_kses_post($_POST['course_short_description']);
                update_post_meta($post_id, '_course_short_description', $short_description);
            }
        }

        if ($post_type === 'course' && 
            isset($_POST['course_settings_nonce']) && 
            wp_verify_nonce($_POST['course_settings_nonce'], 'course_settings_nonce')) {
            
            // REMOVED: course_roles - access is now tag-based via user_meta
            
            // Handle allow_comments
            $allow_comments_value = isset($_POST['allow_comments']) ? true : false;
            update_post_meta($post_id, 'allow_comments', $allow_comments_value);

            // Save access duration
            if (isset($_POST['_access_duration_value'])) {
                $duration_value = max(0, intval($_POST['_access_duration_value']));
                update_post_meta($post_id, '_access_duration_value', $duration_value);
            }
            if (isset($_POST['_access_duration_unit'])) {
                $duration_unit = sanitize_text_field($_POST['_access_duration_unit']);
                if (!in_array($duration_unit, ['days', 'weeks', 'months', 'years'], true)) {
                    $duration_unit = 'days';
                }
                update_post_meta($post_id, '_access_duration_unit', $duration_unit);
            }

            // Save access schedule (course)
            $access_mode = isset($_POST['_access_schedule_mode']) ? sanitize_text_field($_POST['_access_schedule_mode']) : get_post_meta($post_id, '_access_schedule_mode', true);
            if (!in_array($access_mode, ['purchase','fixed_date','drip'], true)) {
                $access_mode = 'purchase';
            }
            update_post_meta($post_id, '_access_schedule_mode', $access_mode);

            if ($access_mode === 'fixed_date') {
                $fixed_date = isset($_POST['_access_fixed_date']) ? sanitize_text_field($_POST['_access_fixed_date']) : '';
                update_post_meta($post_id, '_access_fixed_date', $fixed_date);
            } else {
                delete_post_meta($post_id, '_access_fixed_date');
            }

            if ($access_mode === 'drip') {
                $drip_strategy = isset($_POST['_drip_strategy']) ? sanitize_text_field($_POST['_drip_strategy']) : 'interval';
                if (!in_array($drip_strategy, ['interval','per_module'], true)) {
                    $drip_strategy = 'interval';
                }
                update_post_meta($post_id, '_drip_strategy', $drip_strategy);
                if ($drip_strategy === 'interval') {
                    $interval_days = isset($_POST['_drip_interval_days']) ? intval($_POST['_drip_interval_days']) : 0;
                    update_post_meta($post_id, '_drip_interval_days', max(0, $interval_days));
                } else {
                    delete_post_meta($post_id, '_drip_interval_days');
                }
            } else {
                delete_post_meta($post_id, '_drip_strategy');
                delete_post_meta($post_id, '_drip_interval_days');
            }
        }

        // Handle lesson video meta
        if ($post_type === 'lesson' && 
            isset($_POST['simple_lms_lesson_video_nonce']) && 
            wp_verify_nonce($_POST['simple_lms_lesson_video_nonce'], 'simple_lms_lesson_video_meta')) {
            
            $video_type = sanitize_text_field($_POST['lesson_video_type'] ?? 'none');
            update_post_meta($post_id, 'lesson_video_type', $video_type);
            
            if (in_array($video_type, ['youtube', 'vimeo', 'url'])) {
                $video_url = esc_url_raw($_POST['lesson_video_url'] ?? '');
                update_post_meta($post_id, 'lesson_video_url', $video_url);
                delete_post_meta($post_id, 'lesson_video_file_id');
            } elseif ($video_type === 'file') {
                $video_file_id = absint($_POST['lesson_video_file_id'] ?? 0);
                update_post_meta($post_id, 'lesson_video_file_id', $video_file_id);
                delete_post_meta($post_id, 'lesson_video_url');
            } else {
                delete_post_meta($post_id, 'lesson_video_url');
                delete_post_meta($post_id, 'lesson_video_file_id');
            }
        }

        // Handle lesson attachments meta
        if ($post_type === 'lesson' && 
            isset($_POST['simple_lms_lesson_attachments_nonce']) && 
            wp_verify_nonce($_POST['simple_lms_lesson_attachments_nonce'], 'simple_lms_lesson_attachments_meta')) {
            
            $attachments = [];
            if (isset($_POST['lesson_attachments']) && is_array($_POST['lesson_attachments'])) {
                foreach ($_POST['lesson_attachments'] as $attachment_id) {
                    $attachment_id = absint($attachment_id);
                    if ($attachment_id > 0) {
                        $attachments[] = $attachment_id;
                    }
                }
            }
            update_post_meta($post_id, 'lesson_attachments', $attachments);
        }

        // Save course schedule also when only the sidebar metabox was used
        if ($post_type === 'course' && 
            isset($_POST['course_basic_info_nonce']) && 
            wp_verify_nonce($_POST['course_basic_info_nonce'], 'course_basic_info_nonce')) {
            
            // Save access duration
            if (isset($_POST['_access_duration_value'])) {
                $duration_value = max(0, intval($_POST['_access_duration_value']));
                update_post_meta($post_id, '_access_duration_value', $duration_value);
            }
            if (isset($_POST['_access_duration_unit'])) {
                $duration_unit = sanitize_text_field($_POST['_access_duration_unit']);
                if (!in_array($duration_unit, ['days', 'weeks', 'months', 'years'], true)) {
                    $duration_unit = 'days';
                }
                update_post_meta($post_id, '_access_duration_unit', $duration_unit);
            }
            
            if (isset($_POST['_access_schedule_mode'])) {
                $access_mode = sanitize_text_field($_POST['_access_schedule_mode']);
                if (!in_array($access_mode, ['purchase','fixed_date','drip'], true)) {
                    $access_mode = 'purchase';
                }
                update_post_meta($post_id, '_access_schedule_mode', $access_mode);
                if ($access_mode === 'fixed_date') {
                    update_post_meta($post_id, '_access_fixed_date', sanitize_text_field($_POST['_access_fixed_date'] ?? ''));
                } else {
                    delete_post_meta($post_id, '_access_fixed_date');
                }
                if ($access_mode === 'drip') {
                    $drip_strategy = sanitize_text_field($_POST['_drip_strategy'] ?? 'interval');
                    if (!in_array($drip_strategy, ['interval','per_module'], true)) {
                        $drip_strategy = 'interval';
                    }
                    update_post_meta($post_id, '_drip_strategy', $drip_strategy);
                    if ($drip_strategy === 'interval') {
                        $interval_days = intval($_POST['_drip_interval_days'] ?? 0);
                        update_post_meta($post_id, '_drip_interval_days', max(0, $interval_days));
                    } else {
                        delete_post_meta($post_id, '_drip_interval_days');
                    }
                } else {
                    delete_post_meta($post_id, '_drip_strategy');
                    delete_post_meta($post_id, '_drip_interval_days');
                }
            }
        }

        // Save module drip meta
        if ($post_type === 'module') {
            if (isset($_POST['_module_drip_mode'])) {
                $mode = sanitize_text_field($_POST['_module_drip_mode']);
                if (!in_array($mode, ['now','days','manual'], true)) { $mode = 'now'; }
                update_post_meta($post_id, '_module_drip_mode', $mode);
                if ($mode === 'now') {
                    update_post_meta($post_id, '_module_drip_days', 0);
                    delete_post_meta($post_id, '_module_manual_unlocked');
                } elseif ($mode === 'days') {
                    $days = intval($_POST['_module_drip_days'] ?? 0);
                    update_post_meta($post_id, '_module_drip_days', max(0, $days));
                    delete_post_meta($post_id, '_module_manual_unlocked');
                } elseif ($mode === 'manual') {
                    $manual = isset($_POST['_module_manual_unlocked']) ? (int)($_POST['_module_manual_unlocked']) : 0;
                    update_post_meta($post_id, '_module_manual_unlocked', $manual ? 1 : 0);
                }
            }
        }

        $recursion_guard = false;
    }

    /**
     * Dynamically control the visibility of the discussion meta box for lessons
     */
    public function control_discussion_meta_box($post_type, $post) {
        if ($post_type === 'lesson') {
            $parent_module = get_post_meta($post->ID, 'parent_module', true);
            if ($parent_module) {
                $parent_course = get_post_meta($parent_module, 'parent_course', true);
                if ($parent_course) {
                    $allow_comments = get_post_meta($parent_course, 'allow_comments', true);
                    if (!$allow_comments) {
                        remove_meta_box('commentstatusdiv', 'lesson', 'normal');
                    }
                }
            }
        }
    }

    /**
     * Update comment status for lessons based on course settings
     */
    public function update_lesson_comment_status($post_id) {
        static $recursion_guard = false;
        if ($recursion_guard) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $post_type = get_post_type($post_id);
        
        // Skip Elementor templates and library posts
        if (in_array($post_type, ['elementor_library', 'elementor_snippet', 'e-landing-page'], true)) {
            return;
        }
        
        if ($post_type !== 'lesson') {
            return;
        }

        $recursion_guard = true;

        $parent_module = get_post_meta($post_id, 'parent_module', true);
        
        if ($parent_module) {
            $parent_course = get_post_meta($parent_module, 'parent_course', true);
            
            if ($parent_course) {
                $allow_comments = (bool) get_post_meta($parent_course, 'allow_comments', true);
                
                // Temporarily remove the action to prevent infinite loops
                remove_action('save_post', [$this, 'update_lesson_comment_status']);
                
                wp_update_post([
                    'ID' => $post_id,
                    'comment_status' => $allow_comments ? 'open' : 'closed'
                ]);
                
                // Re-add the action
                add_action('save_post', [$this, 'update_lesson_comment_status']);
            }
        }

        $recursion_guard = false;
    }

    /**
     * Generate video preview HTML
     *
     * @param string $video_type Type of video
     * @param string $video_url Video URL
     * @param string $file_url File URL (for file type)
     * @return string Preview HTML
     */
    private static function generate_video_preview(string $video_type, string $video_url, string $file_url = ''): string {
        if ($video_type === 'none' || (empty($video_url) && empty($file_url))) {
            return '';
        }

        $preview_html = '<div class="video-preview" style="margin: 0; padding: 0; border: none; background: transparent; width: 100%; max-width: 100%; overflow: hidden;">';

        if ($video_type === 'youtube' && $video_url) {
            // Extract YouTube ID with improved regex
            $youtube_id = '';
            
            // Support multiple YouTube URL formats
            if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video_url, $matches)) {
                $youtube_id = $matches[1];
            }
            
            if ($youtube_id) {
                $preview_html .= '<div style="position: relative; max-width: 100%; overflow: hidden;">';
                $preview_html .= '<iframe style="width: 100%; height: auto; aspect-ratio: 16/9;" src="https://www.youtube.com/embed/' . \esc_attr($youtube_id) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                $preview_html .= '</div>';
            } else {
                $preview_html .= '<p style="color: #d63638;">' . \__('Invalid YouTube link. Check URL format.', 'simple-lms') . '</p>';
                $preview_html .= '<p style="color: #666; font-size: 12px;">URL: ' . \esc_html($video_url) . '</p>';
            }
        } elseif ($video_type === 'vimeo' && $video_url) {
            // Extract Vimeo ID with improved regex
            $vimeo_id = '';
            
            // Support multiple Vimeo URL formats
            if (preg_match('/vimeo\.com\/(?:channels\/[^\/]+\/|groups\/[^\/]+\/videos\/|album\/[^\/]+\/video\/|video\/|)(\d+)(?:$|\/|\?)/', $video_url, $matches)) {
                $vimeo_id = $matches[1];
            }
            
            if ($vimeo_id) {
                $preview_html .= '<div style="position: relative; max-width: 100%; overflow: hidden;">';
                $preview_html .= '<iframe style="width: 100%; height: auto; aspect-ratio: 16/9;" src="https://player.vimeo.com/video/' . \esc_attr($vimeo_id) . '" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
                $preview_html .= '</div>';
            } else {
                $preview_html .= '<p style="color: #d63638;">' . \__('Invalid Vimeo link. Check URL format.', 'simple-lms') . '</p>';
                $preview_html .= '<p style="color: #666; font-size: 12px;">URL: ' . \esc_html($video_url) . '</p>';
            }
        } elseif ($video_type === 'url' && $video_url) {
            // Detect video type from URL extension
            $video_extension = strtolower(pathinfo(parse_url($video_url, PHP_URL_PATH), PATHINFO_EXTENSION));
            $mime_type = 'video/mp4'; // default
            
            switch ($video_extension) {
                case 'mp4':
                    $mime_type = 'video/mp4';
                    break;
                case 'webm':
                    $mime_type = 'video/webm';
                    break;
                case 'ogg':
                case 'ogv':
                    $mime_type = 'video/ogg';
                    break;
                case 'avi':
                    $mime_type = 'video/mp4'; // fallback for AVI
                    break;
                case 'mov':
                    $mime_type = 'video/quicktime';
                    break;
                case 'wmv':
                    $mime_type = 'video/x-ms-wmv';
                    break;
            }
            
            $preview_html .= '<video style="width: 100%; max-width: 100%; height: auto;" controls>';
            $preview_html .= '<source src="' . \esc_url($video_url) . '" type="' . esc_attr($mime_type) . '">';
            $preview_html .= \__('Your browser does not support the video element.', 'simple-lms');
            $preview_html .= '</video>';
        } elseif ($video_type === 'file' && $file_url) {
            // Detect video type from file URL extension
            $video_extension = strtolower(pathinfo(parse_url($file_url, PHP_URL_PATH), PATHINFO_EXTENSION));
            $mime_type = 'video/mp4'; // default
            
            switch ($video_extension) {
                case 'mp4':
                    $mime_type = 'video/mp4';
                    break;
                case 'webm':
                    $mime_type = 'video/webm';
                    break;
                case 'ogg':
                case 'ogv':
                    $mime_type = 'video/ogg';
                    break;
                case 'avi':
                    $mime_type = 'video/mp4'; // fallback for AVI
                    break;
                case 'mov':
                    $mime_type = 'video/quicktime';
                    break;
                case 'wmv':
                    $mime_type = 'video/x-ms-wmv';
                    break;
            }
            
            $preview_html .= '<video style="width: 100%; max-width: 100%; height: auto;" controls>';
            $preview_html .= '<source src="' . \esc_url($file_url) . '" type="' . esc_attr($mime_type) . '">';
            $preview_html .= \__('Your browser does not support the video element.', 'simple-lms');
            $preview_html .= '</video>';
        }

        $preview_html .= '</div>';
        return $preview_html;
    }

    /**
     * Render lesson video meta box
     *
     * @param \WP_Post $post Post object
     * @return void
     */
    public function render_lesson_video_meta_box(\WP_Post $post): void {
        wp_nonce_field('simple_lms_lesson_video_meta', 'simple_lms_lesson_video_nonce');
        
        $video_type = get_post_meta($post->ID, 'lesson_video_type', true) ?: 'none';
        $video_url = get_post_meta($post->ID, 'lesson_video_url', true);
        $video_file_id = get_post_meta($post->ID, 'lesson_video_file_id', true);
        
        echo '<div class="lesson-video-settings">';
        
        // Video type selector
        echo '<p><strong>' . __('Typ filmu:', 'simple-lms') . '</strong></p>';
        echo '<label><input type="radio" name="lesson_video_type" value="none"' . checked($video_type, 'none', false) . '> ' . __('Brak filmu', 'simple-lms') . '</label><br>';
        echo '<label><input type="radio" name="lesson_video_type" value="youtube"' . checked($video_type, 'youtube', false) . '> ' . __('YouTube', 'simple-lms') . '</label><br>';
        echo '<label><input type="radio" name="lesson_video_type" value="vimeo"' . checked($video_type, 'vimeo', false) . '> ' . __('Vimeo', 'simple-lms') . '</label><br>';
        echo '<label><input type="radio" name="lesson_video_type" value="url"' . checked($video_type, 'url', false) . '> ' . __('Link URL (other source)', 'simple-lms') . '</label><br>';
        echo '<label><input type="radio" name="lesson_video_type" value="file"' . checked($video_type, 'file', false) . '> ' . __('File from media library', 'simple-lms') . '</label><br>';
        
        // URL input (shown for youtube, vimeo, and url types)
        $show_url_input = in_array($video_type, ['youtube', 'vimeo', 'url']);
        echo '<div class="video-url-section" style="' . (!$show_url_input ? 'display:none;' : '') . 'margin-top:10px;">';
        echo '<p><label for="lesson_video_url"><strong>' . __('URL filmu:', 'simple-lms') . '</strong></label></p>';
        echo '<input type="url" id="lesson_video_url" name="lesson_video_url" value="' . esc_attr($video_url) . '" class="widefat" placeholder="https://www.youtube.com/..." />';
        
        // Show video preview for URL types
        if ($show_url_input && $video_url) {
            echo self::generate_video_preview($video_type, $video_url);
        }
        
        echo '</div>';
        
        // File selector
        echo '<div class="video-file-section" style="' . ($video_type !== 'file' ? 'display:none;' : '') . 'margin-top:10px;">';
        echo '<p><strong>' . __('File from media library:', 'simple-lms') . '</strong></p>';
        
        $file_url = '';
        if ($video_file_id) {
            $file_url = wp_get_attachment_url($video_file_id);
        }
        
        echo '<div class="video-file-preview">';
        if ($file_url) {
            echo '<p>' . __('Wybrany plik:', 'simple-lms') . ' <a href="' . esc_url($file_url) . '" target="_blank">' . esc_html(basename($file_url)) . '</a></p>';
        } else {
            echo '<p>' . __('Brak wybranego pliku', 'simple-lms') . '</p>';
        }
        echo '</div>';
        
        echo '<input type="hidden" id="lesson_video_file_id" name="lesson_video_file_id" value="' . esc_attr($video_file_id) . '" />';
        echo '<button type="button" class="button" id="select-video-file">' . __('Wybierz plik wideo', 'simple-lms') . '</button> ';
        echo '<button type="button" class="button" id="remove-video-file"' . (!$video_file_id ? ' style="display:none;"' : '') . '>' . __('Delete', 'simple-lms') . '</button>';
        
        // Show video preview for file type
        if ($video_type === 'file' && $file_url) {
            echo self::generate_video_preview($video_type, '', $file_url);
        }
        
        echo '</div>';
        
        echo '</div>';
        
        // Video type JS moved to admin-script.js
    }

    /**
     * Force specific order for course metaboxes in sidebar
     */
    public function reorder_course_metaboxes() {
        global $wp_meta_boxes;
        
        if (isset($wp_meta_boxes['course']['side'])) {
            // Get current metaboxes
            $side_boxes = $wp_meta_boxes['course']['side'];
            
            // Create new order - force submitdiv first
            $new_order = [];
            
            // 1. Force submitdiv (Opublikuj) first
            if (isset($side_boxes['core']['submitdiv'])) {
                $new_order['core']['submitdiv'] = $side_boxes['core']['submitdiv'];
            } elseif (isset($side_boxes['high']['submitdiv'])) {
                if (!isset($new_order['core'])) $new_order['core'] = [];
                $new_order['core']['submitdiv'] = $side_boxes['high']['submitdiv'];
                unset($side_boxes['high']['submitdiv']);
            } elseif (isset($side_boxes['default']['submitdiv'])) {
                if (!isset($new_order['core'])) $new_order['core'] = [];
                $new_order['core']['submitdiv'] = $side_boxes['default']['submitdiv'];
                unset($side_boxes['default']['submitdiv']);
            }
            
            // 2. Then add our Settings kursu (course_basic_info)
            if (isset($side_boxes['high']['course_basic_info'])) {
                if (!isset($new_order['high'])) $new_order['high'] = [];
                $new_order['high']['course_basic_info'] = $side_boxes['high']['course_basic_info'];
                unset($side_boxes['high']['course_basic_info']);
            }
            
            // 3. Add remaining high priority boxes
            if (isset($side_boxes['high']) && !empty($side_boxes['high'])) {
                if (!isset($new_order['high'])) $new_order['high'] = [];
                $new_order['high'] = array_merge($new_order['high'], $side_boxes['high']);
            }
            
            // 4. Add remaining core priority boxes (except submitdiv already added)
            if (isset($side_boxes['core']) && !empty($side_boxes['core'])) {
                if (!isset($new_order['core'])) $new_order['core'] = [];
                foreach ($side_boxes['core'] as $id => $box) {
                    if ($id !== 'submitdiv') {
                        $new_order['core'][$id] = $box;
                    }
                }
            }
            
            // 5. Add default priority boxes
            if (isset($side_boxes['default'])) {
                $new_order['default'] = $side_boxes['default'];
            }
            
            // 6. Finally add low priority boxes (Products WooCommerce)
            if (isset($side_boxes['low'])) {
                $new_order['low'] = $side_boxes['low'];
            }
            
            // Update the global array
            $wp_meta_boxes['course']['side'] = $new_order;
        }
    }

    /**
     * Customize course editor - hide standard editor for courses
     */
    public function customize_course_editor() {
        global $post;
        
        if ($post && $post->post_type === 'course') {
            remove_post_type_support('course', 'editor');
            
            // Remove default featured image metabox - we use custom UI in Information tab
            remove_meta_box('postimagediv', 'course', 'side');
            
            // Remove post attributes metabox
            remove_meta_box('pageparentdiv', 'course', 'side');
            
            // Hide course settings metabox (we'll move it to tabs)
            remove_meta_box('course_settings', 'course', 'side');
            
            // Remove old course tabs metabox if exists
            remove_meta_box('course_tabs', 'course', 'normal');
        }
    }

    /**
     * Remove problematic metaboxes for lessons
     */
    public function remove_lesson_metaboxes() {
        // Remove featured image metabox for lessons (handled in fixed elements)
        remove_meta_box('postimagediv', 'lesson', 'side');
    }

    /**
     * Render course basic information meta box
     */
    public function render_course_basic_info_meta_box($post) {
        wp_nonce_field('course_basic_info_nonce', 'course_basic_info_nonce');
        
        $allow_comments = get_post_meta($post->ID, 'allow_comments', true);

        echo '<h3>' . esc_html__('Options', 'simple-lms') . '</h3>';
        echo '<label>
                <input type="checkbox" name="allow_comments" value="1" ' . checked($allow_comments, true, false) . '>
                ' . esc_html__('Allow comments in lessons', 'simple-lms') . '
              </label><br><br>';

        echo '<h3>' . esc_html__('Access', 'simple-lms') . '</h3>';
        echo '<p>' . esc_html__('Access is managed automatically by WooCommerce after product purchase.', 'simple-lms') . '</p>';

        // Access schedule section
        $access_mode = get_post_meta($post->ID, '_access_schedule_mode', true) ?: 'purchase';
        $fixed_date = get_post_meta($post->ID, '_access_fixed_date', true);
        $drip_strategy = get_post_meta($post->ID, '_drip_strategy', true) ?: 'interval';
        $drip_interval_days = (int) get_post_meta($post->ID, '_drip_interval_days', true);

        // Access duration section
        $access_duration_value = (int) get_post_meta($post->ID, '_access_duration_value', true);
        $access_duration_unit = get_post_meta($post->ID, '_access_duration_unit', true) ?: 'days';
        echo '<hr style="margin:12px 0;">';
        echo '<h3>' . esc_html__('Access Duration', 'simple-lms') . '</h3>';
        echo '<p style="margin-bottom:8px;">' . esc_html__('Specify how long after purchase the user will have access to the course. Leave 0 or empty for lifetime access.', 'simple-lms') . '</p>';
        echo '<label style="display:block; margin-bottom:12px;">';
        echo esc_html__('Access time:', 'simple-lms') . '<br>';
        echo '<input type="number" min="0" step="1" style="width:90px; margin-top:4px;" name="_access_duration_value" value="' . esc_attr($access_duration_value) . '" /> ';
        echo '<select name="_access_duration_unit" style="width:120px;">';
        echo '<option value="days"' . selected($access_duration_unit, 'days', false) . '>' . esc_html__('days', 'simple-lms') . '</option>';
        echo '<option value="weeks"' . selected($access_duration_unit, 'weeks', false) . '>' . esc_html__('weeks', 'simple-lms') . '</option>';
        echo '<option value="months"' . selected($access_duration_unit, 'months', false) . '>' . esc_html__('months', 'simple-lms') . '</option>';
        echo '<option value="years"' . selected($access_duration_unit, 'years', false) . '>' . esc_html__('years', 'simple-lms') . '</option>';
        echo '</select>';
        echo '<p class="description" style="margin-top:4px;">' . esc_html__('(0 = lifetime access)', 'simple-lms') . '</p>';
        echo '</label>';

        echo '<hr style="margin:12px 0;">';
        echo '<h3>' . esc_html__('Access Schedule', 'simple-lms') . '</h3>';
        echo '<p style="margin-bottom:8px;">' . esc_html__('Choose how to unlock access to course content.', 'simple-lms') . '</p>';

        echo '<label style="display:block; margin-bottom:6px;">'
            . '<input type="radio" name="_access_schedule_mode" value="purchase" ' . checked($access_mode, 'purchase', false) . '> '
            . esc_html__('After course purchase (default)', 'simple-lms') . '</label>';

        echo '<label style="display:block; margin-bottom:6px;">'
            . '<input type="radio" name="_access_schedule_mode" value="fixed_date" ' . checked($access_mode, 'fixed_date', false) . '> '
            . esc_html__('From specific date', 'simple-lms') . '</label>';

        echo '<div id="simple-lms-fixed-date-wrap" style="margin:6px 0 12px 22px; display:' . ($access_mode === 'fixed_date' ? 'block' : 'none') . ';">';
        echo '<input type="date" name="_access_fixed_date" value="' . esc_attr($fixed_date) . '" />';
        echo '<p class="description" style="margin-top:4px;">' . esc_html__('Date from which the course/modules will be available to all authorized users.', 'simple-lms') . '</p>';
        echo '</div>';

        echo '<label style="display:block; margin-bottom:6px;">'
            . '<input type="radio" name="_access_schedule_mode" value="drip" ' . checked($access_mode, 'drip', false) . '> '
            . esc_html__('Gradually (Drip)', 'simple-lms') . '</label>';

        echo '<div id="simple-lms-drip-wrap" style="margin:6px 0 0 22px; display:' . ($access_mode === 'drip' ? 'block' : 'none') . ';">';
        echo '<label style="display:block; margin-bottom:6px;">'
            . '<input type="radio" name="_drip_strategy" value="interval" ' . checked($drip_strategy, 'interval', false) . '> '
            . esc_html__('Each next module after X days', 'simple-lms') . '</label>';
        echo '<div id="simple-lms-drip-interval" style="margin:6px 0 12px 22px; display:' . ($drip_strategy === 'interval' ? 'block' : 'none') . ';">';
        echo '<input type="number" min="0" step="1" style="width:90px;" name="_drip_interval_days" value="' . esc_attr($drip_interval_days ?: 0) . '" /> ' . esc_html__('days', 'simple-lms');
        echo '</div>';
        echo '<label style="display:block;">'
            . '<input type="radio" name="_drip_strategy" value="per_module" ' . checked($drip_strategy, 'per_module', false) . '> '
            . esc_html__('Each module independently (set in module)', 'simple-lms') . '</label>';
        echo '</div>';

        // Schedule toggle JS moved to admin-script.js
    }

    /**

    /**
     * Render course structure content for tab
     */
    public function render_course_structure_content($post) {
        wp_nonce_field('course_hierarchy_nonce', 'course_hierarchy_nonce');

        // Batch load modules (1 query)
        $modules = get_posts([
            'post_type'      => 'module',
            'posts_per_page' => -1,
            'post_status'    => ['publish', 'draft'],
            'meta_key'       => 'parent_course',
            'meta_value'     => $post->ID,
            'orderby'        => ['menu_order' => 'ASC', 'ID' => 'ASC'],
            'order'          => 'ASC'
        ]);

        echo '<div class="course-structure">';
        
        if ($modules) {
            // Batch load all lessons for all modules (1 query instead of N queries)
            $module_ids = wp_list_pluck($modules, 'ID');
            
            if (!empty($module_ids)) {
                $all_lessons = get_posts([
                    'post_type'      => 'lesson',
                    'posts_per_page' => -1,
                    'post_status'    => ['publish', 'draft'],
                    'meta_query'     => [
                        [
                            'key'     => 'parent_module',
                            'value'   => $module_ids,
                            'compare' => 'IN'
                        ]
                    ],
                    'orderby'        => ['menu_order' => 'ASC', 'ID' => 'ASC'],
                    'order'          => 'ASC'
                ]);

                // Group lessons by parent_module in PHP (no additional queries)
                $lessons_by_module = [];
                foreach ($all_lessons as $lesson) {
                    if ($lesson instanceof \WP_Post) {
                        $parent_module = get_post_meta($lesson->ID, 'parent_module', true);
                        if (!isset($lessons_by_module[$parent_module])) {
                            $lessons_by_module[$parent_module] = [];
                        }
                        $lessons_by_module[$parent_module][] = $lesson;
                    }
                }
            } else {
                $lessons_by_module = [];
            }
            
            self::render_modules_list($modules, $lessons_by_module);
        } else {
            echo '<p>' . esc_html__('Add first module to start building the course.', 'simple-lms') . '</p>';
        }

        echo '<div class="course-add-module">';
        echo '<h3 class="add-module-heading">' . esc_html__('Add Module', 'simple-lms') . '</h3>';
        echo '<input type="text" name="new_module_title" placeholder="' . 
             esc_attr__('Module Title', 'simple-lms') . '" class="widefat" />';
        echo '<button type="button" id="add-module-btn" class="button button-primary" data-course-id="' . 
             esc_attr($post->ID) . '">' . esc_html__('Add Module', 'simple-lms') . '</button>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render course settings content for tab
     */
    public function render_course_settings_content($post) {
        wp_nonce_field('course_settings_nonce', 'course_settings_nonce');
        $allow_comments = get_post_meta($post->ID, 'allow_comments', true);
        echo '<h3>' . esc_html__('Options', 'simple-lms') . '</h3>';
        echo '<label><input type="checkbox" name="allow_comments" value="1" ' . checked($allow_comments, true, false) . '> ' . esc_html__('Allow comments in lessons', 'simple-lms') . '</label><br><br>';
        echo '<h3>' . esc_html__('Access', 'simple-lms') . '</h3>';
        echo '<p>' . esc_html__('Access do kursu nadawany automatycznie po zakupie produktu (tag user_meta).', 'simple-lms') . '</p>';
        echo '<p><em>' . esc_html__('Manual access management in user profile.', 'simple-lms') . '</em></p>';
    }
    
    /**
     * Render fixed course elements (non-metabox sections) after title
     */
    public function render_fixed_course_elements($post) {
        if ($post->post_type !== 'course') {
            return;
        }
        
        echo '<div class="fixed-course-elements" style="margin: 20px 0;">';
        
        // Course Information section (fixed)
        echo '<div class="course-fixed-section" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px;">';
        echo '<h2 style="margin: 0; Padding: 12px 20px; border-bottom: 1px solid #ccd0d4; background: #f7f7f7; font-size: 16px;">' . __('Informacje o kursie', 'simple-lms') . '</h2>';
        echo '<div style="Padding: 20px;">';
        self::render_course_information_content($post);
        echo '</div>';
        echo '</div>';
        
        // Course Structure section (fixed)
        echo '<div class="course-fixed-section" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px;">';
        echo '<h2 style="margin: 0; Padding: 12px 20px; border-bottom: 1px solid #ccd0d4; background: #f7f7f7; font-size: 16px;">' . __('Struktura kursu', 'simple-lms') . '</h2>';
        echo '<div style="Padding: 20px;">';
        self::render_course_structure_content($post);
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Render course information content (extracted from metabox)
     */
    public function render_course_information_content($post) {
        wp_nonce_field('course_information_nonce', 'course_information_nonce');
        
        $short_description = get_post_meta($post->ID, '_course_short_description', true);
        $featured_image_id = get_post_thumbnail_id($post->ID);
        
        echo '<div style="display: flex; gap: 30px; align-items: flex-start;">';
        
        // Featured image section
        echo '<div style="flex: 0 0 300px;">';
        echo '<h3 style="margin-top: 0; font-size: 18px; color: #333;">' . __('Featured image', 'simple-lms') . '</h3>';
        echo '<div id="course-featured-image-container">';
        
        if ($featured_image_id) {
            $image_url = wp_get_attachment_image_src($featured_image_id, 'medium')[0];
            echo '<div style="text-align: center;">';
            echo '<img src="' . esc_url($image_url) . '" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.15);">';
            echo '<div style="margin-top: 15px;">';
            echo '<button type="button" id="change-course-featured-image" class="button">' . __('Change image', 'simple-lms') . '</button> ';
            echo '<button type="button" id="remove-course-featured-image" class="button" style="color: #a00;">' . __('Delete', 'simple-lms') . '</button>';
            echo '</div>';
            echo '</div>';
        } else {
            echo '<div style="text-align: center; Padding: 40px 20px; border: 2px dashed #ddd; border-radius: 8px; background: #fafafa; min-height: 200px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: center;">';
            echo '<p style="color: #666; margin: 0 0 15px 0; font-size: 16px;">' . __('Brak obrazka', 'simple-lms') . '</p>';
            echo '<button type="button" id="set-course-featured-image" class="button button-primary">' . __('Dodaj obrazek', 'simple-lms') . '</button>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        
        // Short description section
        echo '<div style="flex: 1;">';
        echo '<h4>' . __('Short Course Description', 'simple-lms') . '</h4>';
        echo '<div style="border: 1px solid #ddd; border-radius: 5px;">';
        
        wp_editor($short_description, 'course_short_description', [
            'textarea_name' => 'course_short_description',
            'media_buttons' => true,
            'textarea_rows' => 8,
            'teeny' => false,
            'tinymce' => [
                'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,|,link,unlink,|,undo,redo',
                'toolbar2' => ''
            ]
        ]);
        
        echo '</div>';
        echo '<p class="description">' . __('Short Description displayed on the course list and in preview.', 'simple-lms') . '</p>';
        echo '</div>';
        echo '</div>';
        
        // Initialize featured image handler via JavaScript
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            if (typeof simpleLMSFeaturedImage !== 'undefined') {
                simpleLMSFeaturedImage.init('course', {
                    mediaTitle: '<?php echo esc_js(__('Wybierz obrazek kursu', 'simple-lms')); ?>',
                    buttonText: '<?php echo esc_js(__('Use this image', 'simple-lms')); ?>',
                    confirmText: '<?php echo esc_js(__('Are you sure you want to delete the image?', 'simple-lms')); ?>',
                    changeText: '<?php echo esc_js(__('Change image', 'simple-lms')); ?>',
                    removeText: '<?php echo esc_js(__('Delete', 'simple-lms')); ?>',
                    emptyText: '<?php echo esc_js(__('Brak obrazka', 'simple-lms')); ?>',
                    addText: '<?php echo esc_js(__('Dodaj obrazek', 'simple-lms')); ?>'
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Render fixed module elements (non-metabox sections) after title
     */
    public function render_fixed_module_elements($post) {
        if ($post->post_type !== 'module') {
            return;
        }
        
        echo '<div class="fixed-module-elements" style="margin: 20px 0;">';
        
        // Module Structure section (fixed)
        echo '<div class="module-fixed-section" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px;">';
        echo '<h2 style="margin: 0; Padding: 12px 20px; border-bottom: 1px solid #ccd0d4; background: #f7f7f7; font-size: 16px;">' . __('Struktura modułu', 'simple-lms') . '</h2>';
        echo '<div style="Padding: 20px;">';
        self::render_module_hierarchy_content($post);
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Render module hierarchy content (extracted from metabox)
     */
    public function render_module_hierarchy_content($post) {
        wp_nonce_field('module_hierarchy_nonce', 'module_nonce_field');

        $lessons = get_posts([
            'post_type'      => 'lesson',
            'posts_per_page' => -1,
            'post_status'    => ['publish', 'draft'],
            'meta_key'       => 'parent_module',
            'meta_value'     => $post->ID,
            'orderby'        => ['menu_order' => 'ASC', 'ID' => 'ASC'],
            'order'          => 'ASC'
        ]);

        echo '<div class="module-structure" data-module-id="' . esc_attr($post->ID) . '">';
        echo '<h3 class="module-lessons-heading">' . esc_html__('Lesson list', 'simple-lms') . '</h3>';

        if ($lessons) {
            echo '<ul class="module-lessons-list visible" id="module-lessons-' . esc_attr($post->ID) . '">';
            foreach ($lessons as $lesson) {
                self::render_lesson_item($lesson);
            }
            echo '</ul>';
        } else {
            echo '<ul class="module-lessons-list visible" id="module-lessons-' . esc_attr($post->ID) . '">';
            echo '<li class="lessons-empty">';
            echo '<span class="dashicons dashicons-info"></span> ';
            echo esc_html__('This module has no lessons yet.', 'simple-lms');
            echo '</li>';
            echo '</ul>';
        }
        echo '</div>';

        echo '<div class="module-add-lessons">';
        echo '<div class="add-lesson-form">';
        echo '<h3 class="add-lesson-heading">' . esc_html__('Add Lesson', 'simple-lms') . '</h3>';
        echo '<input type="text" name="new_lesson_title_' . esc_attr($post->ID) . 
             '" placeholder="' . esc_attr__('Enter lesson title', 'simple-lms') . '" class="widefat" />';
        echo '<button type="button" class="button button-primary add-lessons-btn" data-module-id="' . 
             esc_attr($post->ID) . '">';
        echo esc_html__('Add Lesson', 'simple-lms');
        echo '</button>';
        echo '</div>';
        echo '</div>';

        echo '<div id="module-messages"></div>';
    }

    /**
     * Add CSS and JS to make metaboxes non-collapsible
     */
    public function add_metabox_styling() {
        global $post;
        
        if (!$post) {
            return;
        }
        ?>
        <style>
        /* Basic styles for fixed course sections */
        .postbox-container .meta-box-sortables {
            min-height: 0;
        }
        </style>
        <?php
        // Normal WordPress behavior - no forcing (JS removed)
    }
    
    /**
     * Force metabox order in sidebar using JavaScript
     */
    public function force_course_metabox_order() {
        global $post;
        
        if (!$post || $post->post_type !== 'course') {
            return;
        }
        // Metabox reordering moved to admin-script.js
    }
    
    /**
     * Render course information metabox (featured image and description)
     */
    public function render_course_information_meta_box($post) {
        wp_nonce_field('course_information_nonce', 'course_information_nonce');
        
        $short_description = get_post_meta($post->ID, '_course_short_description', true);
        $featured_image_id = get_post_thumbnail_id($post->ID);
        
        echo '<div style="display: flex; gap: 30px; align-items: flex-start;">';
        
        // Featured image section
        echo '<div style="flex: 0 0 300px;">';
        echo '<h4>' . __('Featured image', 'simple-lms') . '</h4>';
        echo '<div id="course-featured-image-container">';
        
        if ($featured_image_id) {
            $image_url = wp_get_attachment_image_src($featured_image_id, 'medium')[0];
            echo '<div style="text-align: center;">';
            echo '<img src="' . esc_url($image_url) . '" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.15);">';
            echo '<div style="margin-top: 15px;">';
            echo '<button type="button" id="change-featured-image" class="button">' . __('Change image', 'simple-lms') . '</button> ';
            echo '<button type="button" id="remove-featured-image" class="button" style="color: #a00;">' . __('Delete', 'simple-lms') . '</button>';
            echo '</div>';
            echo '</div>';
        } else {
            echo '<div style="text-align: center; Padding: 40px 20px; border: 2px dashed #ddd; border-radius: 8px; background: #fafafa; min-height: 200px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: center;">';
            echo '<p style="color: #666; margin: 0 0 15px 0; font-size: 16px;">' . __('Brak obrazka', 'simple-lms') . '</p>';
            echo '<button type="button" id="set-featured-image" class="button button-primary">' . __('Dodaj obrazek', 'simple-lms') . '</button>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        
        // Short description section
        echo '<div style="flex: 1;">';
        echo '<h4>' . __('Short Course Description', 'simple-lms') . '</h4>';
        echo '<div style="border: 1px solid #ddd; border-radius: 5px;">';
        
        wp_editor($short_description, 'course_short_description', [
            'textarea_name' => 'course_short_description',
            'media_buttons' => true,
            'textarea_rows' => 8,
            'teeny' => false,
            'tinymce' => [
                'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,|,link,unlink,|,undo,redo',
                'toolbar2' => ''
            ]
        ]);
        
        echo '</div>';
        echo '<p class="description">' . __('Short Description displayed on the course list and in preview.', 'simple-lms') . '</p>';
        echo '</div>';
        echo '</div>';
        
        // Add JavaScript for featured image functionality
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var mediaUploader;
            
            // Featured image handling
            $('#set-featured-image, #change-featured-image').click(function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php echo esc_js(__('Wybierz obrazek kursu', 'simple-lms')); ?>',
                    button: {
                        text: '<?php echo esc_js(__('Use this image', 'simple-lms')); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    
                    var imageHtml = '<div style="text-align: center;">';
                    imageHtml += '<img src="' + attachment.sizes.medium.url + '" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.15);">';
                    imageHtml += '<div style="margin-top: 15px;">';
                    imageHtml += '<button type="button" id="change-featured-image" class="button"><?php echo esc_js(__('Change image', 'simple-lms')); ?></button> ';
                    imageHtml += '<button type="button" id="remove-featured-image" class="button" style="color: #a00;"><?php echo esc_js(__('Delete', 'simple-lms')); ?></button>';
                    imageHtml += '</div>';
                    imageHtml += '</div>';
                    
                    $('#course-featured-image-container').html(imageHtml);
                    $('#_thumbnail_id').val(attachment.id);
                });
                
                mediaUploader.open();
            });
            
            // Remove featured image
            $(document).on('click', '#remove-featured-image', function(e) {
                e.preventDefault();
                
                $('#_thumbnail_id').val('');
                
                var placeholderHtml = '<div style="text-align: center; Padding: 40px 20px; border: 2px dashed #ddd; border-radius: 8px; background: #fafafa;">';
                placeholderHtml += '<p style="color: #666; margin: 0 0 15px 0; font-size: 16px;"><?php echo esc_js(__('Brak obrazka', 'simple-lms')); ?></p>';
                placeholderHtml += '<button type="button" id="set-featured-image" class="button button-primary"><?php echo esc_js(__('Dodaj obrazek', 'simple-lms')); ?></button>';
                placeholderHtml += '</div>';
                
                $('#course-featured-image-container').html(placeholderHtml);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render course structure metabox (main content area)
     */
    public function render_course_structure_meta_box($post) {
        wp_nonce_field('course_structure_nonce', 'course_nonce_field');
        
        // Use existing course structure content
        self::render_course_structure_content($post);
        
        // Add JavaScript for featured image functionality
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var mediaUploader;
            
            // Featured image handling
            $('#set-featured-image, #change-featured-image').click(function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php echo esc_js(__('Wybierz obrazek kursu', 'simple-lms')); ?>',
                    button: {
                        text: '<?php echo esc_js(__('Use this image', 'simple-lms')); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    
                    var imageHtml = '<div style="text-align: center;">';
                    imageHtml += '<img src="' + attachment.sizes.medium.url + '" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.15);">';
                    imageHtml += '<div style="margin-top: 15px;">';
                    imageHtml += '<button type="button" id="change-featured-image" class="button"><?php echo esc_js(__('Change image', 'simple-lms')); ?></button> ';
                    imageHtml += '<button type="button" id="remove-featured-image" class="button" style="color: #a00;"><?php echo esc_js(__('Delete', 'simple-lms')); ?></button>';
                    imageHtml += '</div>';
                    imageHtml += '</div>';
                    
                    $('#course-featured-image-container').html(imageHtml);
                    $('#_thumbnail_id').val(attachment.id);
                });
                
                mediaUploader.open();
            });
            
            // Remove featured image
            $(document).on('click', '#remove-featured-image', function(e) {
                e.preventDefault();
                
                $('#_thumbnail_id').val('');
                
                var placeholderHtml = '<div style="text-align: center; Padding: 40px 20px; border: 2px dashed #ddd; border-radius: 8px; background: #fafafa;">';
                placeholderHtml += '<p style="color: #666; margin: 0 0 15px 0; font-size: 16px;"><?php echo esc_js(__('Brak obrazka', 'simple-lms')); ?></p>';
                placeholderHtml += '<button type="button" id="set-featured-image" class="button button-primary"><?php echo esc_js(__('Dodaj obrazek', 'simple-lms')); ?></button>';
                placeholderHtml += '</div>';
                
                $('#course-featured-image-container').html(placeholderHtml);
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX: Fix course roles
     */
    // ajax_fix_course_roles() removed – legacy role-based repair not required under tag system.

    /**
     * Render fixed lesson elements (non-metabox sections) after editor
     */
    public function render_fixed_lesson_elements($post) {
        if ($post->post_type !== 'lesson') {
            return;
        }
        
        echo '<div class="fixed-lesson-elements" style="margin: 20px 0;">';
        
        // Lesson Details section (fixed)
        echo '<div class="lesson-fixed-section" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px;">';
        echo '<h2 style="margin: 0; Padding: 12px 20px; border-bottom: 1px solid #ccd0d4; background: #f7f7f7; font-size: 16px;">' . __('Lesson details', 'simple-lms') . '</h2>';
        echo '<div style="Padding: 20px;">';
        
        // Two-row layout: First row with image, video controls, and video preview; Second row with attachments
        
        // First row: Featured Image | Video Controls | Video Preview
        echo '<div style="display: flex; gap: 30px; align-items: flex-start; margin-bottom: 30px;">';
        
        // Featured image section (left column - bigger like in courses)
        echo '<div style="flex: 0 0 300px;">';
        self::render_lesson_featured_image_content($post);
        echo '</div>';
        
        // Video section (middle column - fixed width)
        echo '<div style="flex: 0 0 400px;">';
        self::render_lesson_video_content($post);
        echo '</div>';
        
        // Video preview section (right column - takes remaining space)
        echo '<div style="flex: 1; min-width: 300px; max-width: 100%; overflow: hidden;" id="lesson-video-preview-container">';
        // Video preview will be inserted here by JavaScript
        echo '</div>';
        
        echo '</div>'; // End first row
        
        echo '</div>'; // End three columns
        echo '</div>'; // End Padding
        echo '</div>'; // End lesson-fixed-section
        
        echo '</div>'; // End fixed-lesson-elements
        
        // Add CSS to make h4 headers in fixed sections look bigger
        ?>
        <style>
        .fixed-course-elements h4,
        .fixed-module-elements h4,
        .fixed-lesson-elements h4 {
            font-size: 18px !important;
            color: #333 !important;
            margin-top: 0 !important;
        }
        </style>
        <?php
    }
    
    /**
     * Render lesson featured image content
     */
    public function render_lesson_featured_image_content($post) {
        $featured_image_id = get_post_thumbnail_id($post->ID);
        
        echo '<h3 style="margin-top: 0; font-size: 18px; color: #333;">' . __('Featured image', 'simple-lms') . '</h3>';
        echo '<div id="lesson-featured-image-container">';
        
        if ($featured_image_id) {
            $image_url = wp_get_attachment_image_src($featured_image_id, 'medium')[0];
            echo '<div style="text-align: center;">';
            echo '<img src="' . esc_url($image_url) . '" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.15);">';
            echo '<div style="margin-top: 15px;">';
            echo '<button type="button" id="change-lesson-featured-image" class="button">' . __('Change image', 'simple-lms') . '</button> ';
            echo '<button type="button" id="remove-lesson-featured-image" class="button" style="color: #a00;">' . __('Delete', 'simple-lms') . '</button>';
            echo '</div>';
            echo '</div>';
        } else {
            echo '<div style="text-align: center; Padding: 30px 15px; border: 2px dashed #ddd; border-radius: 8px; background: #fafafa; min-height: 150px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: center;">';
            echo '<p style="color: #666; margin: 0 0 10px 0; font-size: 14px;">' . __('Brak obrazka', 'simple-lms') . '</p>';
            echo '<button type="button" id="set-lesson-featured-image" class="button button-primary">' . __('Dodaj obrazek', 'simple-lms') . '</button>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Initialize featured image handler via JavaScript
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            if (typeof simpleLMSFeaturedImage !== 'undefined') {
                simpleLMSFeaturedImage.init('lesson', {
                    mediaTitle: '<?php echo esc_js(__('Wybierz obrazek lessons', 'simple-lms')); ?>',
                    buttonText: '<?php echo esc_js(__('Use this image', 'simple-lms')); ?>',
                    confirmText: '<?php echo esc_js(__('Are you sure you want to delete the image?', 'simple-lms')); ?>',
                    changeText: '<?php echo esc_js(__('Change image', 'simple-lms')); ?>',
                    removeText: '<?php echo esc_js(__('Delete', 'simple-lms')); ?>',
                    emptyText: '<?php echo esc_js(__('Brak obrazka', 'simple-lms')); ?>',
                    addText: '<?php echo esc_js(__('Dodaj obrazek', 'simple-lms')); ?>'
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Render lesson video content (extracted from metabox)
     */
    public function render_lesson_video_content($post) {
        wp_nonce_field('simple_lms_lesson_video_meta', 'simple_lms_lesson_video_nonce');
        
        $video_type = get_post_meta($post->ID, 'lesson_video_type', true) ?: 'none';
        $video_url = get_post_meta($post->ID, 'lesson_video_url', true);
        $video_file_id = get_post_meta($post->ID, 'lesson_video_file_id', true);
        
        echo '<h3 style="margin-top: 0; font-size: 18px; color: #333;">' . __('Film lessons', 'simple-lms') . '</h3>';
        echo '<div class="lesson-video-settings" style="max-width: 100%;">';
        
        // Video type selector
        echo '<p><strong>' . __('Typ filmu:', 'simple-lms') . '</strong></p>';
        echo '<label><input type="radio" name="lesson_video_type" value="none"' . checked($video_type, 'none', false) . '> ' . __('Brak filmu', 'simple-lms') . '</label><br>';
        echo '<label><input type="radio" name="lesson_video_type" value="youtube"' . checked($video_type, 'youtube', false) . '> ' . __('YouTube', 'simple-lms') . '</label><br>';
        echo '<label><input type="radio" name="lesson_video_type" value="vimeo"' . checked($video_type, 'vimeo', false) . '> ' . __('Vimeo', 'simple-lms') . '</label><br>';
        echo '<label><input type="radio" name="lesson_video_type" value="url"' . checked($video_type, 'url', false) . '> ' . __('Link URL (other source)', 'simple-lms') . '</label><br>';
        echo '<label><input type="radio" name="lesson_video_type" value="file"' . checked($video_type, 'file', false) . '> ' . __('File from media library', 'simple-lms') . '</label><br>';
        
        // URL input (shown for youtube, vimeo, and url types)
        $show_url_input = in_array($video_type, ['youtube', 'vimeo', 'url']);
        echo '<div class="video-url-section" style="' . (!$show_url_input ? 'display:none;' : '') . 'margin-top:10px;">';
        echo '<p><label for="lesson_video_url"><strong>' . __('URL filmu:', 'simple-lms') . '</strong></label></p>';
        echo '<input type="url" id="lesson_video_url" name="lesson_video_url" value="' . esc_attr($video_url) . '" class="widefat" placeholder="https://www.youtube.com/..." />';
        
        echo '</div>';
        
        // File selector
        echo '<div class="video-file-section" style="' . ($video_type !== 'file' ? 'display:none;' : '') . 'margin-top:10px;">';
        echo '<p><strong>' . __('File from media library:', 'simple-lms') . '</strong></p>';
        
        $file_url = '';
        if ($video_file_id) {
            $file_url = wp_get_attachment_url($video_file_id);
        }
        
        echo '<div class="video-file-preview">';
        if ($file_url) {
            echo '<p>' . __('Wybrany plik:', 'simple-lms') . ' <a href="' . esc_url($file_url) . '" target="_blank">' . esc_html(basename($file_url)) . '</a></p>';
        } else {
            echo '<p>' . __('Brak wybranego pliku', 'simple-lms') . '</p>';
        }
        echo '</div>';
        
        echo '<input type="hidden" id="lesson_video_file_id" name="lesson_video_file_id" value="' . esc_attr($video_file_id) . '" />';
        echo '<button type="button" class="button" id="select-video-file">' . __('Wybierz plik wideo', 'simple-lms') . '</button> ';
        echo '<button type="button" class="button" id="remove-video-file"' . (!$video_file_id ? ' style="display:none;"' : '') . '>' . __('Delete', 'simple-lms') . '</button>';
        
        echo '</div>';
        
        echo '</div>';
        
        // Initialize video preview on page load
        $initial_preview = '';
        if ($show_url_input && $video_url) {
            $initial_preview = self::generate_video_preview($video_type, $video_url);
        } elseif ($video_type === 'file' && $file_url) {
            $initial_preview = self::generate_video_preview($video_type, '', $file_url);
        }
        
        // JavaScript for handling video type changes and moving preview to right column
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Initialize: show correct field based on current video type
            var currentType = $('input[name=lesson_video_type]:checked').val();
            $(".video-url-section, .video-file-section").hide();
            if (currentType === "youtube" || currentType === "vimeo" || currentType === "url") {
                $(".video-url-section").show();
            } else if (currentType === "file") {
                $(".video-file-section").show();
            }
            
            // Set initial preview
            var initialPreview = <?php echo json_encode($initial_preview); ?>;
            if (initialPreview) {
                $('#lesson-video-preview-container').html('<h3 style="margin-top: 0; font-size: 18px; color: #333;"><?php echo esc_js(__('Preview wideo', 'simple-lms')); ?></h3>' + initialPreview);
            }
            
            function updateVideoPreview() {
                var type = $('input[name=lesson_video_type]:checked').val();
                var url = $('#lesson_video_url').val();
                var fileId = $('#lesson_video_file_id').val();
                
                if (type === 'none' || (type !== 'file' && !url) || (type === 'file' && !fileId)) {
                    $('#lesson-video-preview-container').html('');
                    return;
                }
                
                // Show preview in right column
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'generate_video_preview',
                        type: type,
                        url: url,
                        file_id: fileId,
                        nonce: '<?php echo wp_create_nonce('video_preview_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#lesson-video-preview-container').html('<h3 style="margin-top: 0; font-size: 18px; color: #333;"><?php echo esc_js(__('Preview wideo', 'simple-lms')); ?></h3>' + response.data);
                        }
                    }
                });
            }
            
            $("input[name=lesson_video_type]").change(function() {
                var type = $(this).val();
                $(".video-url-section, .video-file-section").hide();
                if (type === "youtube" || type === "vimeo" || type === "url") {
                    $(".video-url-section").show();
                    // Update placeholder based on type
                    var placeholder = "https://example.com/video.mp4";
                    if (type === "youtube") {
                        placeholder = "https://www.youtube.com/...";
                    } else if (type === "vimeo") {
                        placeholder = "https://vimeo.com/...";
                    }
                    $("#lesson_video_url").attr("placeholder", placeholder);
                } else if (type === "file") {
                    $(".video-file-section").show();
                }
                updateVideoPreview();
            });
            
            // Update preview when URL changes
            $('#lesson_video_url').on('input change', function() {
                updateVideoPreview();
            });
            
            $("#select-video-file").click(function(e) {
                e.preventDefault();
                var custom_uploader = wp.media({
                    title: "<?php echo esc_js(__('Wybierz plik wideo', 'simple-lms')); ?>",
                    library: { type: "video" },
                    button: { text: "<?php echo esc_js(__('Wybierz plik', 'simple-lms')); ?>" },
                    multiple: false
                });
                
                custom_uploader.on("select", function() {
                    var attachment = custom_uploader.state().get("selection").first().toJSON();
                    $("#lesson_video_file_id").val(attachment.id);
                    $(".video-file-preview p").html("<?php echo esc_js(__('Wybrany plik:', 'simple-lms')); ?> <a href=\"" + attachment.url + "\" target=\"_blank\">" + attachment.filename + "</a>");
                    $("#remove-video-file").show();
                    updateVideoPreview();
                });
                
                custom_uploader.open();
            });
            
            $("#remove-video-file").click(function(e) {
                e.preventDefault();
                $("#lesson_video_file_id").val("");
                $(".video-file-preview p").html("<?php echo esc_js(__('Brak wybranego pliku', 'simple-lms')); ?>");
                $(this).hide();
                updateVideoPreview();
            });
        });
        </script>
        <?php
    }
    

    /**
     * Save lesson video and attachments data
     */
    public function save_lesson_data($post_id) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Skip Elementor templates and library posts
        $post_type = get_post_type($post_id);
        if (in_array($post_type, ['elementor_library', 'elementor_snippet', 'e-landing-page'], true)) {
            return;
        }
        
        // Skip if this is an Elementor Ajax save
        if (defined('ELEMENTOR_VERSION') && !empty($_POST['actions'])) {
            return;
        }
        
        // Save lesson video (complete implementation)
        if (isset($_POST['simple_lms_lesson_video_nonce']) && 
            wp_verify_nonce($_POST['simple_lms_lesson_video_nonce'], 'simple_lms_lesson_video_meta')) {
            
            $video_type = sanitize_text_field($_POST['lesson_video_type'] ?? 'none');
            update_post_meta($post_id, 'lesson_video_type', $video_type);
            
            if ($video_type === 'youtube' || $video_type === 'vimeo' || $video_type === 'url') {
                $video_url = esc_url_raw($_POST['lesson_video_url'] ?? '');
                update_post_meta($post_id, 'lesson_video_url', $video_url);
                delete_post_meta($post_id, 'lesson_video_file_id');
            } elseif ($video_type === 'file') {
                $video_file_id = absint($_POST['lesson_video_file_id'] ?? 0);
                update_post_meta($post_id, 'lesson_video_file_id', $video_file_id);
                delete_post_meta($post_id, 'lesson_video_url');
            } else {
                delete_post_meta($post_id, 'lesson_video_url');
                delete_post_meta($post_id, 'lesson_video_file_id');
            }
        }
        
        // (Old _lesson_attachments save logic removed)
        
        // Note: Parent module is handled by its own metabox save function
    }

    /**
     * AJAX handler for generating video preview
     */
    public function ajax_generate_video_preview() {
        check_ajax_referer('video_preview_nonce', 'nonce');
        
        $type = sanitize_text_field($_POST['type'] ?? '');
        $url = esc_url_raw($_POST['url'] ?? '');
        $file_id = absint($_POST['file_id'] ?? 0);
        
        if ($type === 'file' && $file_id) {
            $file_url = wp_get_attachment_url($file_id);
            $preview = self::generate_video_preview($type, '', $file_url);
        } elseif ($url && in_array($type, ['youtube', 'vimeo', 'url'])) {
            $preview = self::generate_video_preview($type, $url);
        } else {
            wp_send_json_error('Invalid parameters');
            return;
        }
        
        wp_send_json_success($preview);
    }
    
    /**
     * AJAX handler for saving lesson attachments
     */
    public function ajax_save_lesson_attachments() {
        check_ajax_referer('lesson_attachments_nonce', 'nonce');
        
        $post_id = absint($_POST['post_id'] ?? 0);
        $attachment_ids = $_POST['attachment_ids'] ?? [];
        
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Brak uprawnień');
            return;
        }
        
        // Sanitize attachment IDs
        $clean_attachments = [];
        if (is_array($attachment_ids)) {
            foreach ($attachment_ids as $attachment_id) {
                $attachment_id = absint($attachment_id);
                if ($attachment_id > 0) {
                    $clean_attachments[] = $attachment_id;
                }
            }
        }
        
        // Save to database
        if (!empty($clean_attachments)) {
            update_post_meta($post_id, 'lesson_attachments', $clean_attachments);
        } else {
            delete_post_meta($post_id, 'lesson_attachments');
        }
        
        wp_send_json_success(['message' => 'Attachments zostały zapisane', 'count' => count($clean_attachments)]);
    }
    
    /**
     * Render lesson details metabox (combines all lesson details)
     */
    public function render_lesson_details_meta_box($post) {
        wp_nonce_field('lesson_details_metabox', 'lesson_details_metabox_nonce');
        
        echo '<div style="display: flex; gap: 30px; align-items: flex-start; margin-bottom: 30px;">';
        
        // Featured image section (left column)
        echo '<div style="flex: 0 0 300px;">';
        self::render_lesson_featured_image_content($post);
        echo '</div>';
        
        // Video section (middle column)
        echo '<div style="flex: 1;">';
        self::render_lesson_video_content($post);
        echo '</div>';
        
        // Video preview section (right column)
        echo '<div style="flex: 1;" id="lesson-video-preview-container">';
        // Preview will be popuyearsed by JavaScript
        echo '</div>';
        
        echo '</div>';
        
        // Attachments section (full width below)
        echo '<div style="margin-top: 30px; border-top: 1px solid #ddd; Padding-top: 20px;">';
        wp_nonce_field('simple_lms_lesson_attachments_meta', 'simple_lms_lesson_attachments_nonce');
        $attachments = get_post_meta($post->ID, 'lesson_attachments', true);
        if (!is_array($attachments)) {
            $attachments = [];
        }
        echo '<div class="lesson-attachments-container">';
        echo '<h3 style="margin-top: 0; font-size: 18px; color: #333;">' . __('Downloadable files for this lesson', 'simple-lms') . '</h3>';
        echo '<div id="attachments-list">';
        if (!empty($attachments)) {
            foreach ($attachments as $index => $attachment_id) {
                $file_url = wp_get_attachment_url($attachment_id);
                $file_title = get_the_title($attachment_id);
                if ($file_url) {
                    // Extract file extension for badge
                    $file_ext = strtolower(pathinfo(parse_url($file_url, PHP_URL_PATH), PATHINFO_EXTENSION));
                    $display_ext = $file_ext ? strtoupper($file_ext) : 'FILE';
                    
                    echo '<div class="attachment-item" data-index="' . $index . '" style="Padding: 12px 0; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 15px;">';
                    echo '<span class="file-type-badge file-type-' . esc_attr($file_ext) . '" style="display: inline-flex; align-items: center; justify-content: center; width: 62px; height: 28px; Padding: 4px 6px; background: #f0f0f0; color: #666; font-size: 10px; font-weight: 600; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.3px; flex-shrink: 0;">' . esc_html($display_ext) . '</span>';
                    echo '<span class="attachment-title" style="flex: 1; font-weight: 500;">' . esc_html($file_title) . '</span>';
                    echo '<div style="display: flex; gap: 10px; align-items: center;">';
                    echo '<a href="' . esc_url($file_url) . '" target="_blank" class="attachment-link" style="color: #0073aa; text-decoration: none; font-size: 12px;">(' . __('Preview', 'simple-lms') . ')</a>';
                    echo '<button type="button" class="button remove-attachment" data-index="' . $index . '" style="font-size: 12px; Padding: 2px 8px;">' . __('Delete', 'simple-lms') . '</button>';
                    echo '</div>';
                    echo '<input type="hidden" name="lesson_attachments[]" value="' . esc_attr($attachment_id) . '" />';
                    echo '</div>';
                }
            }
        }
        echo '</div>';
        echo '<div class="add-attachment-section">';
        echo '<button type="button" class="button button-primary" id="add-attachment">' . __('Add attachment', 'simple-lms') . '</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '<script>
        jQuery(document).ready(function($) {
            var postId = ' . $post->ID . ';
            var attachmentNonce = "' . wp_create_nonce('lesson_attachments_nonce') . '";
            
            function applyBadgeColors($badge, fileExt) {
                // Apply specific colors based on file extension
                $badge.css("background", "#f0f0f0").css("color", "#666"); // Default
                
                switch(fileExt) {
                    case "pdf":
                        $badge.css("background", "#e74c3c").css("color", "#fff");
                        break;
                    case "doc":
                    case "docx":
                        $badge.css("background", "#2980b9").css("color", "#fff");
                        break;
                    case "xls":
                    case "xlsx":
                        $badge.css("background", "#27ae60").css("color", "#fff");
                        break;
                    case "ppt":
                    case "pptx":
                        $badge.css("background", "#f39c12").css("color", "#fff");
                        break;
                    case "zip":
                    case "rar":
                        $badge.css("background", "#8e44ad").css("color", "#fff");
                        break;
                    case "jpg":
                    case "jpeg":
                    case "png":
                    case "gif":
                        $badge.css("background", "#e67e22").css("color", "#fff");
                        break;
                    case "mp4":
                    case "avi":
                    case "mov":
                        $badge.css("background", "#9b59b6").css("color", "#fff");
                        break;
                    case "mp3":
                    case "wav":
                        $badge.css("background", "#1abc9c").css("color", "#fff");
                        break;
                }
            }
            
            // Apply colors to existing badges on page load
            $(".file-type-badge").each(function() {
                var $badge = $(this);
                var classes = $badge.attr("class").split(" ");
                var fileExt = "";
                
                // Extract file extension from class name
                for (var i = 0; i < classes.length; i++) {
                    if (classes[i].startsWith("file-type-")) {
                        fileExt = classes[i].replace("file-type-", "");
                        break;
                    }
                }
                
                if (fileExt) {
                    applyBadgeColors($badge, fileExt);
                }
            });
            
            function saveAttachments() {
                var attachmentIds = [];
                $("#attachments-list input[name=\'lesson_attachments[]\']").each(function() {
                    attachmentIds.push($(this).val());
                });
                
                $.ajax({
                    url: ajaxurl,
                    method: "POST",
                    data: {
                        action: "save_lesson_attachments",
                        post_id: postId,
                        attachment_ids: attachmentIds,
                        nonce: attachmentNonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Opcjonalnie: pokaż krótką informację o zapisaniu
                            
                        }
                    },
                    error: function() {
                        
                    }
                });
            }
            
            $("#add-attachment").click(function(e) {
                e.preventDefault();
                var custom_uploader = wp.media({
                    title: "' . __('Select file to attach', 'simple-lms') . '",
                    button: { text: "' . __('Add attachment', 'simple-lms') . '" },
                    multiple: false
                });
                custom_uploader.on("select", function() {
                    var attachment = custom_uploader.state().get("selection").first().toJSON();
                    var index = $("#attachments-list .attachment-item").length;
                    
                    // Extract file extension for badge
                    var fileExt = "";
                    var displayExt = "FILE";
                    if (attachment.url) {
                        var urlPath = attachment.url.split("?")[0]; // Remove query params
                        var pathParts = urlPath.split(".");
                        if (pathParts.length > 1) {
                            fileExt = pathParts[pathParts.length - 1].toLowerCase();
                            displayExt = fileExt.toUpperCase();
                        }
                    }
                    
                    var attachmentHtml = "<div class=\"attachment-item\" data-index=\"" + index + "\" style=\"Padding: 12px 0; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 15px;\">" +
                        "<span class=\"file-type-badge file-type-" + fileExt + "\" style=\"display: inline-flex; align-items: center; justify-content: center; width: 62px; height: 28px; Padding: 4px 6px; background: #f0f0f0; color: #666; font-size: 10px; font-weight: 600; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.3px; flex-shrink: 0;\">" + displayExt + "</span>" +
                        "<span class=\"attachment-title\" style=\"flex: 1; font-weight: 500;\">" + attachment.title + "</span>" +
                        "<div style=\"display: flex; gap: 10px; align-items: center;\">" +
                        "<a href=\"" + attachment.url + "\" target=\"_blank\" class=\"attachment-link\" style=\"color: #0073aa; text-decoration: none; font-size: 12px;\">(" + "' . __('Preview', 'simple-lms') . '" + ")</a>" +
                        "<button type=\"button\" class=\"button remove-attachment\" data-index=\"" + index + "\" style=\"font-size: 12px; Padding: 2px 8px;\">" + "' . __('Delete', 'simple-lms') . '" + "</button>" +
                        "</div>" +
                        "<input type=\"hidden\" name=\"lesson_attachments[]\" value=\"" + attachment.id + "\" />" +
                        "</div>";
                    $("#attachments-list").append(attachmentHtml);
                    
                    // Apply colored styling to the new badge
                    var $newBadge = $("#attachments-list .attachment-item:last .file-type-badge");
                    applyBadgeColors($newBadge, fileExt);
                    
                    // Auto-save po dodaniu pliku
                    setTimeout(saveAttachments, 100);
                });
                custom_uploader.open();
            });
            $(document).on("click", ".remove-attachment", function() {
                $(this).closest(".attachment-item").remove();
                
                // Auto-save po usunięciu pliku
                setTimeout(saveAttachments, 100);
            });
        });
        </script>';
    }
}

// Initialization now handled via ServiceContainer in main plugin file
