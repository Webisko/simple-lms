<?php
/**
 * Plugin Name: Simple LMS
 * Plugin URI:  https://webisko.pl/simple-lms
 * Description: LMS plugin for managing courses, modules, and lessons with WooCommerce integration for course sales.
 * Version:     1.4.0
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
 * @version 1.4.0
 *
 * ARCHITECTURAL CHANGES IN 1.4.0:
 * - Replaced Singleton pattern with PSR-11 ServiceContainer
 * - Implemented Dependency Injection throughout
 * - Refactored static methods to instance methods
 * - Introduced service providers (HookManager, AssetManager, CPTManager)
 * - Improved testability and maintainability
 */

declare(strict_types=1);

namespace SimpleLMS;

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SIMPLE_LMS_VERSION', '1.4.0');
define('SIMPLE_LMS_PLUGIN_DIR', \plugin_dir_path(__FILE__));
define('SIMPLE_LMS_PLUGIN_URL', \plugin_dir_url(__FILE__));
define('SIMPLE_LMS_PLUGIN_BASENAME', \plugin_basename(__FILE__));

// Load ServiceContainer and core dependencies
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-service-container.php';
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/managers/HookManager.php';
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/managers/AssetManager.php';
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/managers/CPTManager.php';
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-logger.php';
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-error-handler.php';
require_once SIMPLE_LMS_PLUGIN_DIR . 'includes/class-security-service.php';

/**
 * Main Plugin Class (refactored with ServiceContainer)
 *
 * This class now uses dependency injection instead of Singleton pattern.
 * Services are managed through ServiceContainer for better testability.
 */
class Plugin
{
    /**
     * Service container instance
     *
     * @var ServiceContainer
     */
    private ServiceContainer $container;

