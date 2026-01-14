<?php
/**
 * Compatibility layer for qTranslate-X and qTranslate-XT
 * - Registers custom post types as translatable
 * - Provides ID mapping helpers (qTranslate uses single post with multilingual content)
 * 
 * Note: qTranslate-X is no longer actively maintained. qTranslate-XT is the community fork.
 * 
 * @package SimpleLMS
 * @since 1.3.4
 */

namespace SimpleLMS\Compat;

if (!defined('ABSPATH')) {
    exit;
}

class qTranslate_Compat {
    /**
     * Initialize qTranslate compatibility
     */
    public static function init(): void {
        // Register CPTs as translatable
        add_filter('qtranslate_custom_post_types', [__CLASS__, 'register_post_types']);
        
        // qTranslate-XT uses different filter name
        add_filter('qtranslate_xt_custom_post_types', [__CLASS__, 'register_post_types']);
        
        // Register custom fields that should be translatable
        add_filter('qtranslate_meta_fields', [__CLASS__, 'register_meta_fields']);
    }

    /**
     * Register Simple LMS custom post types with qTranslate
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
     * Register meta fields that should be translatable
     * qTranslate stores translations in the same post using language tags like [:en]text[:de]text
     *
     * @param array $meta_fields Existing translatable meta fields
     * @return array Modified meta fields array
     */
    public static function register_meta_fields(array $meta_fields): array {
        // Most of our meta fields are technical (IDs, URLs) and shouldn't be translated
        // qTranslate handles post title and content automatically
        
        return $meta_fields;
    }

    /**
     * Get translated post ID for current language
     * Note: qTranslate doesn't create separate posts - all translations are in one post
     *
     * @param int    $post_id Original post ID
     * @param string $type    Post type
     * @return int Same post ID (qTranslate uses single post approach)
     */
    public static function get_translated_id(int $post_id, string $type = 'post'): int {
        // qTranslate stores all translations in one post using language tags
        // The post ID remains the same across all languages
        // Content is filtered based on current language at display time
        
        return $post_id;
    }

    /**
     * Check if qTranslate-X or qTranslate-XT is active
     *
     * @return bool
     */
    public static function is_active(): bool {
        // qTranslate-X
        if (function_exists('qtranxf_getLanguage') || defined('QTX_VERSION')) {
            return true;
        }
        
        // qTranslate-XT (community fork)
        if (function_exists('qtrans_getLanguage') || class_exists('QTX_Translator')) {
            return true;
        }
        
        return false;
    }

    /**
     * Get current language code
     *
     * @return string Language code (e.g., 'en', 'de', 'pl')
     */
    public static function get_current_language(): string {
        // qTranslate-X
        if (function_exists('qtranxf_getLanguage')) {
            return \qtranxf_getLanguage();
        }
        
        // qTranslate-XT
        if (function_exists('qtrans_getLanguage')) {
            return \qtrans_getLanguage();
        }
        
        // Fallback
        $locale = \get_locale();
        return substr($locale, 0, 2); // Convert en_US to en
    }

    /**
     * Get all enabled languages
     *
     * @return array Array of language codes
     */
    public static function get_enabled_languages(): array {
        global $q_config;
        
        if (isset($q_config['enabled_languages']) && is_array($q_config['enabled_languages'])) {
            return $q_config['enabled_languages'];
        }
        
        return [];
    }

    /**
     * Get default language
     *
     * @return string Language code
     */
    public static function get_default_language(): string {
        global $q_config;
        
        if (isset($q_config['default_language'])) {
            return $q_config['default_language'];
        }
        
        return 'en';
    }

    /**
     * Translate text with language tags
     * Extracts text for current language from qTranslate format: [:en]English[:de]Deutsch
     *
     * @param string $text Text with language tags
     * @return string Translated text for current language
     */
    public static function translate_text(string $text): string {
        if (function_exists('qtranxf_use')) {
            return \qtranxf_use(self::get_current_language(), $text);
        }
        
        if (function_exists('qtrans_use')) {
            return \qtrans_use(self::get_current_language(), $text);
        }
        
        return $text;
    }
}

// Auto-init when file is loaded
if (function_exists('qtranxf_getLanguage') || function_exists('qtrans_getLanguage') || defined('QTX_VERSION')) {
    qTranslate_Compat::init();
}
