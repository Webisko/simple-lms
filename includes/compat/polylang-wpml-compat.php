<?php
/**
 * Compatibility layer for Polylang and WPML.
 * - Declares custom post types as translatable
 * - Provides ID mapping helpers for current language
 */

namespace SimpleLMS\Compat;

if (!defined('ABSPATH')) {
    exit;
}

class Multilingual_Compat {
    public static function init(): void {
        // Polylang: mark CPTs as translatable in settings and runtime
        add_filter('pll_get_post_types', [__CLASS__, 'pll_get_post_types'], 10, 2);
        add_filter('pll_get_taxonomies', [__CLASS__, 'pll_get_taxonomies'], 10, 2);
    }

    /**
     * Ensure our CPTs are translatable in Polylang
     *
     * @param array $post_types
     * @param bool  $is_settings
     * @return array
     */
    public static function pll_get_post_types(array $post_types, bool $is_settings): array {
        foreach (['course', 'module', 'lesson'] as $cpt) {
            $post_types[$cpt] = true;
        }
        return $post_types;
    }

    /**
     * Ensure custom taxonomies (if any) are translatable in Polylang
     *
     * @param array $taxonomies
     * @param bool  $is_settings
     * @return array
     */
    public static function pll_get_taxonomies(array $taxonomies, bool $is_settings): array {
        // Add here if plugin registers custom taxonomies in future
        return $taxonomies;
    }

    /**
     * Map a post ID to the current language using supported multilingual plugins
     *
     * @param int    $post_id
     * @param string $type     Post type
     * @return int
     */
    public static function map_post_id(int $post_id, string $type = 'post'): int {
        // WPML mapping (creates separate posts per language)
        if (function_exists('apply_filters')) {
            $mapped = apply_filters('wpml_object_id', $post_id, $type, true);
            if (is_int($mapped) && $mapped > 0) {
                return $mapped;
            }
        }
        
        // Polylang mapping (creates separate posts per language)
        if (function_exists('pll_get_post')) {
            $mapped = (int) \pll_get_post($post_id);
            if ($mapped > 0) {
                return $mapped;
            }
        }
        
        // MultilingualPress: separate posts across different sites in multisite
        if (is_multisite() && function_exists('mlp_get_linked_elements')) {
            // For multisite, we stay with current site's post ID
            // Use MultilingualPress_Compat::get_translated_id() for cross-site translations
            return $post_id;
        }
        
        // TranslatePress: doesn't create separate posts, translates in-place
        // Returns original ID - TranslatePress handles content translation via gettext
        if (class_exists('TRP_Translate_Press')) {
            // Post ID stays the same across languages in TranslatePress
            return $post_id;
        }
        
        // Weglot: doesn't create separate posts, translates dynamically
        // Returns original ID - Weglot handles translation via JavaScript/server-side
        if (function_exists('weglot_init') || class_exists('Weglot\\Client\\Client')) {
            // Post ID stays the same across languages in Weglot
            return $post_id;
        }
        
        // qTranslate-X/XT: single post with multilingual content using language tags
        // Returns original ID - qTranslate stores all translations in one post
        if (function_exists('qtranxf_getLanguage') || function_exists('qtrans_getLanguage')) {
            // Post ID stays the same, content filtered by language
            return $post_id;
        }
        
        // GTranslate: Google Translate widget, translates entire page dynamically
        // Returns original ID - GTranslate translates via JavaScript
        if (function_exists('gtranslate_init') || class_exists('GTranslate')) {
            // Post ID stays the same across languages in GTranslate
            return $post_id;
        }
        
        return $post_id;
    }
}

// Auto-init when file is loaded
Multilingual_Compat::init();
