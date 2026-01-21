<?php

/**
 * Plugin Name: Simple LMS
 * Plugin URI:  https://webisko.pl/simple-lms
 * Description: LMS plugin for managing courses, modules, and lessons with WooCommerce integration for course sales.
 * Version:     1.0.0
 * Author:      Filip Meyer-Lüters
 * Author URI:  https://webisko.pl
 * License:     GPL2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-lms
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 *
 * @package SimpleLMS
 * @version 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SIMPLE_LMS_VERSION', '1.0.0');
define('SIMPLE_LMS_PLUGIN_DIR', \plugin_dir_path(__FILE__));
define('SIMPLE_LMS_PLUGIN_URL', \plugin_dir_url(__FILE__));
define('SIMPLE_LMS_PLUGIN_BASENAME', \plugin_basename(__FILE__));

// ALWAYS load core classes - no matter if Composer exists or not
// This ensures the plugin works in all environments
// CORE CLASSES (required for plugin boot)
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-service-container.php';
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/interface-logger.php';
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-logger.php';
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-error-handler.php';
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-security-service.php';

// MANAGERS (required for plugin boot)
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/managers/HookManager.php';
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/managers/AssetManager.php';
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/managers/CPTManager.php';

// ADDITIONAL CORE CLASSES (will be loaded later but good to load early)
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-cache-handler.php';
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-progress-tracker.php';
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-analytics-tracker.php';

// Load Composer autoloader if available (optional PSR-4 optimization)
$simple_lms_autoload_file = SIMPLE_LMS_PLUGIN_DIR . 'vendor/autoload.php';
if (file_exists($simple_lms_autoload_file)) {
    require_once $simple_lms_autoload_file;
}

/**
 * Main Plugin Class (refactored with ServiceContainer)
 *
 * This class now uses dependency injection instead of Singleton pattern.
 * Services are managed through ServiceContainer for better testability.
 */
class SimpleLMSPlugin
{
    /**
     * Service container instance
     *
     * @var \SimpleLMS\ServiceContainer
     */
    private \SimpleLMS\ServiceContainer $container;

    /**
     * Constructor - initialize with service container
     *
     * @param \SimpleLMS\ServiceContainer $container Service container
     */
    public function __construct(\SimpleLMS\ServiceContainer $container)
    {
        $this->container = $container;
    }

    /**
     * Bootstrap the plugin
     *
     * @return void
     */
    public function boot(): void
    {
        // Register core services
        $this->registerServices();

        // Initialize hooks via HookManager
        $this->registerHooks();

        // Load plugin files immediately (we're already in plugins_loaded hook)
        $this->loadPluginFiles();

        // Register services that depend on loaded files
        $this->registerLateServices();

        // Initialize plugin
        \add_action('init', [$this, 'init'], 0);

        // Register global error handler early
        if ($this->container->has(\SimpleLMS\Error_Handler::class)) {
            /** @var \SimpleLMS\Error_Handler $eh */
            $eh = $this->container->get(\SimpleLMS\Error_Handler::class);
            $eh->register();
        }
    }

    /**
     * Register all services in the container
     *
     * @return void
     */
    private function registerServices(): void
    {
        $container = $this->container;

        // Register HookManager (singleton)
        $container->singleton(\SimpleLMS\Managers\HookManager::class, function ($c) {
            return new \SimpleLMS\Managers\HookManager($c);
        });

        // Register AssetManager (singleton)
        $container->singleton(\SimpleLMS\Managers\AssetManager::class, function ($c) {
            return new \SimpleLMS\Managers\AssetManager(
                SIMPLE_LMS_PLUGIN_DIR,
                SIMPLE_LMS_PLUGIN_URL,
                SIMPLE_LMS_VERSION,
                $c->get(\SimpleLMS\Logger::class)
            );
        });

        // Register CPTManager (singleton)
        $container->singleton(\SimpleLMS\Managers\CPTManager::class, function ($c) {
            return new \SimpleLMS\Managers\CPTManager($c->get(\SimpleLMS\Logger::class));
        });

        // Register Logger (singleton)
        $container->singleton(\SimpleLMS\Logger::class, function ($c) {
            // Disable verbose logging by default to avoid performance issues.
            // Enable only via filter when explicitly needed.
            $debugEnabled = (bool) \apply_filters('simple_lms_debug_enabled', false);
            return new \SimpleLMS\Logger('simple-lms', $debugEnabled);
        });

        // Register Error Handler (singleton)
        $container->singleton(\SimpleLMS\Error_Handler::class, function ($c) {
            return new \SimpleLMS\Error_Handler($c->get(\SimpleLMS\Logger::class));
        });

        // Register core classes (will be instantiated when needed)
        $this->registerCoreServices();
    }

