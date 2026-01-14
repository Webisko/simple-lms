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
            ->addAction('admin_init', [$this, 'register_settings'])
            ->addAction('init', [$this, 'load_plugin_textdomain'], 1);
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
     * Load plugin text domain for translations
     */
    public function load_plugin_textdomain(): void
    {
        $locale = \determine_locale();
        $language_setting = \get_option('simple_lms_language', 'default');
        
        // Override locale if user selected specific language
        if ($language_setting === 'en_US') {
            $locale = 'en_US';
        } elseif ($language_setting === 'pl_PL') {
            $locale = 'pl_PL';
        } elseif ($language_setting === 'de_DE') {
            $locale = 'de_DE';
        }
        // 'default' uses WordPress locale
        
        \load_plugin_textdomain(
            'simple-lms',
            false,
            dirname(SIMPLE_LMS_PLUGIN_BASENAME) . '/languages'
        );
        
        // Force specific locale if set (build path dynamically from plugin basename)
        if ($language_setting !== 'default') {
            $moPath = WP_PLUGIN_DIR . '/' . dirname(SIMPLE_LMS_PLUGIN_BASENAME) . '/languages/simple-lms-' . $locale . '.mo';
            if (file_exists($moPath)) {
                \load_textdomain('simple-lms', $moPath);
            }
        }
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
        
        // Analytics Settings
        \register_setting('simple_lms_settings', 'simple_lms_analytics_enabled', ['type' => 'boolean', 'default' => false]);
        \register_setting('simple_lms_settings', 'simple_lms_ga4_enabled', ['type' => 'boolean', 'default' => false]);
        \register_setting('simple_lms_settings', 'simple_lms_ga4_measurement_id', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
        \register_setting('simple_lms_settings', 'simple_lms_ga4_api_secret', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
        
        // Privacy & Data Retention Settings
        \register_setting('simple_lms_settings', 'simple_lms_analytics_retention_days', [
            'type' => 'integer',
            'default' => 365,
            'sanitize_callback' => [$this, 'sanitize_retention_days']
        ]);
        \register_setting('simple_lms_settings', 'simple_lms_keep_data_on_uninstall', [
            'type' => 'boolean',
            'default' => false
        ]);
        
        \add_settings_section(
            'simple_lms_analytics_section',
            \__('Analytics Settings', 'simple-lms'),
            [$this, 'render_analytics_section'],
            'simple-lms-settings'
        );
        
        \add_settings_field(
            'simple_lms_analytics_enabled',
            \__('Enable Analytics', 'simple-lms'),
            [$this, 'render_analytics_enabled_field'],
            'simple-lms-settings',
            'simple_lms_analytics_section'
        );
        
        \add_settings_field(
            'simple_lms_ga4_enabled',
            \__('Google Analytics 4', 'simple-lms'),
            [$this, 'render_ga4_enabled_field'],
            'simple-lms-settings',
            'simple_lms_analytics_section'
        );
        
        \add_settings_field(
            'simple_lms_ga4_measurement_id',
            \__('GA4 Measurement ID', 'simple-lms'),
            [$this, 'render_ga4_measurement_id_field'],
            'simple-lms-settings',
            'simple_lms_analytics_section'
        );
        
        \add_settings_field(
            'simple_lms_ga4_api_secret',
            \__('GA4 API Secret', 'simple-lms'),
            [$this, 'render_ga4_api_secret_field'],
            'simple-lms-settings',
            'simple_lms_analytics_section'
        );
        
        // Privacy & Data Retention Section
        \add_settings_section(
            'simple_lms_privacy_section',
            \__('Privacy & Data Retention', 'simple-lms'),
            [$this, 'render_privacy_section'],
            'simple-lms-settings'
        );
        
        \add_settings_field(
            'simple_lms_analytics_retention_days',
            \__('Analytics Data Retention', 'simple-lms'),
            [$this, 'render_retention_field'],
            'simple-lms-settings',
            'simple_lms_privacy_section'
        );
        
        \add_settings_field(
            'simple_lms_keep_data_on_uninstall',
            \__('Keep Data on Uninstall', 'simple-lms'),
            [$this, 'render_keep_data_field'],
            'simple-lms-settings',
            'simple_lms_privacy_section'
        );
    }

    /**
     * Sanitize language setting
     */
    public function sanitize_language($value): string
    {
        $allowed = ['default', 'en_US', 'pl_PL', 'de_DE'];
        return in_array($value, $allowed, true) ? $value : 'default';
    }
    
    /**
     * Sanitize retention days setting
     */
    public function sanitize_retention_days($value): int
    {
        $allowed = [90, 180, 365, -1]; // -1 = unlimited
        $value = (int) $value;
        return in_array($value, $allowed, true) ? $value : 365;
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
                <?php \esc_html_e('Default (WordPress language)', 'simple-lms'); ?>
            </option>
            <option value="en_US" <?php \selected($current, 'en_US'); ?>>
                <?php \esc_html_e('English', 'simple-lms'); ?>
            </option>
            <option value="pl_PL" <?php \selected($current, 'pl_PL'); ?>>
                <?php \esc_html_e('Polish', 'simple-lms'); ?>
            </option>
            <option value="de_DE" <?php \selected($current, 'de_DE'); ?>>
                <?php \esc_html_e('German', 'simple-lms'); ?>
            </option>
        </select>
        <p class="description">
            <?php \esc_html_e('Choose the language for the Simple LMS plugin interface.', 'simple-lms'); ?>
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
     * Render analytics settings section
     */
    public function render_analytics_section(): void
    {
        echo '<p>' . \esc_html__('Track user learning events and integrate with external analytics platforms.', 'simple-lms') . '</p>';
    }
    
    /**
     * Render analytics enabled field
     */
    public function render_analytics_enabled_field(): void
    {
        $enabled = \get_option('simple_lms_analytics_enabled', false);
        ?>
        <label>
            <input type="checkbox" name="simple_lms_analytics_enabled" value="1" <?php \checked($enabled, 1); ?>>
            <?php \esc_html_e('Enable event tracking', 'simple-lms'); ?>
        </label>
        <p class="description">
            <?php \esc_html_e('Stores learning events (lesson started, completed, milestones) in database.', 'simple-lms'); ?>
        </p>
        <?php
    }
    
    /**
     * Render GA4 enabled field
     */
    public function render_ga4_enabled_field(): void
    {
        $enabled = \get_option('simple_lms_ga4_enabled', false);
        ?>
        <label>
            <input type="checkbox" name="simple_lms_ga4_enabled" value="1" <?php \checked($enabled, 1); ?>>
            <?php \esc_html_e('Send events to Google Analytics 4', 'simple-lms'); ?>
        </label>
        <p class="description">
            <?php \esc_html_e('Requires Measurement ID and API Secret below.', 'simple-lms'); ?>
        </p>
        <?php
    }
    
    /**
     * Render GA4 Measurement ID field
     */
    public function render_ga4_measurement_id_field(): void
    {
        $value = \get_option('simple_lms_ga4_measurement_id', '');
        ?>
        <input type="text" name="simple_lms_ga4_measurement_id" value="<?php echo \esc_attr($value); ?>" class="regular-text" placeholder="G-XXXXXXXXXX">
        <p class="description">
            <?php \esc_html_e('Find in GA4: Admin → Data Streams → Web → Measurement ID', 'simple-lms'); ?>
        </p>
        <?php
    }
    
    /**
     * Render GA4 API Secret field
     */
    public function render_ga4_api_secret_field(): void
    {
        $value = \get_option('simple_lms_ga4_api_secret', '');
        ?>
        <input type="text" name="simple_lms_ga4_api_secret" value="<?php echo \esc_attr($value); ?>" class="regular-text">
        <p class="description">
            <?php \esc_html_e('Find in GA4: Admin → Data Streams → Web → Measurement Protocol API secrets', 'simple-lms'); ?>
        </p>
        <?php
    }
    
    /**
     * Render privacy settings section
     */
    public function render_privacy_section(): void
    {
        echo '<p>' . \esc_html__('Configure GDPR compliance and data retention policies.', 'simple-lms') . '</p>';
    }
    
    /**
     * Render analytics retention field
     */
    public function render_retention_field(): void
    {
        $current = \get_option('simple_lms_analytics_retention_days', 365);
        ?>
        <select name="simple_lms_analytics_retention_days" id="simple_lms_analytics_retention_days">
            <option value="90" <?php \selected($current, 90); ?>>
                <?php \esc_html_e('90 days', 'simple-lms'); ?>
            </option>
            <option value="180" <?php \selected($current, 180); ?>>
                <?php \esc_html_e('180 days', 'simple-lms'); ?>
            </option>
            <option value="365" <?php \selected($current, 365); ?>>
                <?php \esc_html_e('1 year (365 days)', 'simple-lms'); ?>
            </option>
            <option value="-1" <?php \selected($current, -1); ?>>
                <?php \esc_html_e('Unlimited (keep all data)', 'simple-lms'); ?>
            </option>
        </select>
        <p class="description">
            <?php \esc_html_e('Older analytics events will be automatically deleted. Compliance with GDPR data minimization.', 'simple-lms'); ?>
        </p>
        <?php
    }
    
    /**
     * Render keep data on uninstall field
     */
    public function render_keep_data_field(): void
    {
        $enabled = \get_option('simple_lms_keep_data_on_uninstall', false);
        ?>
        <label>
            <input type="checkbox" name="simple_lms_keep_data_on_uninstall" value="1" <?php \checked($enabled, 1); ?>>
            <?php \esc_html_e('Preserve all plugin data when uninstalling', 'simple-lms'); ?>
        </label>
        <p class="description">
            <?php \esc_html_e('If enabled, courses, lessons, user progress, and settings will NOT be deleted when the plugin is uninstalled.', 'simple-lms'); ?>
        </p>
        <?php
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

            <hr style="margin: 30px 0;">

            <h2><?php \esc_html_e('Translation Status', 'simple-lms'); ?></h2>
            <table class="widefat" style="max-width: 600px;">
                <thead>
                    <tr>
                        <th><?php \esc_html_e('Language', 'simple-lms'); ?></th>
                        <th><?php \esc_html_e('Status', 'simple-lms'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php \esc_html_e('English', 'simple-lms'); ?> (en_US)</td>
                        <td>
                            <?php
                            $en_file = WP_PLUGIN_DIR . '/' . dirname(SIMPLE_LMS_PLUGIN_BASENAME) . '/languages/simple-lms-en_US.po';
                            if (file_exists($en_file)) {
                                echo '<span style="color: green;">✓ ' . \esc_html__('Available', 'simple-lms') . '</span>';
                            } else {
                                echo '<span style="color: orange;">○ ' . \esc_html__('Not available', 'simple-lms') . '</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php \esc_html_e('Polish', 'simple-lms'); ?> (pl_PL)</td>
                        <td>
                            <?php
                            $pl_file = WP_PLUGIN_DIR . '/' . dirname(SIMPLE_LMS_PLUGIN_BASENAME) . '/languages/simple-lms-pl_PL.po';
                            if (file_exists($pl_file)) {
                                echo '<span style="color: green;">✓ ' . \esc_html__('Available', 'simple-lms') . '</span>';
                            } else {
                                echo '<span style="color: orange;">○ ' . \esc_html__('Not available', 'simple-lms') . '</span>';
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="description" style="max-width: 600px; margin-top: 15px;">
                <?php \esc_html_e('Translation files (.po) need to be compiled to .mo files to work. Use Poedit or Loco Translate plugin to compile them.', 'simple-lms'); ?>
            </p>
        </div>
        <?php
    }
}

