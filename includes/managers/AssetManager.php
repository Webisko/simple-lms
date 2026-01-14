<?php
/**
 * Asset Manager - Manages scripts and styles enqueuing
 *
 * @package SimpleLMS
 * @since 1.4.0
 */

declare(strict_types=1);

namespace SimpleLMS\Managers;
use SimpleLMS\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Asset Manager Class
 *
 * Centralized management of scripts and styles registration and enqueuing.
 */
class AssetManager
{
    /**
     * Plugin directory path
     *
     * @var string
     */
    private string $pluginDir;

    /**
     * Plugin URL
     *
     * @var string
     */
    private string $pluginUrl;

    /**
     * Plugin version (for cache busting)
     *
     * @var string
     */
    private string $version;

    /**
     * Logger instance
     *
     * @var Logger|null
     */
    private ?Logger $logger = null;

    /**
     * Registered scripts
     *
     * @var array<string, array>
     */
    private array $scripts = [];

    /**
     * Registered styles
     *
     * @var array<string, array>
     */
    private array $styles = [];

    /**
     * Constructor
     *
     * @param string $pluginDir Plugin directory path
     * @param string $pluginUrl Plugin URL
     * @param string $version   Plugin version
     */
    public function __construct(string $pluginDir, string $pluginUrl, string $version, ?Logger $logger = null)
    {
        $this->pluginDir = $pluginDir;
        $this->pluginUrl = $pluginUrl;
        $this->version = $version;
        $this->logger = $logger;
    }

    /**
     * Register a script
     *
     * @param string $handle    Script handle
     * @param string $src       Script source (relative to plugin directory)
     * @param array  $deps      Dependencies (default: [])
     * @param bool   $inFooter  Whether to enqueue in footer (default: true)
     * @return self For method chaining
     */
    public function registerScript(
        string $handle,
        string $src,
        array $deps = [],
        bool $inFooter = true
    ): self {
        $this->scripts[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'in_footer' => $inFooter,
        ];

        $fullSrc = $this->pluginUrl . $src;
        try {
            \wp_register_script($handle, $fullSrc, $deps, $this->version, $inFooter);
            if ($this->logger) {
                $this->logger->debug('Registered script {handle} -> {src}', ['handle' => $handle, 'src' => $fullSrc]);
            }
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('Failed to register script {handle}: {error}', ['handle' => $handle, 'error' => $e]);
            }
        }

