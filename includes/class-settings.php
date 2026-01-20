<?php
namespace SimpleLMS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings management class
 */
class Settings {
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
            ->addAction('admin_menu', [$this, 'add_settings_page'])
            ->addAction('admin_init', [$this, 'register_settings']);
    }

    /**
     * Legacy static init for backward compatibility
     * 
     * @deprecated Use dependency injection instead
     */
    public static function init(): void
    {
        // No-op - left for backward compatibility
        // Initialization now handled via constructor
    }

    /**
     * Add settings page to admin menu
     */
    public function add_settings_page(): void
    {
        \add_submenu_page(
            'edit.php?post_type=course',
            \__('Settings', 'simple-lms'),
            \__('Settings', 'simple-lms'),
            'manage_options',
            'simple-lms-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings(): void
    {
        \register_setting(
            'simple_lms_settings',
            'simple_lms_language',
            [
                'type'              => 'string',
                'default'           => 'default',
                'sanitize_callback' => [$this, 'sanitize_language'],
            ]
        );

        \add_settings_section(
            'simple_lms_general_section',
            \__('General Settings', 'simple-lms'),
            [$this, 'render_general_section'],
            'simple-lms-settings'
        );

        \add_settings_field(
            'simple_lms_language',
            \__('Language', 'simple-lms'),
            [$this, 'render_language_field'],
            'simple-lms-settings',
            'simple_lms_general_section'
        );

        // Verbose Logging toggle
        \register_setting(
            'simple_lms_settings',
            'simple_lms_verbose_logging',
            [
                'type' => 'string',
                'default' => 'no',
                'sanitize_callback' => function ($v) { return $v === 'yes' ? 'yes' : 'no'; }
            ]
        );

        \add_settings_field(
            'simple_lms_verbose_logging',
            \__('Verbose Logging', 'simple-lms'),
            [$this, 'render_verbose_logging_field'],
            'simple-lms-settings',
            'simple_lms_general_section'
        );

        // Delete data on uninstall
        \register_setting(
            'simple_lms_settings',
            'simple_lms_delete_data_on_uninstall',
            [
                'type' => 'string',
                'default' => 'no',
                'sanitize_callback' => function ($v) { return $v === 'yes' ? 'yes' : 'no'; }
            ]
        );

        \add_settings_field(
            'simple_lms_delete_data_on_uninstall',
            \__('Delete all data on uninstall', 'simple-lms'),
            [$this, 'render_delete_data_field'],
            'simple-lms-settings',
            'simple_lms_general_section'
        );
        
        // Course Settings Section
        \add_settings_section(
            'simple_lms_course_section',
            \__('Course Settings', 'simple-lms'),
            [$this, 'render_course_section'],
            'simple-lms-settings'
        );
        
        // Module & Lesson Settings Section
        \add_settings_section(
            'simple_lms_module_lesson_section',
            \__('Module & Lesson Settings', 'simple-lms'),
            [$this, 'render_module_lesson_section'],
            'simple-lms-settings'
        );
    }

    /**
     * Sanitize language setting and trigger translation reload
     */
    public function sanitize_language($value): string
    {
        $allowed = ['default', 'en_US', 'pl_PL'];
        $sanitized = in_array($value, $allowed, true) ? $value : 'default';
        
        // Clear the option cache to ensure fresh read on next page load
        wp_cache_delete('simple_lms_language', 'options');
        
        // Force reload translations after language change
        // This ensures the new language is loaded immediately
        if ($sanitized !== \get_option('simple_lms_language')) {
            global $l10n;
            
            // Clear any cached translations
            \unload_textdomain('simple-lms');
            
            // Remove from global $l10n array
            if (isset($l10n['simple-lms'])) {
                unset($l10n['simple-lms']);
            }
            
            // Clear WordPress translation cache
            wp_cache_delete('simple-lms', 'translations');
            wp_cache_flush();
            
            // Set a flag to reload translations on next page load
            \add_action('init', function() {
                simpleLmsLoadTranslations();
            }, 999);
        }
        
        return $sanitized;
    }

    /**
     * Render general settings section
     */
    public function render_general_section(): void
    {
        echo '<p>' . \esc_html__('Configure basic Simple LMS plugin settings.', 'simple-lms') . '</p>';
    }

    /**
     * Render language field
     */
    public function render_language_field(): void
    {
        $current = \get_option('simple_lms_language', 'default');
        ?>
        <select name="simple_lms_language" id="simple_lms_language">
            <option value="default" <?php \selected($current, 'default'); ?>>
                Default (WordPress language)
            </option>
            <option value="en_US" <?php \selected($current, 'en_US'); ?>>
                English
            </option>
            <option value="pl_PL" <?php \selected($current, 'pl_PL'); ?>>
                Polski
            </option>
        </select>
        <p class="description">
            <?php \esc_html_e('Choose the language for the Simple LMS plugin interface.', 'simple-lms'); ?>
        </p>
        <p class="description" style="margin-top: 10px; color: #666;">
            <em><?php \esc_html_e('Note: Changes take effect immediately on the next page load.', 'simple-lms'); ?></em>
        </p>
        <?php
    }

    /**
     * Render verbose logging field
     */
    public function render_verbose_logging_field(): void
    {
        $current = \get_option('simple_lms_verbose_logging', 'no');
        ?>
        <label>
            <input type="checkbox" name="simple_lms_verbose_logging" value="yes" <?php \checked($current, 'yes'); ?>>
            <?php \esc_html_e('Enable detailed debug logs (recommended only for troubleshooting).', 'simple-lms'); ?>
        </label>
        <p class="description">
            <?php \esc_html_e('When enabled, the plugin logs additional diagnostic information even if WP_DEBUG is off.', 'simple-lms'); ?>
        </p>
        <?php
    }

    /**
     * Render delete data on uninstall field
     */
    public function render_delete_data_field(): void
    {
        $current = \get_option('simple_lms_delete_data_on_uninstall', 'no');
        ?>
        <label>
            <input type="checkbox" name="simple_lms_delete_data_on_uninstall" value="yes" <?php \checked($current, 'yes'); ?>>
            <?php \esc_html_e('Delete all courses, modules, lessons and plugin data when uninstalling', 'simple-lms'); ?>
        </label>
        <p class="description">
            <?php \esc_html_e('If enabled, all plugin data will be permanently deleted when you uninstall the plugin. If disabled, your data will be preserved.', 'simple-lms'); ?>
        </p>
        <p class="description" style="margin-top: 8px; color: #d63638; font-weight: 500;">
            <strong><?php \esc_html_e('Warning: This action cannot be undone!', 'simple-lms'); ?></strong>
        </p>
        <?php
    }
    
    /**
     * Render course settings section
     */
    public function render_course_section(): void
    {
        echo '<p>' . \esc_html__('Configure global settings for all courses.', 'simple-lms') . '</p>';
        echo '<p><em>' . \esc_html__('Course-specific settings will be added here in future updates.', 'simple-lms') . '</em></p>';
    }
    
    /**
     * Render module & lesson settings section
     */
    public function render_module_lesson_section(): void
    {
        echo '<p>' . \esc_html__('Configure global settings for modules and lessons.', 'simple-lms') . '</p>';
        echo '<p><em>' . \esc_html__('Module and lesson-specific settings will be added here in future updates.', 'simple-lms') . '</em></p>';
    }

    /**
     * Render settings page
     */
    public function render_settings_page(): void
    {
        if (!\current_user_can('manage_options')) {
            return;
        }

        // Save success message
        if (isset($_GET['settings-updated'])) {
            \add_settings_error(
                'simple_lms_messages',
                'simple_lms_message',
                \__('Settings saved successfully.', 'simple-lms'),
                'updated'
            );
        }

        \settings_errors('simple_lms_messages');
        ?>
        <div class="wrap">
            <h1><?php echo \esc_html(\get_admin_page_title()); ?></h1>
            
            <form action="options.php" method="post">
                <?php
                \settings_fields('simple_lms_settings');
                \do_settings_sections('simple-lms-settings');
                \submit_button(\__('Save Settings', 'simple-lms'));
                ?>
            </form>
        </div>
        <?php
    }
}