    /**
     * Register core plugin services
     *
     * @return void
     */
    private function registerCoreServices(): void
    {
        $container = $this->container;

        // Progress Tracker
        $container->singleton('SimpleLMS\\Progress_Tracker', function ($c) {
            return new \SimpleLMS\Progress_Tracker($c->get(\SimpleLMS\Logger::class));
        });

        // Cache Handler
        $container->singleton('SimpleLMS\\Cache_Handler', function ($c) {
            return new \SimpleLMS\Cache_Handler();
        });

        // Access Control (refactored to instance with Logger)
        $container->singleton('SimpleLMS\\Access_Control', function ($c) {
            return new \SimpleLMS\Access_Control($c->get(\SimpleLMS\Logger::class));
        });

        // Security Service (nonce + capability helpers) - loaded early
        $container->singleton(\SimpleLMS\Security_Service::class, function ($c) {
            return new \SimpleLMS\Security_Service();
        });

        // REST API (with DI: Logger + Security_Service)
        $container->singleton('SimpleLMS\\Rest_API', function ($c) {
            return new \SimpleLMS\Rest_API(
                $c->get(\SimpleLMS\Logger::class),
                $c->get(\SimpleLMS\Security_Service::class)
            );
        });

        // WooCommerce Integration - Lazily loaded on woocommerce_loaded hook
        // Do not instantiate here - will be registered in loadPluginFiles()

        // Analytics Tracker
        $container->singleton('SimpleLMS\\Analytics_Tracker', function ($c) {
            return new \SimpleLMS\Analytics_Tracker($c->has(\SimpleLMS\Logger::class) ? $c->get(\SimpleLMS\Logger::class) : null);
        });
    }

    /**
     * Register services that depend on files loaded via loadPluginFiles()
     * This is called AFTER loadPluginFiles() via plugins_loaded hook
     *
     * @return void
     */
    public function registerLateServices(): void
    {
        $container = $this->container;

        // Settings (requires class-settings.php)
          $container->singleton('SimpleLMS\\Settings', function ($c) {
              return new \SimpleLMS\Settings($c->get(\SimpleLMS\Managers\HookManager::class));
          });

        // Meta Boxes (requires custom-meta-boxes.php)
          $container->singleton('SimpleLMS\\Meta_Boxes', function ($c) {
              return new \SimpleLMS\Meta_Boxes($c->get(\SimpleLMS\Managers\HookManager::class));
          });

        // Admin Customizations (requires admin-customizations.php)
        $container->singleton('SimpleLMS\\Admin_Customizations', function ($c) {
            return new \SimpleLMS\Admin_Customizations($c->get(\SimpleLMS\Managers\HookManager::class));
        });

        // AJAX Handler (requires ajax-handlers.php)
        $container->singleton('SimpleLMS\\Ajax_Handler', function ($c) {
            return new \SimpleLMS\Ajax_Handler(
                $c->has(\SimpleLMS\Logger::class) ? $c->get(\SimpleLMS\Logger::class) : null,
                $c->has(\SimpleLMS\Security_Service::class) ? $c->get(\SimpleLMS\Security_Service::class) : null
            );
        });

        // Privacy Handlers (requires class-privacy-handlers.php)
        $container->singleton('SimpleLMS\\Privacy_Handlers', function ($c) {
            return new \SimpleLMS\Privacy_Handlers($c->has(\SimpleLMS\Logger::class) ? $c->get(\SimpleLMS\Logger::class) : null);
        });

        // Analytics Retention (requires class-analytics-retention.php)
        $container->singleton('SimpleLMS\\Analytics_Retention', function ($c) {
            return new \SimpleLMS\Analytics_Retention($c->has(\SimpleLMS\Logger::class) ? $c->get(\SimpleLMS\Logger::class) : null);
        });

        // Access Meta Boxes (requires class-access-meta-boxes.php)
        $container->singleton('SimpleLMS\\Access_Meta_Boxes', function ($c) {
            return new \SimpleLMS\Access_Meta_Boxes();
        });
    }

