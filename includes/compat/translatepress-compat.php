<?php
/**
 * Compatibility layer for TranslatePress
 * - Registers custom post types as translatable
 * - Provides ID mapping helpers for current language
 * 
 * @package SimpleLMS
 * @since 1.3.4
 */

namespace SimpleLMS\Compat;

if (!defined('ABSPATH')) {
    exit;
}

class TranslatePress_Compat {
    /**
     * Initialize TranslatePress compatibility
     */
    public static function init(): void {
        // Register CPTs as translatable
        add_filter('trp_register_custom_post_types', [__CLASS__, 'register_post_types']);
        
        // Register custom fields that should be translatable
        add_filter('trp_translatable_strings', [__CLASS__, 'register_translatable_fields'], 10, 2);
    }

    /**
     * Register Simple LMS custom post types with TranslatePress
     *
     * @param array $post_types Existing translatable post types
     * @return array Modified post types array
     */
    public static function register_post_types(array $post_types): array {
        $post_types[] = 'course';
        $post_types[] = 'module';
        $post_types[] = 'lesson';
        
        return $post_types;
    }

    /**
     * Register custom fields that should be translatable
     * Note: TranslatePress by default translates post title/content/excerpt
     * This adds meta fields that should also be translated
     *
     * @param array  $strings Translatable strings
     * @param string $language Target language
     * @return array Modified strings array
     */
    public static function register_translatable_fields(array $strings, string $language): array {
        // TranslatePress handles post content automatically
        // Custom meta fields would need manual registration if needed
        // For now, we rely on TranslatePress's automatic detection
        
        return $strings;
    }

    /**
     * Get translated post ID for current language
     * Compatible with TranslatePress's translation system
     *
     * @param int    $post_id Original post ID
     * @param string $type    Post type
     * @return int Translated post ID or original if not found
     */
    public static function get_translated_id(int $post_id, string $type = 'post'): int {
        if (!$post_id) {
            return $post_id;
        }

        // TranslatePress uses a different approach - it doesn't create separate posts
        // Instead, it translates content in-place using gettext
        // So we return the original ID as TranslatePress handles translation internally
        
        // If you need to get the URL in a specific language:
        if (function_exists('trp_get_url_for_language')) {
            $current_lang = \get_locale();
            // TranslatePress works with URLs, not post IDs
            // The post ID remains the same across languages
        }
        
        return $post_id;
    }

    /**
     * Check if TranslatePress is active
     *
     * @return bool
     */
    public static function is_active(): bool {
        return class_exists('TRP_Translate_Press');
    }

    /**
     * Get current language code
     *
     * @return string Language code (e.g., 'en_US', 'de_DE')
     */
    public static function get_current_language(): string {
        if (function_exists('trp_get_current_language')) {
            return \trp_get_current_language();
        }
        
        return \get_locale();
    }

    /**
     * Get all active languages
     *
     * @return array Array of language codes
     */
    public static function get_active_languages(): array {
        if (!self::is_active()) {
            return [];
        }

        $trp = \TRP_Translate_Press::get_trp_instance();
        if (!$trp) {
            return [];
        }

        $settings = $trp->get_component('settings');
        if (!$settings) {
            return [];
        }

        $trp_settings = $settings->get_settings();
        
        return isset($trp_settings['translation-languages']) ? $trp_settings['translation-languages'] : [];
    }
}

// Auto-init when file is loaded
if (class_exists('TRP_Translate_Press')) {
    TranslatePress_Compat::init();
}
