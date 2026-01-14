<?php
/**
 * Compatibility layer for MultilingualPress
 * - Supports WordPress Multisite-based translation
 * - Each language is a separate site in the network
 * - Provides content relationship mapping between sites
 * 
 * @package SimpleLMS
 * @since 1.3.4
 */

namespace SimpleLMS\Compat;

if (!defined('ABSPATH')) {
    exit;
}

class MultilingualPress_Compat {
    /**
     * Initialize MultilingualPress compatibility
     */
    public static function init(): void {
        // Register CPTs as translatable
        add_filter('multilingualpress.custom_post_types', [__CLASS__, 'register_post_types']);
        
        // Copy meta fields when creating translations
        add_action('mlp_translation_created', [__CLASS__, 'copy_meta_on_translation'], 10, 3);
        
        // Ensure proper content relationships
        add_filter('multilingualpress.relationship_context', [__CLASS__, 'filter_relationship_context'], 10, 2);
    }

    /**
     * Register Simple LMS custom post types with MultilingualPress
     *
     * @param array $post_types Existing translatable post types
     * @return array Modified post types array
     */
    public static function register_post_types(array $post_types): array {
        $post_types['course'] = 'course';
        $post_types['module'] = 'module';
        $post_types['lesson'] = 'lesson';
        
        return $post_types;
    }

    /**
     * Copy meta fields when translation is created
     *
     * @param int $source_site_id Source site ID
     * @param int $target_site_id Target site ID
     * @param array $relationship Relationship data
     */
    public static function copy_meta_on_translation(int $source_site_id, int $target_site_id, array $relationship): void {
        if (empty($relationship['source_post_id']) || empty($relationship['target_post_id'])) {
            return;
        }

        $source_post_id = (int) $relationship['source_post_id'];
        $target_post_id = (int) $relationship['target_post_id'];

        // Switch to source site to get meta
        \switch_to_blog($source_site_id);
        $meta_fields = [
            'parent_course',
            'parent_module',
            'lesson_video_type',
            'lesson_video_url',
            'lesson_video_file_id',
            'lesson_duration',
            'lesson_attachments',
            '_access_duration_value',
            '_access_duration_unit',
            '_selected_product_id',
            '_module_unlock_delay_value',
            '_module_unlock_delay_unit',
        ];

        $meta_data = [];
        foreach ($meta_fields as $key) {
            $value = \get_post_meta($source_post_id, $key, true);
            if ($value !== '') {
                $meta_data[$key] = $value;
            }
        }
        \restore_current_blog();

        // Switch to target site to set meta
        if (!empty($meta_data)) {
            \switch_to_blog($target_site_id);
            foreach ($meta_data as $key => $value) {
                \update_post_meta($target_post_id, $key, $value);
            }
            \restore_current_blog();
        }
    }

    /**
     * Filter relationship context to ensure proper content connections
     *
     * @param array $context Current context
     * @param int $site_id Site ID
     * @return array Modified context
     */
    public static function filter_relationship_context(array $context, int $site_id): array {
        // Ensure Simple LMS CPTs are included in relationship context
        if (isset($context['post_type']) && in_array($context['post_type'], ['course', 'module', 'lesson'])) {
            $context['is_simple_lms'] = true;
        }
        
        return $context;
    }

    /**
     * Get translated post ID for target site/language
     *
     * @param int $post_id Post ID in current site
     * @param int $target_site_id Target site ID
     * @return int Translated post ID or 0 if not found
     */
    public static function get_translated_id(int $post_id, int $target_site_id = 0): int {
        if (!self::is_active()) {
            return $post_id;
        }

        // If no target site specified, use current site
        if (!$target_site_id) {
            $target_site_id = \get_current_blog_id();
        }

        // MultilingualPress 3.x API
        if (function_exists('mlp_get_linked_elements')) {
            $translations = \mlp_get_linked_elements($post_id);
            if (isset($translations[$target_site_id])) {
                return (int) $translations[$target_site_id];
            }
        }

        return 0;
    }

    /**
     * Get all translations of a post across all sites
     *
     * @param int $post_id Post ID
     * @return array Array of [site_id => post_id]
     */
    public static function get_all_translations(int $post_id): array {
        if (!self::is_active() || !function_exists('mlp_get_linked_elements')) {
            return [];
        }

        return \mlp_get_linked_elements($post_id) ?: [];
    }

    /**
     * Check if MultilingualPress is active
     *
     * @return bool
     */
    public static function is_active(): bool {
        // Check if multisite and MultilingualPress is active
        if (!\is_multisite()) {
            return false;
        }

        return function_exists('mlp_get_linked_elements') || class_exists('Inpsyde\\MultilingualPress\\Framework\\Plugin');
    }

    /**
     * Get current site language
     *
     * @return string Language code (e.g., 'en_US', 'de_DE')
     */
    public static function get_current_language(): string {
        if (!self::is_active()) {
            return \get_locale();
        }

        $site_id = \get_current_blog_id();
        
        // MultilingualPress 3.x
        if (function_exists('mlp_get_language')) {
            $language = \mlp_get_language($site_id);
            if ($language) {
                return $language;
            }
        }

        return \get_locale();
    }

    /**
     * Get all sites with their languages
     *
     * @return array Array of [site_id => language_code]
     */
    public static function get_all_sites(): array {
        if (!self::is_active()) {
            return [];
        }

        $sites = [];
        $network_sites = \get_sites(['number' => 100]);

        foreach ($network_sites as $site) {
            \switch_to_blog($site->blog_id);
            $sites[$site->blog_id] = \get_locale();
            \restore_current_blog();
        }

        return $sites;
    }

    /**
     * Get translation URL for specific site
     *
     * @param int $post_id Post ID
     * @param int $target_site_id Target site ID
     * @return string URL or empty string if not found
     */
    public static function get_translation_url(int $post_id, int $target_site_id): string {
        $translated_id = self::get_translated_id($post_id, $target_site_id);
        
        if (!$translated_id) {
            return '';
        }

        \switch_to_blog($target_site_id);
        $url = \get_permalink($translated_id);
        \restore_current_blog();

        return $url ?: '';
    }
}

// Auto-init when file is loaded (only on multisite with MLP)
if (is_multisite() && (function_exists('mlp_get_linked_elements') || class_exists('Inpsyde\\MultilingualPress\\Framework\\Plugin'))) {
    MultilingualPress_Compat::init();
}