    /**
     * Register WordPress hooks via HookManager
     *
     * @return void
     */
    private function registerHooks(): void
    {
        /** @var \SimpleLMS\Managers\HookManager $hookManager */
        $hookManager = $this->container->get(\SimpleLMS\Managers\HookManager::class);

        /** @var \SimpleLMS\Managers\AssetManager $assetManager */
        $assetManager = $this->container->get(\SimpleLMS\Managers\AssetManager::class);

        // Frontend assets
        $hookManager->addAction(
            'wp_enqueue_scripts',
            [$assetManager, 'enqueueFrontendAssets']
        );

        // Admin assets
        if (\is_admin()) {
            $hookManager->addAction(
                'admin_enqueue_scripts',
                [$assetManager, 'enqueueAdminAssets']
            );

            // Plugin action links
            $hookManager->addFilter(
                'plugin_action_links_' . SIMPLE_LMS_PLUGIN_BASENAME,
                [$this, 'addPluginActionLinks']
            );
        }

        // Cascade delete: when course/module is deleted, delete all children
        $hookManager->addAction(
            'before_delete_post',
            [$this, 'cascadeDeleteChildren'],
            10,
            2
        );
    }

    /**
     * Load required plugin files
     *
     * @return void
     */
    public function loadPluginFiles(): void
    {
        $files = [
            'includes/class-settings.php',
            'includes/class-cache-handler.php',
            'includes/access-control.php',
            'includes/class-access-meta-boxes.php',
            'includes/custom-meta-boxes.php',
            'includes/admin-customizations.php',
            'includes/ajax-handlers.php',
            'includes/class-rest-api-refactored.php',
            'includes/class-progress-tracker.php',
            'includes/class-woocommerce-integration.php',
            'includes/class-analytics-tracker.php',
            'includes/class-analytics-retention.php',
            'includes/class-privacy-handlers.php',
            'includes/compat/polylang-wpml-compat.php',
            'includes/compat/translatepress-compat.php',
            'includes/compat/weglot-compat.php',
            'includes/compat/qtranslate-compat.php',
            'includes/compat/multilingualpress-compat.php',
            'includes/compat/gtranslate-compat.php',
        ];

        // Load files - classes are auto-loaded via Composer PSR-4
        // Files are required for WordPress hooks and compatibility code
        foreach ($files as $file) {
            $filePath = SIMPLE_LMS_PLUGIN_DIR . $file;
            if (file_exists($filePath)) {
                require_once $filePath;
            }
        }

        // Register WooCommerce integration on woocommerce_loaded hook
        // This ensures WooCommerce is fully initialized before our integration hooks in
        \add_action('woocommerce_loaded', [$this, 'registerWooCommerceIntegration'], 10);

        // Lazy-load Elementor integration only when Elementor is active
        \add_action('elementor_loaded', [$this, 'registerElementorIntegration']);

        // ALSO try elementor/init as backup
        \add_action('elementor/init', [$this, 'registerElementorIntegration'], 1);

        // Lazy-load Bricks integration only when Bricks is active
        \add_action('bricks_init', [$this, 'registerBricksIntegration']);
    }

    /**
     * Register WooCommerce integration on woocommerce_loaded hook
     *
     * Called only when WooCommerce is fully loaded and active
     *
     * @return void
     */
    public function registerWooCommerceIntegration(): void
    {
        $container = $this->container;

        if (!$container->has('SimpleLMS\\WooCommerce_Integration')) {
            // Register WooCommerce Integration service
            $container->singleton('SimpleLMS\\WooCommerce_Integration', function ($c) {
                return new \SimpleLMS\WooCommerce_Integration(
                    $c->has(\SimpleLMS\Logger::class) ? $c->get(\SimpleLMS\Logger::class) : null,
                    $c->has(\SimpleLMS\Security_Service::class) ? $c->get(\SimpleLMS\Security_Service::class) : null
                );
            });
        }

        if ($container->has('SimpleLMS\\WooCommerce_Integration')) {
            $container->get('SimpleLMS\\WooCommerce_Integration')->register();
        }
    }

