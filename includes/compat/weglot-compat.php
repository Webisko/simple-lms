<?php
/**
 * Compatibility layer for Weglot
 * - Registers custom post types as translatable
 * - Configures REST API endpoints for translation
 * - Provides ID mapping helpers for current language
 * 
 * @package SimpleLMS
 * @since 1.3.4
 */

namespace SimpleLMS\Compat;

if (!defined('ABSPATH')) {
    exit;
}

class Weglot_Compat {
    /**
     * Initialize Weglot compatibility
     */
    public static function init(): void {
        // Register CPTs as translatable
        add_filter('weglot_get_post_types', [__CLASS__, 'register_post_types']);
        
        // Add custom meta fields to translation
        add_filter('weglot_get_custom_fields', [__CLASS__, 'register_custom_fields']);
        
        // Exclude certain fields from translation
        add_filter('weglot_exclude_custom_fields', [__CLASS__, 'exclude_custom_fields']);
        
        // Add REST API endpoints
        add_filter('weglot_rest_routes', [__CLASS__, 'register_rest_routes']);
    }

    /**
     * Register Simple LMS custom post types with Weglot
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
     * Register custom fields that should be translated by Weglot
     * These are meta fields that contain user-facing text
     *
     * @param array $custom_fields Existing custom fields
     * @return array Modified custom fields array
     */
    public static function register_custom_fields(array $custom_fields): array {
        // Add lesson video URL (if it's a text description or title)
        // Note: URLs typically shouldn't be translated, but titles should
        
        // Most of our custom fields are technical (IDs, URLs) and shouldn't be translated
        // Weglot will automatically handle post title, content, and excerpt
        
        return $custom_fields;
    }

    /**
     * Exclude technical fields from translation
     * These are IDs, settings, and other non-translatable data
     *
     * @param array $excluded_fields Existing excluded fields
     * @return array Modified excluded fields array
     */
    public static function exclude_custom_fields(array $excluded_fields): array {
        $excluded_fields[] = 'parent_course';
        $excluded_fields[] = 'parent_module';
        $excluded_fields[] = 'lesson_video_type';
        $excluded_fields[] = 'lesson_video_url';
        $excluded_fields[] = 'lesson_video_file_id';
        $excluded_fields[] = 'lesson_duration';
        $excluded_fields[] = 'lesson_attachments';
        $excluded_fields[] = '_access_duration_value';
        $excluded_fields[] = '_access_duration_unit';
        $excluded_fields[] = '_selected_product_id';
        $excluded_fields[] = '_module_unlock_delay_value';
        $excluded_fields[] = '_module_unlock_delay_unit';
        $excluded_fields[] = 'simple_lms_completed_lessons';
        $excluded_fields[] = 'simple_lms_course_access_*';
        
        return $excluded_fields;
    }

    /**
     * Register REST API routes for Weglot translation
     *
     * @param array $routes Existing REST routes
     * @return array Modified routes array
     */
    public static function register_rest_routes(array $routes): array {
        // Add Simple LMS REST API endpoints if they should be translated
        $routes[] = '/simple-lms/v1/courses';
        $routes[] = '/simple-lms/v1/modules';
        $routes[] = '/simple-lms/v1/lessons';
        
        return $routes;
    }

    /**
     * Get translated post ID for current language
     * Note: Weglot doesn't create separate posts - it translates URLs and content dynamically
     *
     * @param int    $post_id Original post ID
     * @param string $type    Post type
     * @return int Original post ID (Weglot uses same IDs across languages)
     */
    public static function get_translated_id(int $post_id, string $type = 'post'): int {
        // Weglot doesn't create duplicate posts like WPML
        // It translates content on-the-fly via JavaScript and server-side
        // So the post ID remains the same across all languages
        
        return $post_id;
    }

    /**
     * Get translated URL for a post in specific language
     *
     * @param int    $post_id Post ID
     * @param string $language_code Target language code (e.g., 'de', 'en')
     * @return string Translated URL
     */
    public static function get_translated_url(int $post_id, string $language_code = ''): string {
        if (!self::is_active()) {
            return \get_permalink($post_id);
        }

        $url = \get_permalink($post_id);
        
        if (empty($language_code)) {
            $language_code = self::get_current_language();
        }

        // Weglot adds language code to URL (e.g., /de/course/my-course/)
        if (function_exists('weglot_get_translated_url')) {
            return \weglot_get_translated_url($url, $language_code);
        }
        
        return $url;
    }

    /**
     * Check if Weglot is active
     *
     * @return bool
     */
    public static function is_active(): bool {
        return function_exists('weglot_init') || class_exists('Weglot\\Client\\Client');
    }

    /**
     * Get current language code
     *
     * @return string Language code (e.g., 'en', 'de', 'pl')
     */
    public static function get_current_language(): string {
        if (function_exists('weglot_get_current_language')) {
            return \weglot_get_current_language();
        }
        
        if (self::is_active() && function_exists('weglot_get_options')) {
            $options = \weglot_get_options();
            if (isset($options['current_language'])) {
                return $options['current_language'];
            }
        }
        
        // Fallback to original language
        return self::get_original_language();
    }

    /**
     * Get original (default) language code
     *
     * @return string Language code
     */
    public static function get_original_language(): string {
        if (function_exists('weglot_get_original_language')) {
            return \weglot_get_original_language();
        }
        
        if (self::is_active() && function_exists('weglot_get_options')) {
            $options = \weglot_get_options();
            if (isset($options['original_language'])) {
                return $options['original_language'];
            }
        }
        
        return 'en';
    }

    /**
     * Get all active destination languages
     *
     * @return array Array of language codes
     */
    public static function get_destination_languages(): array {
        if (!self::is_active()) {
            return [];
        }

        if (function_exists('weglot_get_destination_languages')) {
            return \weglot_get_destination_languages();
        }

        if (function_exists('weglot_get_options')) {
            $options = \weglot_get_options();
            if (isset($options['destination_languages'])) {
                return $options['destination_languages'];
            }
        }
        
        return [];
    }

    /**
     * Get all languages (original + destinations)
     *
     * @return array Array of language codes
     */
    public static function get_all_languages(): array {
        $languages = [self::get_original_language()];
        $destinations = self::get_destination_languages();
        
        return array_merge($languages, $destinations);
    }
}

// Auto-init when file is loaded
if (function_exists('weglot_init') || class_exists('Weglot\\Client\\Client')) {
    Weglot_Compat::init();
}
