<?php
namespace SimpleLMS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin customization handler class
 */
class Admin_Customizations {
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
            ->addAction('admin_head', [$this, 'customize_admin_ui'])
            ->addAction('admin_init', [$this, 'restrict_module_lesson_access'])
            ->addAction('admin_footer', [$this, 'add_navigation_buttons'])
            ->addAction('admin_menu', [$this, 'customize_admin_menu'], 999)
            ->addFilter('use_block_editor_for_post_type', [$this, 'disable_block_editor'], 10, 2)
            ->addFilter('manage_course_posts_columns', [$this, 'set_course_columns'])
            ->addAction('manage_course_posts_custom_column', [$this, 'display_course_column_content'], 10, 2)
            ->addFilter('get_user_option_meta-box-order_course', [$this, 'set_course_metabox_order'])
            ->addFilter('get_user_option_meta-box-order_lesson', [$this, 'set_lesson_metabox_order']);
    }

    /**
     * Disable Gutenberg (block editor) for LMS post types
     */
    public function disable_block_editor(bool $use_block_editor, string $post_type): bool
    {
        if (in_array($post_type, ['course', 'module', 'lesson'], true)) {
            return false;
        }
        return $use_block_editor;
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
     * Customize admin UI elements
     */
    public function customize_admin_ui(): void
    {
        global $current_screen;
        if (!$current_screen) {
            return;
        }

        if (in_array($current_screen->post_type, ['module', 'lesson'])) {
            echo '<style>
                body.post-php .wrap h1.wp-heading-inline + a.page-title-action:not(.custom-nav-button) {
                    display: none !important;
                }
                body.post-php .wrap .page-title-action:not(.custom-nav-button) {
                    display: none !important;
                }
                .wrap a.page-title-action:not(.custom-nav-button) {
                    display: none !important;
                }
                #delete-action {
                    display: none !important;
                }
            </style>';
        }
    }

    /**
     * Restrict direct access to module and lesson list pages
     */
    public function restrict_module_lesson_access() {
        global $pagenow;
        
        if ($pagenow === 'edit.php' && 
            isset($_GET['post_type']) && 
            in_array($_GET['post_type'], ['module', 'lesson']) &&
            !isset($_GET['post'])) {
            
            $course_id = isset($_GET['course_id']) ? absint($_GET['course_id']) : 0;
            if ($course_id && current_user_can('edit_post', $course_id)) {
                return;
            }
            
            wp_safe_redirect(admin_url('edit.php?post_type=course'));
            exit;
        }
    }

    /**
     * Remove unwanted submenu entries under Courses for modules and lessons
     */
    public function customize_admin_menu(): void
    {
        // Hide standalone module/lesson menus entirely
        remove_menu_page('edit.php?post_type=module');
        remove_menu_page('edit.php?post_type=lesson');
        remove_submenu_page('edit.php?post_type=course', 'edit.php?post_type=module');
        remove_submenu_page('edit.php?post_type=course', 'edit.php?post_type=lesson');
    }

    /**
     * Add navigation buttons to module and lesson edit screens
     */
    public function add_navigation_buttons() {
        global $post, $pagenow;

        if ($pagenow !== 'post.php' || !$post) {
            return;
        }

        if ($post->post_type === 'module') {
            self::render_module_navigation($post);
        } elseif ($post->post_type === 'lesson') {
            self::render_lesson_navigation($post);
        }
    }

    /**
     * Render module navigation
     */
    private static function render_module_navigation($post) {
        $course_id = get_post_meta($post->ID, 'parent_course', true);
        if (!$course_id) {
            return;
        }

        $course_edit_link = get_edit_post_link($course_id);
        ?>
        <script>
        jQuery(document).ready(function($) {
            var button = $('<a href="<?php echo esc_url($course_edit_link); ?>" class="page-title-action custom-nav-button"><?php 
                echo esc_html__('Powrót do edycji', 'simple-lms'); 
                ?> <span class="custom-strong"><?php echo esc_html__('KURSU', 'simple-lms'); ?></span></a>');
            $('.wp-heading-inline').after(button);

            var deleteButton = $('#delete-action a.submitdelete');
            if (deleteButton.length) {
                deleteButton
                    .text(<?php echo wp_json_encode(__('Usuń Moduł', 'simple-lms')); ?>)
                    .addClass('custom-delete')
                    .removeClass('submitdelete')
                    .attr('href', '#')
                    .on('click', function(e) {
                        e.preventDefault();
                        if (confirm(simpleLMS.i18n.confirm_delete_module)) {
                            $.post(ajaxurl, {
                                action: 'delete_module',
                                module_id: <?php echo $post->ID; ?>,
                                security: simpleLMS.nonce
                            }, function(response) {
                                if (response.success) {
                                    window.location.href = <?php echo wp_json_encode($course_edit_link); ?>;
                                } else {
                                    alert(response.data.message || simpleLMS.i18n.error_generic);
                                }
                            });
                        }
                    });
            }
        });
        </script>
        <?php
    }

    /**
     * Render lesson navigation
     */
    private static function render_lesson_navigation($post) {
        $module_id = get_post_meta($post->ID, 'parent_module', true);
        if (!$module_id) {
            return;
        }

        $module_edit_link = get_edit_post_link($module_id);
        $course_id = get_post_meta($module_id, 'parent_course', true);
        $course_edit_link = $course_id ? get_edit_post_link($course_id) : '';
        ?>
        <script>
        jQuery(document).ready(function($) {
            <?php if ($course_edit_link): ?>
            var buttonCourse = $('<a href="<?php echo esc_url($course_edit_link); ?>" class="page-title-action custom-nav-button"><?php 
                echo esc_html__('Powrót do edycji', 'simple-lms'); 
                ?> <span class="custom-strong"><?php echo esc_html__('KURSU', 'simple-lms'); ?></span></a>');
            $('.wp-heading-inline').after(buttonCourse);
            <?php endif; ?>

            <?php if ($module_edit_link): ?>
            var buttonModule = $('<a href="<?php echo esc_url($module_edit_link); ?>" class="page-title-action custom-nav-button"><?php 
                echo esc_html__('Powrót do edycji', 'simple-lms'); 
                ?> <span class="custom-strong"><?php echo esc_html__('MODUŁU', 'simple-lms'); ?></span></a>');
            $('.wp-heading-inline').after(buttonModule);

            var deleteButton = $('#delete-action a.submitdelete');
            if (deleteButton.length) {
                deleteButton
                    .text(<?php echo wp_json_encode(__('Usuń Lekcję', 'simple-lms')); ?>)
                    .addClass('custom-delete')
                    .removeClass('submitdelete')
                    .attr('href', '#')
                    .on('click', function(e) {
                        e.preventDefault();
                        if (confirm(simpleLMS.i18n.confirm_delete_lesson)) {
                            $.post(ajaxurl, {
                                action: 'delete_lesson',
                                lesson_id: <?php echo $post->ID; ?>,
                                security: simpleLMS.nonce
                            }, function(response) {
                                if (response.success) {
                                    window.location.href = <?php echo wp_json_encode($module_edit_link); ?>;
                                } else {
                                    alert(response.data.message || simpleLMS.i18n.error_generic);
                                }
                            });
                        }
                    });
            }
            <?php endif; ?>
        });
        </script>
        <?php
    }

    /**
     * Set custom columns for course list
     */
    public function set_course_columns($columns) {
        return [
            'cb'        => $columns['cb'],
            'title'     => __('Tytuł', 'simple-lms'),
            'thumbnail' => __('Obrazek wyróżniający', 'simple-lms'),
            'modules'   => __('Liczba opublikowanych modułów', 'simple-lms'),
            'lessons'   => __('Liczba opublikowanych lekcji', 'simple-lms'),
            'date'      => __('Data', 'simple-lms'),
        ];
    }

    /**
     * Display custom column content for courses
     */
    public function display_course_column_content($column, $post_id) {
        switch ($column) {
            case 'thumbnail':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, [50, 50]);
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
            case 'modules':
                $modules = get_posts([
                    'post_type' => 'module',
                    'posts_per_page' => -1,
                    'meta_key' => 'parent_course',
                    'meta_value' => $post_id,
                    'post_status' => 'publish'
                ]);
                echo count($modules);
                break;
            case 'lessons':
                $modules = get_posts([
                    'post_type' => 'module',
                    'posts_per_page' => -1,
                    'meta_key' => 'parent_course',
                    'meta_value' => $post_id,
                    'post_status' => 'publish'
                ]);
                $lesson_count = 0;
                foreach ($modules as $module) {
                    $lessons = get_posts([
                        'post_type' => 'lesson',
                        'posts_per_page' => -1,
                        'meta_key' => 'parent_module',
                        'meta_value' => $module->ID,
                        'post_status' => 'publish'
                    ]);
                    $lesson_count += count($lessons);
                }
                echo $lesson_count;
                break;
        }
    }

    /**
     * Set course metabox order
     */
    public function set_course_metabox_order($order) {
        return array(
            'side' => 'submitdiv,postimagediv,course_settings',
            'normal' => 'course_hierarchy',
            'advanced' => '',
        );
    }

    /**
     * Set lesson metabox order
     */
    public function set_lesson_metabox_order($order) {
        return array(
            'side' => 'submitdiv,postimagediv,lesson_parent_module',
            'normal' => 'postexcerpt,postcustom,commentstatusdiv,commentsdiv,slugdiv,authordiv',
            'advanced' => '',
        );
    }
}

// Admin_Customizations is now managed by ServiceContainer
// and instantiated in Plugin::registerLateServices()
?>