        return $this;
    }

    /**
     * Register a style
     *
     * @param string $handle Script handle
     * @param string $src    Style source (relative to plugin directory)
     * @param array  $deps   Dependencies (default: [])
     * @param string $media  Media type (default: 'all')
     * @return self For method chaining
     */
    public function registerStyle(
        string $handle,
        string $src,
        array $deps = [],
        string $media = 'all'
    ): self {
        $this->styles[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'media' => $media,
        ];

        $fullSrc = $this->pluginUrl . $src;
        try {
            \wp_register_style($handle, $fullSrc, $deps, $this->version, $media);
            if ($this->logger) {
                $this->logger->debug('Registered style {handle} -> {src}', ['handle' => $handle, 'src' => $fullSrc]);
            }
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('Failed to register style {handle}: {error}', ['handle' => $handle, 'error' => $e]);
            }
        }

        return $this;
    }

    /**
     * Enqueue a registered script
     *
     * @param string $handle Script handle
     * @return self For method chaining
     */
    public function enqueueScript(string $handle): self
    {
        try {
            \wp_enqueue_script($handle);
            if ($this->logger) {
                $this->logger->debug('Enqueued script {handle}', ['handle' => $handle]);
            }
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('Failed to enqueue script {handle}: {error}', ['handle' => $handle, 'error' => $e]);
            }
        }

        return $this;
    }

    /**
     * Enqueue a registered style
     *
     * @param string $handle Style handle
     * @return self For method chaining
     */
    public function enqueueStyle(string $handle): self
    {
        try {
            \wp_enqueue_style($handle);
            if ($this->logger) {
                $this->logger->debug('Enqueued style {handle}', ['handle' => $handle]);
            }
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('Failed to enqueue style {handle}: {error}', ['handle' => $handle, 'error' => $e]);
            }
        }

        return $this;
    }

    /**
     * Localize a script with data
     *
     * @param string $handle      Script handle
     * @param string $objectName  JavaScript object name
     * @param array  $data        Data to localize
     * @return self For method chaining
     */
    public function localizeScript(string $handle, string $objectName, array $data): self
    {
        try {
            \wp_localize_script($handle, $objectName, $data);
            if ($this->logger) {
                $this->logger->debug('Localized script {handle} with object {object}', ['handle' => $handle, 'object' => $objectName]);
            }
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('Failed to localize script {handle}: {error}', ['handle' => $handle, 'error' => $e]);
            }
        }

        return $this;
    }

    /**
     * Add inline script
     *
     * @param string $handle   Script handle
     * @param string $data     JavaScript code
     * @param string $position Position ('before' or 'after', default: 'after')
     * @return self For method chaining
     */
    public function addInlineScript(string $handle, string $data, string $position = 'after'): self
    {
        try {
            \wp_add_inline_script($handle, $data, $position);
            if ($this->logger) {
                $this->logger->debug('Added inline script to {handle}', ['handle' => $handle]);
            }
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('Failed to add inline script {handle}: {error}', ['handle' => $handle, 'error' => $e]);
            }
        }

        return $this;
    }

    /**
     * Add inline style
     *
     * @param string $handle Style handle
     * @param string $data   CSS code
     * @return self For method chaining
     */
    public function addInlineStyle(string $handle, string $data): self
    {
        try {
            \wp_add_inline_style($handle, $data);
            if ($this->logger) {
                $this->logger->debug('Added inline style to {handle}', ['handle' => $handle]);
            }
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('Failed to add inline style {handle}: {error}', ['handle' => $handle, 'error' => $e]);
            }
        }

        return $this;
    }

    /**
     * Register and enqueue frontend scripts
     *
     * @return void
     */
    public function enqueueFrontendAssets(): void
    {
        // Main frontend CSS (built with Vite)
        $this->registerStyle(
            'simple-lms-frontend',
            'assets/dist/css/frontend-style.css'
        )->enqueueStyle('simple-lms-frontend');

        // Main frontend JS (built with Vite)
        $this->registerScript(
            'simple-lms-frontend',
            'assets/dist/js/frontend.js',
            ['jquery']
        )->enqueueScript('simple-lms-frontend');

        // Localize script with data
        $this->localizeScript('simple-lms-frontend', 'simpleLMS', [
            'ajaxUrl' => \admin_url('admin-ajax.php'),
            'nonce' => \wp_create_nonce('simple_lms_nonce'),
            'userId' => \get_current_user_id(),
            'isUserLoggedIn' => \is_user_logged_in(),
        ]);

        // Lesson-specific assets (only on lesson pages)
        if (\is_singular('lesson')) {
            $this->registerScript(
                'simple-lms-lesson',
                'assets/dist/js/lesson.js',
                ['simple-lms-frontend']
            )->enqueueScript('simple-lms-lesson');

            $lessonId = \get_queried_object_id();
            $this->localizeScript('simple-lms-lesson', 'simpleLMSLesson', [
                'lessonId' => $lessonId,
                'moduleId' => (int) \get_post_meta($lessonId, 'parent_module', true),
            ]);
        }
    }

    /**
     * Register and enqueue admin scripts
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueueAdminAssets(string $hook): void
    {
        // Admin CSS - load from src since Vite embeds it in JS
        $this->registerStyle(
            'simple-lms-admin',
            'assets/src/css/admin.css'
        )->enqueueStyle('simple-lms-admin');

        // Admin JS
        $this->registerScript(
            'simple-lms-admin',
            'assets/dist/js/admin.js',
            ['jquery', 'jquery-ui-sortable']
        )->enqueueScript('simple-lms-admin');

        // Legacy-compatible localization keys expected by admin.js
        $localization = [
            'ajaxurl' => \admin_url('admin-ajax.php'),
            'nonce' => \wp_create_nonce('simple-lms-nonce_ajax'),
            'editModuleUrl' => \admin_url('post.php?post=MODULE_ID&action=edit'),
            'editLessonUrl' => \admin_url('post.php?post=LESSON_ID&action=edit'),
            'i18n' => [
                'error_generic' => __('Wystąpił błąd. Spróbuj ponownie.', 'simple-lms'),
                'confirm_delete_module' => __('Czy na pewno chcesz usunąć moduł?', 'simple-lms'),
                'confirm_delete_lesson' => __('Czy na pewno chcesz usunąć lekcję?', 'simple-lms'),
                'enter_module_title' => __('Wpisz tytuł modułu.', 'simple-lms'),
                'enter_lesson_title' => __('Wpisz tytuł lekcji.', 'simple-lms'),
                'add_lesson' => __('Dodaj lekcję', 'simple-lms'),
                'lesson_title' => __('Tytuł lekcji', 'simple-lms'),
                'duplicate_module' => __('Duplikuj', 'simple-lms'),
                'delete_module' => __('Usuń', 'simple-lms'),
            ],
        ];
        $this->localizeScript('simple-lms-admin', 'simpleLMS', $localization);
        // Also localize under previous object name for broader compatibility
        $this->localizeScript('simple-lms-admin', 'simpleLMSAdmin', $localization);

        // Post edit screen specific assets
        if (in_array($hook, ['post.php', 'post-new.php'], true)) {
            $screen = \get_current_screen();

            if ($screen && in_array($screen->post_type, ['course', 'module', 'lesson'], true)) {
                $this->registerScript(
                    'simple-lms-meta-boxes',
                    'assets/dist/js/meta-boxes.js',
                    ['simple-lms-admin']
                )->enqueueScript('simple-lms-meta-boxes');
            }
        }

        // Settings page specific assets
        if (strpos($hook, 'simple-lms-settings') !== false) {
            $this->registerScript(
                'simple-lms-settings',
                'assets/dist/js/settings.js',
                ['simple-lms-admin']
            )->enqueueScript('simple-lms-settings');
        }
    }

    /**
     * Get all registered scripts
     *
     * @return array<string, array>
     */
    public function getScripts(): array
    {
        return $this->scripts;
    }

    /**
     * Get all registered styles
     *
     * @return array<string, array>
     */
    public function getStyles(): array
    {
        return $this->styles;
    }

    /**
     * Check if a script is registered
     *
     * @param string $handle Script handle
     * @return bool
     */
    public function hasScript(string $handle): bool
    {
        return isset($this->scripts[$handle]);
    }

    /**
     * Check if a style is registered
     *
     * @param string $handle Style handle
     * @return bool
     */
    public function hasStyle(string $handle): bool
    {
        return isset($this->styles[$handle]);
    }
}