    /**
     * Register Elementor integration
     *
     * Called only when Elementor is loaded and active
     *
     * @return void
     */
    public function registerElementorIntegration(): void
    {
        if (!$this->shouldLoadElementorIntegration()) {
            return;
        }

        $elemTags = SIMPLE_LMS_PLUGIN_DIR . 'includes/elementor-dynamic-tags/class-elementor-dynamic-tags.php';
        $elemGuard = SIMPLE_LMS_PLUGIN_DIR . 'includes/compat/elementor-embed-guard.php';

        if (file_exists($elemTags)) {
            require_once $elemTags;
            if (class_exists('SimpleLMS\Elementor\Elementor_Dynamic_Tags')) {
                \SimpleLMS\Elementor\Elementor_Dynamic_Tags::init();
            }
        }

        if (file_exists($elemGuard)) {
            require_once $elemGuard;
            if (class_exists('SimpleLMS\Elementor\Elementor_Embed_Guard')) {
                \SimpleLMS\Elementor\Elementor_Embed_Guard::init();
            }
        }
    }

    /**
     * Determine if Elementor widgets/tags should be loaded for this request.
     *
     * @return bool
     */
    private function shouldLoadElementorIntegration(): bool
    {
        if (!class_exists('\Elementor\Plugin')) {
            return false;
        }

        $plugin = \Elementor\Plugin::instance();

        if (isset($plugin->editor) && method_exists($plugin->editor, 'is_edit_mode') && $plugin->editor->is_edit_mode()) {
            return true;
        }

        if (isset($plugin->preview) && method_exists($plugin->preview, 'is_preview_mode') && $plugin->preview->is_preview_mode()) {
            return true;
        }

        $post_id = get_queried_object_id();
        if ($post_id && isset($plugin->db) && method_exists($plugin->db, 'is_built_with_elementor')) {
            return (bool) $plugin->db->is_built_with_elementor($post_id);
        }

        return false;
    }

    /**
     * Register Bricks integration
     *
     * Called only when Bricks is loaded and active
     *
     * @return void
     */
    public function registerBricksIntegration(): void
    {
        if (!$this->shouldLoadBricksIntegration()) {
            return;
        }

        $bricks = SIMPLE_LMS_PLUGIN_DIR . 'includes/bricks/class-bricks-integration.php';

        if (file_exists($bricks)) {
            require_once $bricks;
            if (class_exists('SimpleLMS\Bricks\Bricks_Integration')) {
                \SimpleLMS\Bricks\Bricks_Integration::init();
            }
        }
    }