    /**
     * Constructor - initialize with service container
     *
     * @param ServiceContainer $container Service container
     */
    public function __construct(ServiceContainer $container)
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
        if ($this->container->has(Error_Handler::class)) {
            /** @var Error_Handler $eh */
            $eh = $this->container->get(Error_Handler::class);
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
        $container->singleton(Managers\HookManager::class, function ($c) {
            return new Managers\HookManager($c);
        });

        // Register AssetManager (singleton)
        $container->singleton(Managers\AssetManager::class, function ($c) {
            return new Managers\AssetManager(
                SIMPLE_LMS_PLUGIN_DIR,
                SIMPLE_LMS_PLUGIN_URL,
                SIMPLE_LMS_VERSION,
                $c->get(Logger::class)
            );
        });

        // Register CPTManager (singleton)
        $container->singleton(Managers\CPTManager::class, function ($c) {
            return new Managers\CPTManager($c->get(Logger::class));
        });

        // Register Logger (singleton)
        $container->singleton(Logger::class, function ($c) {
            $debug = defined('WP_DEBUG') ? (bool) \WP_DEBUG : false;
            // Allow enabling verbose logging via option or filter
            $verboseOpt = (string) \get_option('simple_lms_verbose_logging', 'no') === 'yes';
            $debugEnabled = (bool) \apply_filters('simple_lms_debug_enabled', ($debug || $verboseOpt));
            return new Logger('simple-lms', $debugEnabled);
        });

        // Register Error Handler (singleton)
        $container->singleton(Error_Handler::class, function ($c) {
            return new Error_Handler($c->get(Logger::class));
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
            return new \SimpleLMS\Progress_Tracker($c->get(Logger::class));
        });

        // Cache Handler
        $container->singleton('SimpleLMS\\Cache_Handler', function ($c) {
            return new \SimpleLMS\Cache_Handler();
        });

        // Access Control (refactored to instance with Logger)
        $container->singleton('SimpleLMS\\Access_Control', function ($c) {
            return new \SimpleLMS\Access_Control($c->get(Logger::class));
        });

        // Security Service (nonce + capability helpers) - loaded early
        $container->singleton(Security_Service::class, function ($c) {
            return new Security_Service();
        });

        // Shortcodes (refactored to instance with Logger)
        $container->singleton('SimpleLMS\\LmsShortcodes', function ($c) {
            return new \SimpleLMS\LmsShortcodes($c->get(Logger::class));
        });

        // REST API (with DI: Logger + Security_Service)
        $container->singleton('SimpleLMS\\Rest_API', function ($c) {
            return new \SimpleLMS\Rest_API(
                $c->get(Logger::class),
                $c->get(Security_Service::class)
            );
        });

        // WooCommerce Integration - Lazily loaded on woocommerce_loaded hook
        // Do not instantiate here - will be registered in loadPluginFiles()

        // Analytics Tracker
        $container->singleton('SimpleLMS\\Analytics_Tracker', function ($c) {
            return new \SimpleLMS\Analytics_Tracker($c->has(Logger::class) ? $c->get(Logger::class) : null);
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
              return new \SimpleLMS\Settings($c->get(Managers\HookManager::class));
        });

        // Meta Boxes (requires custom-meta-boxes.php)
        $container->singleton('SimpleLMS\\Meta_Boxes', function ($c) {
              return new \SimpleLMS\Meta_Boxes($c->get(Managers\HookManager::class));
        });

        // Admin Customizations (requires admin-customizations.php)
        $container->singleton('SimpleLMS\\Admin_Customizations', function ($c) {
            return new \SimpleLMS\Admin_Customizations($c->get(Managers\HookManager::class));
        });

        // AJAX Handler (requires ajax-handlers.php)
        $container->singleton('SimpleLMS\\Ajax_Handler', function ($c) {
            return new \SimpleLMS\Ajax_Handler(
                $c->has(Logger::class) ? $c->get(Logger::class) : null,
                $c->has(Security_Service::class) ? $c->get(Security_Service::class) : null
            );
        });

        // Privacy Handlers (requires class-privacy-handlers.php)
        $container->singleton('SimpleLMS\\Privacy_Handlers', function ($c) {
            return new \SimpleLMS\Privacy_Handlers($c->has(Logger::class) ? $c->get(Logger::class) : null);
        });

        // Analytics Retention (requires class-analytics-retention.php)
        $container->singleton('SimpleLMS\\Analytics_Retention', function ($c) {
            return new \SimpleLMS\Analytics_Retention($c->has(Logger::class) ? $c->get(Logger::class) : null);
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
        /** @var Managers\HookManager $hookManager */
        $hookManager = $this->container->get(Managers\HookManager::class);

        /** @var Managers\AssetManager $assetManager */
        $assetManager = $this->container->get(Managers\AssetManager::class);

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
            'includes/custom-post-types.php',
            'includes/custom-meta-boxes.php',
            'includes/admin-customizations.php',
            'includes/ajax-handlers.php',
            'includes/class-rest-api-refactored.php',
            'includes/class-progress-tracker.php',
            'includes/class-shortcodes.php',
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
                    $c->has(Logger::class) ? $c->get(Logger::class) : null,
                    $c->has(Security_Service::class) ? $c->get(Security_Service::class) : null
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
     * Register Bricks integration
     * 
     * Called only when Bricks is loaded and active
     *
     * @return void
     */
    public function registerBricksIntegration(): void
    {
        $bricks = SIMPLE_LMS_PLUGIN_DIR . 'includes/bricks/class-bricks-integration.php';
        
        if (file_exists($bricks)) {
            require_once $bricks;
            if (class_exists('SimpleLMS\Bricks\Bricks_Integration')) {
                \SimpleLMS\Bricks\Bricks_Integration::init();
            }
        }
    }

    /**
     * Initialize plugin functionality (with DI)
     *
     * @return void
     */
    public function init(): void
    {
        \do_action('simple_lms_before_init');

        /** @var Managers\CPTManager $cptManager */
        $cptManager = $this->container->get(Managers\CPTManager::class);
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
            /** @var Rest_API $restApi */
            $restApi = $this->container->get('SimpleLMS\\Rest_API');
            /** @var Managers\HookManager $hookManager */
            $hookManager = $this->container->get(Managers\HookManager::class);
            $hookManager->addAction('rest_api_init', [$restApi, 'registerEndpoints']);
        }

        if ($this->container->has('SimpleLMS\\Progress_Tracker')) {
            $this->container->get('SimpleLMS\\Progress_Tracker')->register();
        }

        if ($this->container->has('SimpleLMS\\Access_Control')) {
            $this->container->get('SimpleLMS\\Access_Control')->register();
        }

        if ($this->container->has('SimpleLMS\\LmsShortcodes')) {
            $this->container->get('SimpleLMS\\LmsShortcodes')->register();
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
            Cache_Handler::init();
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
     * @return ServiceContainer
     */
    public function getContainer(): ServiceContainer
    {
        return $this->container;
    }

    /**
     * Plugin activation hook
     *
     * @return void
     */
    public static function activate(): void
    {
        $container = ServiceContainer::getInstance();

        // Register and flush CPT rewrites
        if ($container->has(Managers\CPTManager::class)) {
            /** @var Managers\CPTManager $cptManager */
            $cptManager = $container->get(Managers\CPTManager::class);
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
            Analytics_Retention::deactivate_cleanup_cron();
        }

        \flush_rewrite_rules();
        \do_action('simple_lms_deactivated');
    }
}

/**
 * Initialize and boot the plugin
 *
 * @return Plugin
 */
function simpleLmsInit(): Plugin
{
    $container = ServiceContainer::getInstance();

    // Register Plugin instance in container
    $container->singleton(Plugin::class, function ($c) {
        return new Plugin($c);
    });

    /** @var Plugin $plugin */
    $plugin = $container->get(Plugin::class);
    $plugin->boot();

    return $plugin;
}

// Boot plugin
\add_action('plugins_loaded', __NAMESPACE__ . '\\simpleLmsInit', 5);

// Register activation/deactivation hooks
\register_activation_hook(__FILE__, [Plugin::class, 'activate']);
\register_deactivation_hook(__FILE__, [Plugin::class, 'deactivate']);
