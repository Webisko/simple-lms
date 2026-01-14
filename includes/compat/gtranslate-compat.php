<?php
/**
 * Compatibility layer for GTranslate
 * - Google Translate-based automatic translation
 * - Widget/JavaScript based translation (no separate posts)
 * - Limited integration needed as GTranslate translates entire page dynamically
 * 
 * @package SimpleLMS
 * @since 1.3.4
 */

namespace SimpleLMS\Compat;

if (!defined('ABSPATH')) {
    exit;
}

class GTranslate_Compat {
    /**
     * Initialize GTranslate compatibility
     */
    public static function init(): void {
        // GTranslate translates entire page via JavaScript/widget
        // No specific post type registration needed
        
        // Ensure AJAX requests respect language parameter
        add_action('wp_ajax_nopriv_simple_lms_complete_lesson', [__CLASS__, 'set_language_from_request'], 1);
        add_action('wp_ajax_simple_lms_complete_lesson', [__CLASS__, 'set_language_from_request'], 1);
        
        // Add language attribute to HTML for better translation
        add_filter('language_attributes', [__CLASS__, 'add_language_attributes']);
    }

    /**
     * Set language from GTranslate request parameter
     * GTranslate passes language via URL parameter
     */
    public static function set_language_from_request(): void {
        // GTranslate typically uses 'lang' or 'gt_lang' parameter
        if (isset($_GET['lang'])) {
            $lang = sanitize_text_field($_GET['lang']);
            // Store for reference if needed
            if (!defined('GTRANSLATE_CURRENT_LANG')) {
                define('GTRANSLATE_CURRENT_LANG', $lang);
            }
        }
    }

    /**
     * Add language attributes to HTML tag for better translation detection
     *
     * @param string $output Existing language attributes
     * @return string Modified attributes
     */
    public static function add_language_attributes(string $output): string {
        // Ensure lang attribute is present for GTranslate to detect
        if (self::is_active() && strpos($output, 'lang=') === false) {
            $locale = \get_locale();
            $lang = substr($locale, 0, 2);
            $output .= ' lang="' . esc_attr($lang) . '"';
        }
        
        return $output;
    }

    /**
     * Get translated post ID
     * Note: GTranslate doesn't create separate posts - translates dynamically
     *
     * @param int    $post_id Original post ID
     * @param string $type    Post type
     * @return int Same post ID (GTranslate uses dynamic translation)
     */
    public static function get_translated_id(int $post_id, string $type = 'post'): int {
        // GTranslate translates entire page via JavaScript
        // Post IDs remain the same across all languages
        return $post_id;
    }

    /**
     * Check if GTranslate is active
     *
     * @return bool
     */
    public static function is_active(): bool {
        // Check for GTranslate plugin
        if (function_exists('gtranslate_init') || class_exists('GTranslate')) {
            return true;
        }
        
        // Check for GTranslate script in footer
        global $wp_scripts;
        if ($wp_scripts) {
            foreach ($wp_scripts->registered as $handle => $script) {
                if (strpos($script->src, 'gtranslate') !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get current language from GTranslate
     *
     * @return string Language code (e.g., 'en', 'de', 'pl')
     */
    public static function get_current_language(): string {
        // Check URL parameter
        if (isset($_GET['lang'])) {
            return sanitize_text_field($_GET['lang']);
        }
        
        // Check cookie (GTranslate stores language preference in cookie)
        if (isset($_COOKIE['googtrans'])) {
            $cookie = sanitize_text_field($_COOKIE['googtrans']);
            // Cookie format: /en/de (from english to german)
            $parts = explode('/', $cookie);
            if (isset($parts[2])) {
                return $parts[2];
            }
        }
        
        // Fallback to WordPress locale
        $locale = \get_locale();
        return substr($locale, 0, 2);
    }

    /**
     * Get original (source) language
     *
     * @return string Language code
     */
    public static function get_original_language(): string {
        // GTranslate source language is typically the site's default language
        $locale = \get_locale();
        return substr($locale, 0, 2);
    }

    /**
     * Check if currently viewing translated version
     *
     * @return bool
     */
    public static function is_translated(): bool {
        $current = self::get_current_language();
        $original = self::get_original_language();
        
        return $current !== $original;
    }

    /**
     * Get all available languages from GTranslate configuration
     *
     * @return array Array of language codes
     */
    public static function get_available_languages(): array {
        // Try to get from GTranslate options
        $options = \get_option('GTranslate');
        
        if (is_array($options) && isset($options['incl_langs'])) {
            $langs = explode(',', $options['incl_langs']);
            return array_filter(array_map('trim', $langs));
        }
        
        // GTranslate free version supports 100+ languages by default
        // Return common ones if configuration not found
        return ['en', 'de', 'pl', 'es', 'fr', 'it', 'pt', 'ru', 'ja', 'zh-CN'];
    }

    /**
     * Add notranslate class to elements that shouldn't be translated
     * Use this for technical elements, IDs, or code snippets
     *
     * @param string $content Content to mark as non-translatable
     * @return string Content wrapped with notranslate class
     */
    public static function mark_no_translate(string $content): string {
        return '<span class="notranslate">' . $content . '</span>';
    }

    /**
     * Get translation URL for specific language
     *
     * @param string $url Original URL
     * @param string $target_lang Target language code
     * @return string Modified URL with language parameter
     */
    public static function get_translation_url(string $url, string $target_lang): string {
        return add_query_arg('lang', $target_lang, $url);
    }
}

// Auto-init when file is loaded
if (function_exists('gtranslate_init') || class_exists('GTranslate')) {
    GTranslate_Compat::init();
}