    /**
     * Determine if Bricks elements should be loaded for this request.
     *
     * @return bool
     */
    private function shouldLoadBricksIntegration(): bool
    {
        if (!class_exists('Bricks\Database')) {
            return false;
        }

        if (function_exists('bricks_is_builder') && bricks_is_builder()) {
            return true;
        }

        if (class_exists('Bricks\Helpers')) {
            if (method_exists('Bricks\Helpers', 'is_builder') && \Bricks\Helpers::is_builder()) {
                return true;
            }

            if (method_exists('Bricks\Helpers', 'is_builder_call') && \Bricks\Helpers::is_builder_call()) {
                return true;
            }

            if (method_exists('Bricks\Helpers', 'is_preview') && \Bricks\Helpers::is_preview()) {
                return true;
            }
        }

        $post_id = get_queried_object_id();
        if ($post_id) {
            $meta_keys = [
                'bricks_data',
                '_bricks_data',
                'bricks_page_content',
                '_bricks_page_content',
                'bricks_template',
                '_bricks_template',
            ];

            foreach ($meta_keys as $meta_key) {
                $value = get_post_meta($post_id, $meta_key, true);
                if (!empty($value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Initialize plugin functionality (with DI)
     *
     * @return void
     */
    public function init(): void
    {
        \do_action('simple_lms_before_init');

        /** @var \SimpleLMS\Managers\CPTManager $cptManager */
        $cptManager = $this->container->get(\SimpleLMS\Managers\CPTManager::class);
        $cptManager->registerPostTypes();

        // Initialize services that have static init() methods (legacy compatibility)
        // TODO: Refactor these to use proper dependency injection
        $this->initLegacyServices();

        \do_action('simple_lms_after_init');
    }

    /**
     * Initialize legacy services (temporary until full refactor)
     *
     * @return void
     */
    private function initLegacyServices(): void
    {
        // Initialize refactored services via container
        if ($this->container->has('SimpleLMS\\Settings')) {
            $this->container->get('SimpleLMS\\Settings');
        }

        if ($this->container->has('SimpleLMS\\Meta_Boxes')) {
            $this->container->get('SimpleLMS\\Meta_Boxes');
        }

        if ($this->container->has('SimpleLMS\\Admin_Customizations')) {
            $this->container->get('SimpleLMS\\Admin_Customizations');
        }

        // These still use static init() - will be refactored in next iteration

        if ($this->container->has('SimpleLMS\\Ajax_Handler')) {
            $this->container->get('SimpleLMS\\Ajax_Handler')->register();
        }

        // REST API - now uses HookManager for endpoint registration
        if ($this->container->has('SimpleLMS\\Rest_API')) {
            /** @var \SimpleLMS\Rest_API $restApi */
            $restApi = $this->container->get('SimpleLMS\\Rest_API');
            /** @var \SimpleLMS\Managers\HookManager $hookManager */
            $hookManager = $this->container->get(\SimpleLMS\Managers\HookManager::class);
            $hookManager->addAction('rest_api_init', [$restApi, 'registerEndpoints']);
        }

        if ($this->container->has('SimpleLMS\\Progress_Tracker')) {
            $this->container->get('SimpleLMS\\Progress_Tracker')->register();
        }

        if ($this->container->has('SimpleLMS\\Access_Control')) {
            $this->container->get('SimpleLMS\\Access_Control')->register();
        }

        if ($this->container->has('SimpleLMS\\WooCommerce_Integration')) {
            $this->container->get('SimpleLMS\\WooCommerce_Integration')->register();
        }

        if ($this->container->has('SimpleLMS\\Analytics_Tracker')) {
            $this->container->get('SimpleLMS\\Analytics_Tracker')->register();
        }

        if ($this->container->has('SimpleLMS\\Analytics_Retention')) {
            $this->container->get('SimpleLMS\\Analytics_Retention')->register();
        }

        if ($this->container->has('SimpleLMS\\Privacy_Handlers')) {
            $this->container->get('SimpleLMS\\Privacy_Handlers')->register();
        }

        // WooCommerce, Elementor, Bricks integrations now registered on their respective hooks
        // See: registerWooCommerceIntegration(), registerElementorIntegration(), registerBricksIntegration()

        if (class_exists('SimpleLMS\Cache_Handler')) {
            \SimpleLMS\Cache_Handler::init();
        }
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing links
     * @return array Modified links
     */
    public function addPluginActionLinks(array $links): array
    {
        $pluginLinks = [
            '<a href="' . \admin_url('edit.php?post_type=course') . '">' . \__('Kursy', 'simple-lms') . '</a>',
        ];

        return array_merge($pluginLinks, $links);
    }

    /**
     * Get service container
     *
     * @return \SimpleLMS\ServiceContainer
     */
    public function getContainer(): \SimpleLMS\ServiceContainer
    {
        return $this->container;
    }

    /**     * Cascade delete children when a course or module is deleted
     * When deleting a course: delete all modules and lessons
     * When deleting a module: delete all lessons
     *
     * @param int $postId Post ID being deleted
     * @param \WP_Post $post Post object being deleted
     * @return void
     */
    public function cascadeDeleteChildren(int $postId, \WP_Post $post): void
    {
        // Only process our custom post types
        if (!in_array($post->post_type, ['course', 'module'], true)) {
            return;
        }

        // Prevent recursion
        static $processing = [];
        if (isset($processing[$postId])) {
            return;
        }
        $processing[$postId] = true;

        try {
            if ($post->post_type === 'course') {
                // Delete all modules in this course
                $modules = \get_posts([
                    'post_type'      => 'module',
                    'posts_per_page' => -1,
                    'post_status'    => 'any',
                    'meta_key'       => 'parent_course',
                    'meta_value'     => $postId,
                    'fields'         => 'ids'
                ]);

                foreach ($modules as $moduleId) {
                    // This will trigger cascade delete for module's lessons
                    \wp_delete_post($moduleId, true);
                }
            } elseif ($post->post_type === 'module') {
                // Delete all lessons in this module
                $lessons = \get_posts([
                    'post_type'      => 'lesson',
                    'posts_per_page' => -1,
                    'post_status'    => 'any',
                    'meta_key'       => 'parent_module',
                    'meta_value'     => $postId,
                    'fields'         => 'ids'
                ]);

                foreach ($lessons as $lessonId) {
                    \wp_delete_post($lessonId, true);
                }
            }
        } finally {
            unset($processing[$postId]);
        }
    }

    /**     * Plugin activation hook
     *
     * @return void
     */
    public static function activate(): void
    {
        $container = \SimpleLMS\ServiceContainer::getInstance();

        // Register and flush CPT rewrites
        if ($container->has(\SimpleLMS\Managers\CPTManager::class)) {
            /** @var \SimpleLMS\Managers\CPTManager $cptManager */
            $cptManager = $container->get(\SimpleLMS\Managers\CPTManager::class);
            $cptManager->flushRewrites();
        }

        \do_action('simple_lms_activated');
    }

    /**
     * Plugin deactivation hook
     *
     * @return void
     */
    public static function deactivate(): void
    {
        // Cleanup scheduled cron jobs
        if (class_exists('SimpleLMS\Analytics_Retention')) {
            \SimpleLMS\Analytics_Retention::deactivate_cleanup_cron();
        }

        \flush_rewrite_rules();
        \do_action('simple_lms_deactivated');
    }
}

/**
 * Load plugin translations
 * Hooked to 'init' priority 999 to ensure all plugins have registered their settings first
 *
 * @return void
 */
function simpleLmsLoadTranslations(): void
{
    global $l10n;

    // Always unload first to ensure clean slate
    \unload_textdomain('simple-lms');

    // Also remove from global $l10n array to force fresh load
    if (isset($l10n['simple-lms'])) {
        unset($l10n['simple-lms']);
    }

    // Clear WordPress translation cache
    wp_cache_delete('simple-lms', 'translations');

    // Get plugin language setting (with proper fallback)
    $language_setting = \get_option('simple_lms_language', 'default');

    // Validate the setting value
    $allowed_languages = ['default', 'en_US', 'pl_PL'];
    if (!in_array($language_setting, $allowed_languages, true)) {
        $language_setting = 'default';
    }

    // For English, explicitly don't load any translation
    if ($language_setting === 'en_US') {
        // English is the default source language - use strings from code
        return;
    }

    // Determine which locale to use
    if ($language_setting === 'pl_PL') {
        // Force Polish
        $locale = 'pl_PL';
    } elseif ($language_setting === 'default') {
        // Use WordPress default locale
        $locale = \get_locale();
    } else {
        $locale = \get_locale();
    }

    // Don't load translation for English
    if ($locale === 'en_US') {
        return;
    }

    // Build paths
    $plugin_dir = dirname(SIMPLE_LMS_PLUGIN_BASENAME);
    $languages_dir = WP_PLUGIN_DIR . '/' . $plugin_dir . '/languages';
    $mofile = $languages_dir . '/simple-lms-' . $locale . '.mo';

    // Load the .mo file if it exists
    if (file_exists($mofile)) {
        \load_textdomain('simple-lms', $mofile);
    }
}

/**
 * Initialize and boot the plugin
 *
 * @return SimpleLMSPlugin
 */
function simpleLmsInit(): SimpleLMSPlugin
{
    $container = \SimpleLMS\ServiceContainer::getInstance();

    // Register Plugin instance in container
    $container->singleton(SimpleLMSPlugin::class, function ($c) {
        return new SimpleLMSPlugin($c);
    });

    /** @var SimpleLMSPlugin $plugin */
    $plugin = $container->get(SimpleLMSPlugin::class);
    $plugin->boot();

    return $plugin;
}

// Load plugin translations on init hook (after all plugins loaded and settings registered)
// This ensures simple_lms_language option can be properly read from the database
\add_action('init', 'simpleLmsLoadTranslations', 1);

// Boot plugin
\add_action('plugins_loaded', 'simpleLmsInit', 5);

// Register activation/deactivation hooks
\register_activation_hook(__FILE__, [SimpleLMSPlugin::class, 'activate']);
\register_deactivation_hook(__FILE__, [SimpleLMSPlugin::class, 'deactivate']);
