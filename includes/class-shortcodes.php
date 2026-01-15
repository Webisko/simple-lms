<?php
/**
 * Shortcodes for Simple LMS
 *
 * @package SimpleLMS
 * @since 1.1.0
 */

declare(strict_types=1);

namespace SimpleLMS;

// Import WordPress functions
use function add_shortcode;
use function get_posts;
use function get_post_meta;
use function get_the_ID;
use function get_post_type;
use function get_permalink;
use function get_post;
use function get_current_user_id;
use function get_user_meta;
use function is_user_logged_in;
use function wp_get_current_user;
use function wp_create_nonce;
use function shortcode_atts;
use function esc_html;
use function esc_attr;
use function esc_url;
use function do_shortcode;
use function wp_get_attachment_url;
use function apply_filters;
use function get_the_excerpt;
use function get_the_title;
use function wc_price;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * LMS Shortcodes handler class
 */
class LmsShortcodes {
    /**
     * Backward compatibility instance
     */
    private static ?LmsShortcodes $instance = null;

    /**
     * Logger for debug/diagnostic output
     */
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }
    /**
     * Map post ID to current language (WPML/Polylang) if available
     */
    private static function mapId(int $id, string $type): int {
        if (function_exists('SimpleLMS\\Compat\\Multilingual_Compat::map_post_id')) {
            return \SimpleLMS\Compat\Multilingual_Compat::map_post_id($id, $type);
        }
        return $id;
    }

    /**
     * Initialize shortcodes
     *
     * @return void
     */
    /**
     * Legacy static init shim (kept for backward compatibility)
     */
    public static function init(): void {
        if (self::$instance instanceof self) {
            return; // Already initialized
        }
        $debug = defined('WP_DEBUG') ? (bool) \WP_DEBUG : false;
        $verboseOpt = (string) \get_option('simple_lms_verbose_logging', 'no') === 'yes';
        $debugEnabled = (bool) \apply_filters('simple_lms_debug_enabled', ($debug || $verboseOpt));
        $logger = new Logger('simple-lms', $debugEnabled);
        self::$instance = new self($logger);
        self::$instance->register();
    }

    /**
     * Preferred instance-based registration
     */
    public function register(): void
    {
        $this->logger->debug('Registering LMS shortcodes');
        self::registerShortcodes();
    }

    /**
     * Register all shortcodes
     *
     * @return void
     */
    public static function registerShortcodes(): void {
        // Core lesson shortcodes (removed lesson_video shortcode - use video_url with Elementor/Bricks widgets)
        add_shortcode('simple_lms_lesson_video_url', [__CLASS__, 'lessonVideoUrlShortcode']);
        add_shortcode('simple_lms_lesson_video_type', [__CLASS__, 'lessonVideoTypeShortcode']);
        add_shortcode('simple_lms_lesson_title', [__CLASS__, 'lessonTitleShortcode']);
        add_shortcode('simple_lms_lesson_content', [__CLASS__, 'lessonContentShortcode']);
        add_shortcode('simple_lms_lesson_excerpt', [__CLASS__, 'lessonExcerptShortcode']);
        add_shortcode('simple_lms_lesson_permalink', [__CLASS__, 'lessonPermalinkShortcode']);
        add_shortcode('simple_lms_lesson_duration', [__CLASS__, 'lessonDurationShortcode']);
        add_shortcode('simple_lms_lesson_parent_module', [__CLASS__, 'lessonParentModuleShortcode']);
        add_shortcode('simple_lms_lesson_module_title', [__CLASS__, 'lessonModuleTitleShortcode']);
        add_shortcode('simple_lms_lesson_files', [__CLASS__, 'lessonFilesShortcode']);

        // Module shortcodes
        add_shortcode('simple_lms_module_title', [__CLASS__, 'moduleTitleShortcode']);
        add_shortcode('simple_lms_module_content', [__CLASS__, 'moduleContentShortcode']);
        add_shortcode('simple_lms_module_excerpt', [__CLASS__, 'moduleExcerptShortcode']);

        // Course shortcodes
        add_shortcode('simple_lms_course_title', [__CLASS__, 'courseTitleShortcode']);
        add_shortcode('simple_lms_course_content', [__CLASS__, 'courseContentShortcode']);
        add_shortcode('simple_lms_course_excerpt', [__CLASS__, 'courseExcerptShortcode']);

        // Navigation shortcodes
        add_shortcode('simple_lms_course_navigation', [__CLASS__, 'courseNavigationShortcode']);
        add_shortcode('simple_lms_course_overview', [__CLASS__, 'courseOverviewShortcode']);
        add_shortcode('simple_lms_previous_lesson', [__CLASS__, 'previousLessonShortcode']);
        add_shortcode('simple_lms_next_lesson', [__CLASS__, 'nextLessonShortcode']);
        
        // Navigation URL shortcodes (for custom buttons in Elementor)
        add_shortcode('simple_lms_previous_lesson_url', [__CLASS__, 'previousLessonUrlShortcode']);
        add_shortcode('simple_lms_next_lesson_url', [__CLASS__, 'nextLessonUrlShortcode']);
        
        // Navigation state CSS classes (for custom button styling in Elementor)
        add_shortcode('simple_lms_previous_lesson_class', [__CLASS__, 'previousLessonClassShortcode']);
        add_shortcode('simple_lms_next_lesson_class', [__CLASS__, 'nextLessonClassShortcode']);

        // Lesson completion button
        add_shortcode('simple_lms_lesson_complete_toggle', [__CLASS__, 'lessonCompleteToggleShortcode']);
        // Builder-friendly: returns a minimal anchor for use as link target, not styled
        add_shortcode('simple_lms_lesson_complete_toggle_anchor', [__CLASS__, 'lessonCompleteToggleAnchorShortcode']);
        // Text-only label for builder buttons
        add_shortcode('simple_lms_toggle_text', [__CLASS__, 'lessonToggleTextShortcode']);

        // Access control shortcodes
        add_shortcode('simple_lms_access_control', [__CLASS__, 'accessControlShortcode']);

        // WooCommerce purchase button
        add_shortcode('simple_lms_purchase_button', [__CLASS__, 'purchaseButtonShortcode']);
    // WooCommerce purchase URL (builder-friendly, URL-only)
    add_shortcode('simple_lms_purchase_url', [__CLASS__, 'purchaseUrlShortcode']);
    // Optional: explicit lesson variant that resolves to its course
    add_shortcode('simple_lms_lesson_purchase_url', [__CLASS__, 'lessonPurchaseUrlShortcode']);
            // Price helpers (builder-friendly)
            add_shortcode('simple_lms_course_price_html', [__CLASS__, 'coursePriceHtmlShortcode']);
            add_shortcode('simple_lms_course_regular_price', [__CLASS__, 'courseRegularPriceShortcode']);
            add_shortcode('simple_lms_course_sale_price', [__CLASS__, 'courseSalePriceShortcode']);
            add_shortcode('simple_lms_course_price_class', [__CLASS__, 'coursePriceClassShortcode']);
    // Context wrapper for native Product widgets (Elementor/Bricks): set $product while rendering inner content
    add_shortcode('simple_lms_product_context', [__CLASS__, 'productContextShortcode']);
    }

    /**
     * Display lesson video URL for use with Elementor/Bricks video widgets
     */
    public static function lessonVideoUrlShortcode(array $atts = []): string {
        $atts = shortcode_atts(['lesson_id' => 0], $atts);
        $lesson_id = (int) ($atts['lesson_id'] ?: get_the_ID());
        $lesson_id = self::mapId($lesson_id, 'lesson');
        
        if (!$lesson_id || get_post_type($lesson_id) !== 'lesson') {
            return '';
        }

        $video_type = get_post_meta($lesson_id, 'lesson_video_type', true);
        $video_url = get_post_meta($lesson_id, 'lesson_video_url', true);
        $video_file_id = get_post_meta($lesson_id, 'lesson_video_file_id', true);

        // For file type, return the attachment URL
        if ($video_type === 'file' && $video_file_id) {
            $file_url = wp_get_attachment_url($video_file_id);
            return $file_url ? esc_url($file_url) : '';
        }

        // For URL types (youtube, vimeo, url), return the URL
        if (in_array($video_type, ['youtube', 'vimeo', 'url']) && $video_url) {
            return esc_url($video_url);
        }

        return '';
    }

    /**
     * Return purchase URL for the current course/lesson context.
     * Usage in course template: [simple_lms_purchase_url]
     * Usage in lesson template: [simple_lms_purchase_url] (auto-resolves to parent course)
     * You can override with [simple_lms_purchase_url course_id="123"]
     */
    public static function purchaseUrlShortcode($atts): string {
        if (!class_exists('SimpleLMS\WooCommerce_Integration')) {
            return '';
        }
        $atts = shortcode_atts(['course_id' => 0], $atts);
        $course_id = (int) ($atts['course_id'] ?: \SimpleLMS\WooCommerce_Integration::getCurrentCourseId());
        if ($course_id) {
            $course_id = self::mapId($course_id, 'course');
        }
        if (!$course_id) {
            return '';
        }
        return \SimpleLMS\WooCommerce_Integration::get_purchase_url_for_course($course_id) ?: '';
    }

    /**
     * Return purchase URL specifically for a lesson context: resolves parent module->course.
     * Optional lesson_id can be provided; otherwise, current post is used.
     */
    public static function lessonPurchaseUrlShortcode($atts): string {
        if (!class_exists('SimpleLMS\WooCommerce_Integration')) {
            return '';
        }
        $atts = shortcode_atts(['lesson_id' => 0], $atts);
        $lesson_id = (int) ($atts['lesson_id'] ?: get_the_ID());
        $lesson_id = self::mapId($lesson_id, 'lesson');
        if (!$lesson_id || get_post_type($lesson_id) !== 'lesson') {
            // Fallback to generic resolution
            return self::purchaseUrlShortcode([]);
        }
        $module_id = (int) get_post_meta($lesson_id, 'parent_module', true);
        if ($module_id) {
            $module_id = self::mapId($module_id, 'module');
        }
        $course_id = $module_id ? (int) get_post_meta($module_id, 'parent_course', true) : 0;
        if ($course_id) {
            $course_id = self::mapId($course_id, 'course');
        }
        if (!$course_id) {
            // Fallback to generic resolution
            return self::purchaseUrlShortcode([]);
        }
        return WooCommerce_Integration::get_purchase_url_for_course($course_id) ?: '';
    }

    /**
     * Full WooCommerce price HTML for selected course product (includes sale formatting)
     * Usage: [simple_lms_course_price_html] or [simple_lms_course_price_html course_id="123"]
     */
    public static function coursePriceHtmlShortcode($atts): string {
        if (!class_exists('SimpleLMS\WooCommerce_Integration')) {
            return '';
        }
        $atts = shortcode_atts(['course_id' => 0], $atts);
        $course_id = (int) ($atts['course_id'] ?: WooCommerce_Integration::getCurrentCourseId());
        if ($course_id) { $course_id = self::mapId($course_id, 'course'); }
        if (!$course_id) return '';
        $product = WooCommerce_Integration::get_selected_product_for_course($course_id);
        if (!$product) return '';
        $html = $product->get_price_html();
        if (strpos($html, '<ins') !== false) {
            $html = preg_replace('#<ins\b[^>]*>(.*?)</ins>#si', '<strong class="simple-lms-sale-price">$1</strong>', (string)$html);
        }
        return $html;
    }

    /**
     * Regular (non-sale) price only
     */
    public static function courseRegularPriceShortcode($atts): string {
        if (!class_exists('SimpleLMS\WooCommerce_Integration')) {
            return '';
        }
        $atts = shortcode_atts(['course_id' => 0], $atts);
        $course_id = (int) ($atts['course_id'] ?: WooCommerce_Integration::getCurrentCourseId());
        if ($course_id) { $course_id = self::mapId($course_id, 'course'); }
        if (!$course_id) return '';
        $product = WooCommerce_Integration::get_selected_product_for_course($course_id);
        if (!$product) return '';
        $regular = $product->get_regular_price();
        return $regular !== '' ? wc_price((float)$regular) : '';
    }

    /**
     * Sale price only (empty if no sale)
     */
    public static function courseSalePriceShortcode($atts): string {
        if (!class_exists('SimpleLMS\WooCommerce_Integration')) {
            return '';
        }
        $atts = shortcode_atts(['course_id' => 0], $atts);
        $course_id = (int) ($atts['course_id'] ?: WooCommerce_Integration::getCurrentCourseId());
        if ($course_id) { $course_id = self::mapId($course_id, 'course'); }
        if (!$course_id) return '';
        $product = WooCommerce_Integration::get_selected_product_for_course($course_id);
        if (!$product) return '';
        $sale = $product->get_sale_price();
        return $sale !== '' ? wc_price((float)$sale) : '';
    }

    /**
     * Helper: returns a CSS class reflecting pricing state for easy widget targeting
     * Outputs e.g. "has-sale" or "no-sale" (optionally with custom prefix)
     * Usage: [simple_lms_course_price_class] or [simple_lms_course_price_class prefix="my-"]
     */
    public static function coursePriceClassShortcode($atts): string {
        if (!class_exists('SimpleLMS\WooCommerce_Integration')) {
            return '';
        }
        $atts = shortcode_atts(['course_id' => 0, 'prefix' => ''], $atts);
        $course_id = (int) ($atts['course_id'] ?: WooCommerce_Integration::getCurrentCourseId());
        if ($course_id) { $course_id = self::mapId($course_id, 'course'); }
        if (!$course_id) return '';
        $product = WooCommerce_Integration::get_selected_product_for_course($course_id);
        if (!$product) return '';
        $has_sale = $product->get_sale_price() !== '';
        $suffix = $has_sale ? 'has-sale' : 'no-sale';
        return esc_attr($atts['prefix'] . $suffix);
    }

    /**
     * Wrapper that sets global $product to the selected course product while rendering inner content.
     * Usage: [simple_lms_product_context] [woocommerce_product_price] or native widget [/simple_lms_product_context]
     */
    public static function productContextShortcode($atts, $content = ''): string {
        if (!class_exists('SimpleLMS\WooCommerce_Integration')) {
            return do_shortcode($content);
        }
        $atts = shortcode_atts(['course_id' => 0], $atts);
        $course_id = (int) ($atts['course_id'] ?: WooCommerce_Integration::getCurrentCourseId());
        if ($course_id) { $course_id = self::mapId($course_id, 'course'); }
        if (!$course_id) return do_shortcode($content);
        $product = WooCommerce_Integration::get_selected_product_for_course($course_id);
        if (!$product) return do_shortcode($content);
    // Temporarily set the global product
    $prev = isset($GLOBALS['product']) ? $GLOBALS['product'] : null;
    $GLOBALS['product'] = $product;
        try {
            return do_shortcode($content);
        } finally {
            if ($prev !== null) {
                $GLOBALS['product'] = $prev;
            } else {
                unset($GLOBALS['product']);
            }
        }
    }

    /**
     * Display lesson video type
     */
    public static function lessonVideoTypeShortcode(array $atts = []): string {
        $atts = shortcode_atts(['lesson_id' => 0], $atts);
        $lesson_id = (int) ($atts['lesson_id'] ?: get_the_ID());
        $lesson_id = self::mapId($lesson_id, 'lesson');
        
        if (!$lesson_id || get_post_type($lesson_id) !== 'lesson') {
            return '';
        }

        return esc_html(get_post_meta($lesson_id, 'lesson_video_type', true));
    }

    /**
     * Display lesson title
     */
    public static function lessonTitleShortcode(array $atts = []): string {
        $atts = shortcode_atts(['lesson_id' => 0], $atts);
        $lesson_id = (int) ($atts['lesson_id'] ?: get_the_ID());
        $lesson_id = self::mapId($lesson_id, 'lesson');
        
        if (!$lesson_id || get_post_type($lesson_id) !== 'lesson') {
            return '';
        }

        return esc_html(get_the_title($lesson_id));
    }

    /**
     * Display lesson content
     */
    public static function lessonContentShortcode(array $atts = []): string {
        $atts = shortcode_atts(['lesson_id' => 0], $atts);
        $lesson_id = (int) ($atts['lesson_id'] ?: get_the_ID());
        $lesson_id = self::mapId($lesson_id, 'lesson');
        
        if (!$lesson_id || get_post_type($lesson_id) !== 'lesson') {
            return '';
        }

        $post = get_post($lesson_id);
        return $post ? apply_filters('the_content', $post->post_content) : '';
    }

    /**
     * Display lesson excerpt
     */
    public static function lessonExcerptShortcode(array $atts = []): string {
        $atts = shortcode_atts(['lesson_id' => 0], $atts);
        $lesson_id = (int) ($atts['lesson_id'] ?: get_the_ID());
        $lesson_id = self::mapId($lesson_id, 'lesson');
        
        if (!$lesson_id || get_post_type($lesson_id) !== 'lesson') {
            return '';
        }

        return esc_html(get_the_excerpt($lesson_id));
    }

    /**
     * Display lesson permalink
     */
    public static function lessonPermalinkShortcode(array $atts = []): string {
        $atts = shortcode_atts(['lesson_id' => 0], $atts);
        $lesson_id = (int) ($atts['lesson_id'] ?: get_the_ID());
        $lesson_id = self::mapId($lesson_id, 'lesson');
        
        if (!$lesson_id || get_post_type($lesson_id) !== 'lesson') {
            return '';
        }

        return esc_url(get_permalink($lesson_id));
    }

    /**
     * Display lesson duration
     */
    public static function lessonDurationShortcode(array $atts = []): string {
        $atts = shortcode_atts(['lesson_id' => 0], $atts);
        $lesson_id = (int) ($atts['lesson_id'] ?: get_the_ID());
        $lesson_id = self::mapId($lesson_id, 'lesson');
        
        if (!$lesson_id || get_post_type($lesson_id) !== 'lesson') {
            return '';
        }

        return esc_html(get_post_meta($lesson_id, 'lesson_duration', true));
    }

    /**
     * Display lesson parent module
     */
    public static function lessonParentModuleShortcode(array $atts = []): string {
        $atts = shortcode_atts([
            'lesson_id' => 0,
            'field' => 'title'
        ], $atts);
        
        $lesson_id = (int) ($atts['lesson_id'] ?: get_the_ID());
        $lesson_id = self::mapId($lesson_id, 'lesson');
        
        if (!$lesson_id || get_post_type($lesson_id) !== 'lesson') {
            return '';
        }

        $module_id = get_post_meta($lesson_id, 'parent_module', true);
        if (!$module_id) {
            return '';
        }
        $module_id = self::mapId((int)$module_id, 'module');

        switch ($atts['field']) {
            case 'title':
                return esc_html(get_the_title($module_id));
            case 'content':
                $post = get_post($module_id);
                return $post ? apply_filters('the_content', $post->post_content) : '';
            case 'excerpt':
                return esc_html(get_the_excerpt($module_id));
            case 'permalink':
                return esc_url(get_permalink($module_id));
            default:
                return esc_html(get_the_title($module_id));
        }
    }

    /**
     * Display lesson module title (simplified version)
     */
    public static function lessonModuleTitleShortcode(array $atts = []): string {
        $atts = shortcode_atts([
            'lesson_id' => 0,
            'wrapper' => 'span',
            'class' => 'simple-lms-module-title'
        ], $atts);
        
        $lesson_id = (int) ($atts['lesson_id'] ?: get_the_ID());
        $lesson_id = self::mapId($lesson_id, 'lesson');
        
        if (!$lesson_id || get_post_type($lesson_id) !== 'lesson') {
            return '';
        }

        $module_id = get_post_meta($lesson_id, 'parent_module', true);
        if (!$module_id) {
            return '';
        }
        $module_id = self::mapId((int)$module_id, 'module');

        $module_title = get_the_title($module_id);
        if (!$module_title) {
            return '';
        }

        $wrapper = in_array($atts['wrapper'], ['span', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p']) ? $atts['wrapper'] : 'span';
        
        return '<' . $wrapper . ' class="' . esc_attr($atts['class']) . '">' . esc_html($module_title) . '</' . $wrapper . '>';
    }

    /**
     * Display lesson files
     */
    public static function lessonFilesShortcode(array $atts = []): string {
        $atts = shortcode_atts([
            'lesson_id' => 0,
            'show_download' => 1,
            'wrapper_class' => 'simple-lms-lesson-files'
        ], $atts);
        
        $lesson_id = (int) ($atts['lesson_id'] ?: get_the_ID());
        $lesson_id = self::mapId($lesson_id, 'lesson');
        
        if (!$lesson_id || get_post_type($lesson_id) !== 'lesson') {
            return '';
        }

        $attachment_ids = get_post_meta($lesson_id, 'lesson_attachments', true);
        if (!$attachment_ids || !is_array($attachment_ids)) {
            return ''; // Return empty string instead of message
        }

        // Convert attachment IDs to file data structure
        $files = [];
        foreach ($attachment_ids as $attachment_id) {
            $attachment_id = absint($attachment_id);
            if ($attachment_id > 0) {
                $file_url = wp_get_attachment_url($attachment_id);
                $file_title = get_the_title($attachment_id);
                
                if ($file_url && $file_title) {
                    $files[] = [
                        'url' => $file_url,
                        'title' => $file_title,
                        'description' => get_post_field('post_content', $attachment_id) ?: ''
                    ];
                }
            }
        }

        if (empty($files)) {
            return ''; // Return empty string instead of message
        }

        $output = '<div class="' . esc_attr($atts['wrapper_class']) . '">';
        $output .= '<ul class="lesson-files-list">';
        
        foreach ($files as $file) {
            if (!empty($file['url']) && !empty($file['title'])) {
                // Extract file extension from URL or filename
                $file_ext = strtolower(pathinfo(parse_url($file['url'], PHP_URL_PATH), PATHINFO_EXTENSION));
                $display_ext = $file_ext ? strtoupper($file_ext) : 'FILE';
                
                $output .= '<li class="lesson-file-item">';
                $output .= '<span class="file-type-badge file-type-' . esc_attr($file_ext) . '">' . esc_html($display_ext) . '</span>';
                
                if ($atts['show_download']) {
                    $output .= '<a href="' . esc_url($file['url']) . '" download class="lesson-file-link">';
                    $output .= esc_html($file['title']);
                    $output .= '</a>';
                } else {
                    $output .= '<span class="lesson-file-title">' . esc_html($file['title']) . '</span>';
                }
                
                if (!empty($file['description'])) {
                    $output .= '<span class="file-description"> - ' . esc_html($file['description']) . '</span>';
                }
                
                $output .= '</li>';
            }
        }
        
        $output .= '</ul>';
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Display module title
     */
    public static function moduleTitleShortcode(array $atts = []): string {
        $atts = shortcode_atts(['module_id' => 0], $atts);
        $module_id = (int) ($atts['module_id'] ?: get_the_ID());
        $module_id = self::mapId($module_id, 'module');
        
        if (!$module_id || get_post_type($module_id) !== 'module') {
            return '';
        }

        return esc_html(get_the_title($module_id));
    }

    /**
     * Display module content
     */
    public static function moduleContentShortcode(array $atts = []): string {
        $atts = shortcode_atts(['module_id' => 0], $atts);
        $module_id = (int) ($atts['module_id'] ?: get_the_ID());
        $module_id = self::mapId($module_id, 'module');
        
        if (!$module_id || get_post_type($module_id) !== 'module') {
            return '';
        }

        $post = get_post($module_id);
        return $post ? apply_filters('the_content', $post->post_content) : '';
    }

    /**
     * Display module excerpt
     */
    public static function moduleExcerptShortcode(array $atts = []): string {
        $atts = shortcode_atts(['module_id' => 0], $atts);
        $module_id = (int) ($atts['module_id'] ?: get_the_ID());
        $module_id = self::mapId($module_id, 'module');
        
        if (!$module_id || get_post_type($module_id) !== 'module') {
            return '';
        }

        return esc_html(get_the_excerpt($module_id));
    }

    /**
     * Display course title
     */
    public static function courseTitleShortcode(array $atts = []): string {
        $atts = shortcode_atts(['course_id' => 0], $atts);
        $course_id = (int) ($atts['course_id'] ?: 0);
        
        // If no course_id provided, try to find it from current lesson
        if (!$course_id) {
            $current_id = get_the_ID();
            $current_type = get_post_type($current_id);
            
            if ($current_type === 'lesson') {
                // Get course through lesson → module → course
                $module_id = get_post_meta($current_id, 'parent_module', true);
                if ($module_id) {
                    $module_id = self::mapId((int)$module_id, 'module');
                    $course_id = get_post_meta($module_id, 'parent_course', true);
                }
            } elseif ($current_type === 'module') {
                // Get course from module
                $course_id = get_post_meta($current_id, 'parent_course', true);
            } elseif ($current_type === 'course') {
                // Already on course page
                $course_id = $current_id;
            }
        }
        if ($course_id) {
            $course_id = self::mapId((int)$course_id, 'course');
        }
        
        if (!$course_id || get_post_type($course_id) !== 'course') {
            return '';
        }

        return esc_html(get_the_title($course_id));
    }

    /**
     * Display course content
     */
    public static function courseContentShortcode(array $atts = []): string {
        $atts = shortcode_atts(['course_id' => 0], $atts);
        $course_id = (int) $atts['course_id'];
        
        // If no course_id provided, try to determine from current context
        if (!$course_id) {
            $current_id = get_the_ID();
            $current_type = get_post_type($current_id);
            
            if ($current_type === 'lesson') {
                // Get course through lesson → module → course
                $module_id = get_post_meta($current_id, 'parent_module', true);
                if ($module_id) {
                    $module_id = self::mapId((int)$module_id, 'module');
                    $course_id = get_post_meta($module_id, 'parent_course', true);
                }
            } elseif ($current_type === 'module') {
                // Get course from module
                $course_id = get_post_meta($current_id, 'parent_course', true);
            } elseif ($current_type === 'course') {
                // Already on course page
                $course_id = $current_id;
            }
        }
        if ($course_id) {
            $course_id = self::mapId((int)$course_id, 'course');
        }
        
        if (!$course_id || get_post_type($course_id) !== 'course') {
            return '';
        }

        $post = get_post($course_id);
        return $post ? apply_filters('the_content', $post->post_content) : '';
    }

    /**
     * Display course excerpt
     */
    public static function courseExcerptShortcode(array $atts = []): string {
        $atts = shortcode_atts(['course_id' => 0], $atts);
        $course_id = (int) $atts['course_id'];
        
        // If no course_id provided, try to determine from current context
        if (!$course_id) {
            $current_id = get_the_ID();
            $current_type = get_post_type($current_id);
            
            if ($current_type === 'lesson') {
                // Get course through lesson → module → course
                $module_id = get_post_meta($current_id, 'parent_module', true);
                if ($module_id) {
                    $module_id = self::mapId((int)$module_id, 'module');
                    $course_id = get_post_meta($module_id, 'parent_course', true);
                }
            } elseif ($current_type === 'module') {
                // Get course from module
                $course_id = get_post_meta($current_id, 'parent_course', true);
            } elseif ($current_type === 'course') {
                // Already on course page
                $course_id = $current_id;
            }
        }
        if ($course_id) {
            $course_id = self::mapId((int)$course_id, 'course');
        }
        
        if (!$course_id || get_post_type($course_id) !== 'course') {
            return '';
        }

        return esc_html(get_the_excerpt($course_id));
    }

    /**
     * Course navigation shortcode
     */
    public static function courseNavigationShortcode($atts): string {
        $atts = shortcode_atts([
            'course_id' => 0,
            'show_progress' => 1,
            'wrapper_class' => 'simple-lms-course-navigation'
        ], $atts);

        $course_id = (int) ($atts['course_id'] ?: self::getCurrentCourseId());
        
        if (!$course_id) {
            return '<div class="simple-lms-error">Nie można wykryć kursu dla nawigacji</div>';
        }
        // Map to current language course if multilingual
        $course_id = self::mapId((int)$course_id, 'course');

        // Get current lesson ID for highlighting
        $current_lesson_id = get_the_ID();
        if ($current_lesson_id && get_post_type($current_lesson_id) === 'lesson') {
            $current_lesson_id = self::mapId((int)$current_lesson_id, 'lesson');
        }
        $is_lesson_page = get_post_type($current_lesson_id) === 'lesson';

        // Get course modules using cache handler for consistency
        $modules = Cache_Handler::getCourseModules($course_id);

        if (empty($modules)) {
            return '<div class="simple-lms-no-modules">Ten kurs nie ma jeszcze modułów</div>';
        }

        $output = '<div class="' . esc_attr($atts['wrapper_class']) . '">';
        
        foreach ($modules as $module) {
            $lessons = Cache_Handler::getModuleLessons((int) $module->ID);

            $lessons_count = count($lessons);
            $has_current_lesson = false;
            $module_locked = false;
            if (class_exists('SimpleLMS\\Access_Control')) {
                $module_locked = !\SimpleLMS\Access_Control::isModuleUnlocked((int)$module->ID);
            }
            
            // Check if current lesson is in this module
            if ($is_lesson_page) {
                foreach ($lessons as $lesson) {
                    if ($lesson->ID == $current_lesson_id) {
                        $has_current_lesson = true;
                        break;
                    }
                }
            }
            
            $unlock_title = '';
            $unlock_label_html = '';
            if ($module_locked && class_exists('SimpleLMS\\Access_Control')) {
                $info = \SimpleLMS\Access_Control::getModuleUnlockInfo((int)$module->ID);
                if (!empty($info['unlock_ts'])) {
                    $date_str = date_i18n('d.m.Y', (int)$info['unlock_ts']);
                    $unlock_title = ' title="' . esc_attr(sprintf(__('Available from: %s', 'simple-lms'), $date_str)) . '"';
                    $unlock_label_html = '<span class="unlock-date" aria-label="' . esc_attr(sprintf(__('Available from: %s', 'simple-lms'), $date_str)) . '">' . sprintf(__('Available from: %s', 'simple-lms'), esc_html($date_str)) . '</span>';
                }
            }
            $output .= '<div class="accordion-module' . ($module_locked ? ' locked' : '') . '" data-module-id="' . $module->ID . '"' . $unlock_title . '>';
            $output .= '<div class="accordion-header' . ($has_current_lesson ? ' active' : '') . '">';
            $output .= '<h3 class="module-title">' . esc_html($module->post_title) . '</h3>';
            $output .= '<span class="lessons-count">(' . self::getLessonsCountText($lessons_count) . ')</span>';
            if ($unlock_label_html) { $output .= $unlock_label_html; }
            $output .= '<span class="accordion-toggle">' . ($module_locked ? '🔒' : '▼') . '</span>';
            $output .= '</div>';
            
            if (!empty($lessons)) {
                $output .= '<div class="accordion-content' . ($has_current_lesson ? ' expanded' : '') . '">';
                $output .= '<ul class="lessons-list">';
                
                foreach ($lessons as $lesson) {
                    $is_current = ($is_lesson_page && $lesson->ID == $current_lesson_id);
                    $is_completed = self::isLessonCompleted($lesson->ID);
                    
                    $classes = ['lesson-item'];
                    if ($is_current) {
                        $classes[] = 'current-lesson';
                    }
                    if ($is_completed) {
                        $classes[] = 'completed-lesson';
                    }
                    
                    $output .= '<li class="' . implode(' ', $classes) . '" data-lesson-id="' . $lesson->ID . '">';
                    $output .= '<a href="' . get_permalink($lesson->ID) . '" class="lesson-link">';
                    
                    // Completion status circle
                    if ($atts['show_progress']) {
                        if ($is_completed) {
                            $output .= '<span class="completion-status completed" data-lesson-id="' . $lesson->ID . '">✓</span>';
                        } else {
                            $output .= '<span class="completion-status incomplete" data-lesson-id="' . $lesson->ID . '"></span>';
                        }
                    }
                    
                    $output .= '<span class="lesson-title">' . esc_html($lesson->post_title) . '</span>';
                    $output .= '</a>';
                    $output .= '</li>';
                }
                
                $output .= '</ul>';
                $output .= '</div>';
            }
            
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Course overview shortcode - displays all modules and lessons without accordion
     */
    public static function courseOverviewShortcode($atts): string {
        $atts = shortcode_atts([
            'course_id' => 0,
            'show_progress' => 1,
            'wrapper_class' => 'simple-lms-course-overview'
        ], $atts);

        $course_id = (int) ($atts['course_id'] ?: self::getCurrentCourseId());
        
        if (!$course_id) {
            return '<div class="simple-lms-error">Nie można wykryć kursu dla przeglądu</div>';
        }
        // Map to current language course if multilingual
        $course_id = self::mapId((int)$course_id, 'course');

        // Get current lesson ID for highlighting
        $current_lesson_id = get_the_ID();
        if ($current_lesson_id && get_post_type($current_lesson_id) === 'lesson') {
            $current_lesson_id = self::mapId((int)$current_lesson_id, 'lesson');
        }
        $is_lesson_page = get_post_type($current_lesson_id) === 'lesson';

        // Get course modules using cache handler for consistency
        $modules = Cache_Handler::getCourseModules($course_id);

        if (empty($modules)) {
            return '<div class="simple-lms-no-modules">Ten kurs nie ma jeszcze modułów</div>';
        }

        $output = '<div class="' . esc_attr($atts['wrapper_class']) . '">';
        
        foreach ($modules as $module) {
            $lessons = Cache_Handler::getModuleLessons((int) $module->ID);

            $lessons_count = count($lessons);
            $module_locked = false;
            if (class_exists('SimpleLMS\\Access_Control')) {
                $module_locked = !\SimpleLMS\Access_Control::isModuleUnlocked((int)$module->ID);
            }
            
            $unlock_title = '';
            $unlock_label_html = '';
            if ($module_locked && class_exists('SimpleLMS\\Access_Control')) {
                $info = \SimpleLMS\Access_Control::getModuleUnlockInfo((int)$module->ID);
                if (!empty($info['unlock_ts'])) {
                    $date_str = date_i18n('d.m.Y', (int)$info['unlock_ts']);
                    $unlock_title = ' title="' . esc_attr(sprintf(__('Available from: %s', 'simple-lms'), $date_str)) . '"';
                    $unlock_label_html = '<span class="unlock-date" aria-label="' . esc_attr(sprintf(__('Available from: %s', 'simple-lms'), $date_str)) . '">' . sprintf(__('Available from: %s', 'simple-lms'), esc_html($date_str)) . '</span>';
                }
            }
            $output .= '<div class="overview-module' . ($module_locked ? ' locked' : '') . '" data-module-id="' . $module->ID . '"' . $unlock_title . '>';
            $output .= '<div class="module-header">';
            $output .= '<h3 class="module-title">' . esc_html($module->post_title) . '</h3>';
            $output .= '<span class="lessons-count">(' . self::getLessonsCountText($lessons_count) . ')</span>';
            if ($unlock_label_html) { $output .= $unlock_label_html; }
            $output .= '</div>';
            
            if (!empty($lessons)) {
                $output .= '<div class="module-content">';
                $output .= '<ul class="lessons-list">';
                
                foreach ($lessons as $lesson) {
                    $is_current = ($is_lesson_page && $lesson->ID == $current_lesson_id);
                    $is_completed = self::isLessonCompleted($lesson->ID);
                    
                    $classes = ['lesson-item'];
                    if ($is_current) {
                        $classes[] = 'current-lesson';
                    }
                    if ($is_completed) {
                        $classes[] = 'completed-lesson';
                    }
                    
                    $output .= '<li class="' . implode(' ', $classes) . '" data-lesson-id="' . $lesson->ID . '">';
                    $output .= '<a href="' . get_permalink($lesson->ID) . '" class="lesson-link">';
                    
                    // Completion status circle
                    if ($atts['show_progress']) {
                        if ($is_completed) {
                            $output .= '<span class="completion-status completed" data-lesson-id="' . $lesson->ID . '">✓</span>';
                        } else {
                            $output .= '<span class="completion-status incomplete" data-lesson-id="' . $lesson->ID . '"></span>';
                        }
                    }
                    
                    $output .= '<span class="lesson-title">' . esc_html($lesson->post_title) . '</span>';
                    $output .= '</a>';
                    $output .= '</li>';
                }
                
                $output .= '</ul>';
                $output .= '</div>';
            }
            
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Previous lesson shortcode
     */
    public static function previousLessonShortcode($atts): string {
        $atts = shortcode_atts([
            'text' => 'Previous lesson',
            'class' => 'simple-lms-prev-lesson'
        ], $atts);

        $current_lesson_id = get_the_ID();
        if ($current_lesson_id) {
            $current_lesson_id = self::mapId((int)$current_lesson_id, 'lesson');
        }
        if (get_post_type($current_lesson_id) !== 'lesson') {
            return '';
        }

        $previous_lesson = self::getPreviousLesson($current_lesson_id);
        
        if (!$previous_lesson) {
            // No previous lesson - show disabled button
            return '<span class="' . esc_attr($atts['class']) . ' disabled">' . 
                   esc_html($atts['text']) . '</span>';
        }

        return '<a href="' . get_permalink($previous_lesson->ID) . '" class="' . esc_attr($atts['class']) . '">' . 
               esc_html($atts['text']) . '</a>';
    }

    /**
     * Next lesson shortcode
     */
    public static function nextLessonShortcode($atts): string {
        $atts = shortcode_atts([
            'text' => 'Next lesson',
            'class' => 'simple-lms-next-lesson'
        ], $atts);

        $current_lesson_id = get_the_ID();
        if ($current_lesson_id) {
            $current_lesson_id = self::mapId((int)$current_lesson_id, 'lesson');
        }
        if (get_post_type($current_lesson_id) !== 'lesson') {
            return '';
        }

        $next_lesson = self::getNextLesson($current_lesson_id);
        
        if (!$next_lesson) {
            // No next lesson - show disabled button
            return '<span class="' . esc_attr($atts['class']) . ' disabled">' . 
                   esc_html($atts['text']) . '</span>';
        }

        return '<a href="' . get_permalink($next_lesson->ID) . '" class="' . esc_attr($atts['class']) . '">' . 
               esc_html($atts['text']) . '</a>';
    }

    /**
     * Purchase button shortcode
     */
    public static function purchaseButtonShortcode($atts): string {
        if (!class_exists('SimpleLMS\\WooCommerce_Integration')) {
            return '';
        }

        return WooCommerce_Integration::purchase_button_shortcode($atts);
    }

    /**
     * Previous lesson URL shortcode (for custom buttons in Elementor)
     */
    public static function previousLessonUrlShortcode($atts): string {
        $current_lesson_id = get_the_ID();
        if ($current_lesson_id) {
            $current_lesson_id = self::mapId((int)$current_lesson_id, 'lesson');
        }
        if (get_post_type($current_lesson_id) !== 'lesson') {
            return '';
        }

        $previous_lesson = self::getPreviousLesson($current_lesson_id);
        
        if (!$previous_lesson) {
            return ''; // No previous lesson
        }

        return esc_url(get_permalink($previous_lesson->ID));
    }

    /**
     * Next lesson URL shortcode (for custom buttons in Elementor)
     */
    public static function nextLessonUrlShortcode($atts): string {
        $current_lesson_id = get_the_ID();
        if (get_post_type($current_lesson_id) !== 'lesson') {
            return '';
        }

        $next_lesson = self::getNextLesson($current_lesson_id);
        
        if (!$next_lesson) {
            return ''; // No next lesson
        }

        return esc_url(get_permalink($next_lesson->ID));
    }

    /**
     * Previous lesson CSS class shortcode (for button state styling in Elementor)
     */
    public static function previousLessonClassShortcode($atts): string {
        $current_lesson_id = get_the_ID();
        if ($current_lesson_id) {
            $current_lesson_id = self::mapId((int)$current_lesson_id, 'lesson');
        }
        if (get_post_type($current_lesson_id) !== 'lesson') {
            return '';
        }

        $previous_lesson = self::getPreviousLesson($current_lesson_id);
        
        if (!$previous_lesson) {
            return 'simple-lms-nav-disabled'; // No previous lesson - disabled state
        }

        return 'simple-lms-nav-active'; // Has previous lesson - active state
    }

    /**
     * Next lesson CSS class shortcode (for button state styling in Elementor)
     */
    public static function nextLessonClassShortcode($atts): string {
        $current_lesson_id = get_the_ID();
        if ($current_lesson_id) {
            $current_lesson_id = self::mapId((int)$current_lesson_id, 'lesson');
        }
        if (get_post_type($current_lesson_id) !== 'lesson') {
            return '';
        }

        $next_lesson = self::getNextLesson($current_lesson_id);
        
        if (!$next_lesson) {
            return 'simple-lms-nav-disabled'; // No next lesson - disabled state
        }

        return 'simple-lms-nav-active'; // Has next lesson - active state
    }

    // Helper methods

    /**
     * Get current course ID
     */
    private static function getCurrentCourseId(): int {
        $current_post_id = get_the_ID();
        $post_type = get_post_type($current_post_id);
        
        switch ($post_type) {
            case 'course':
                return (int) self::mapId((int)$current_post_id, 'course');
                
            case 'module':
                $course_id = get_post_meta($current_post_id, 'parent_course', true);
                return $course_id ? (int) self::mapId((int)$course_id, 'course') : 0;
                
            case 'lesson':
                $module_id = get_post_meta($current_post_id, 'parent_module', true);
                if ($module_id) {
                    $module_id = self::mapId((int)$module_id, 'module');
                    $course_id = get_post_meta($module_id, 'parent_course', true);
                    return $course_id ? (int) self::mapId((int)$course_id, 'course') : 0;
                }
                break;
        }
        
        return 0;
    }

    /**
     * Get previous lesson in the entire course
     */
    public static function getPreviousLesson($lesson_id) {
        // Get the module and course
        $module_id = get_post_meta($lesson_id, 'parent_module', true);
        if (!$module_id) {
            return null;
        }
        $module_id = self::mapId((int)$module_id, 'module');
        
        $course_id = (int) get_post_meta($module_id, 'parent_course', true);
        if (!$course_id) {
            return null;
        }
        $course_id = self::mapId((int)$course_id, 'course');

        // Get all modules in the course, ordered
        $modules = Cache_Handler::getCourseModules($course_id);

        // Build a flat list of all lessons in the course, in order
        $all_lessons = [];
        foreach ($modules as $module) {
            $lessons = Cache_Handler::getModuleLessons((int) $module->ID);
            $all_lessons = array_merge($all_lessons, $lessons);
        }

        // Find current lesson index and return previous
        $current_index = array_search($lesson_id, array_column($all_lessons, 'ID'));
        
        return ($current_index > 0) ? $all_lessons[$current_index - 1] : null;
    }

    /**
     * Get next lesson in the entire course
     */
    public static function getNextLesson($lesson_id) {
        // Get the module and course
        $module_id = get_post_meta($lesson_id, 'parent_module', true);
        if (!$module_id) {
            return null;
        }
        $module_id = self::mapId((int)$module_id, 'module');
        
        $course_id = (int) get_post_meta($module_id, 'parent_course', true);
        if (!$course_id) {
            return null;
        }
        $course_id = self::mapId((int)$course_id, 'course');

        // Get all modules in the course, ordered
        $modules = Cache_Handler::getCourseModules($course_id);

        // Build a flat list of all lessons in the course, in order
        $all_lessons = [];
        foreach ($modules as $module) {
            $lessons = Cache_Handler::getModuleLessons((int) $module->ID);
            $all_lessons = array_merge($all_lessons, $lessons);
        }

        // Find current lesson index and return next
        $current_index = array_search($lesson_id, array_column($all_lessons, 'ID'));
        
        return ($current_index !== false && $current_index < count($all_lessons) - 1) ? $all_lessons[$current_index + 1] : null;
    }

    /**
     * Display lesson completion toggle button
     */
    public static function lessonCompleteToggleShortcode($atts = []): string {
        global $post;

        // Get lesson ID
        $lesson_id = isset($atts['lesson_id']) ? intval($atts['lesson_id']) : ($post ? $post->ID : 0);
        if ($lesson_id) { $lesson_id = self::mapId($lesson_id, 'lesson'); }
        
        if (!$lesson_id) {
            return '<p class="simple-lms-error">Błąd: Nie podano ID lekcji.</p>';
        }

        // Check if the post exists and is a lesson
        $lesson_post = get_post($lesson_id);
        if (!$lesson_post || $lesson_post->post_type !== 'lesson') {
            return '<p class="simple-lms-error">Błąd: Post o ID ' . $lesson_id . ' nie jest lekcją.</p>';
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p class="simple-lms-info">Zaloguj się, aby oznaczyć lekcję jako ukończoną.</p>';
        }

        $user_id = get_current_user_id();
        $completed_lessons = get_user_meta($user_id, 'simple_lms_completed_lessons', true);
        if (!is_array($completed_lessons)) {
            $completed_lessons = [];
        }

    $btn_text_incomplete = 'Oznacz jako ukończoną';
    $btn_text_complete   = 'Oznacz jako nieukończoną';

    $is_completed = in_array($lesson_id, $completed_lessons);
    $button_text = $is_completed ? $btn_text_complete : $btn_text_incomplete;
    $button_class = $is_completed ? 'btn-lesson-complete completed' : 'btn-lesson-complete';

    $inlineStyle = '';
        $action = $is_completed ? 'simple_lms_uncomplete_lesson' : 'simple_lms_complete_lesson';

        return sprintf(
            '<button type="button" class="%s" data-lesson-id="%d" data-action="%s" data-nonce="%s"%s>
                <span class="button-text">%s</span>
                <span class="spinner" style="display: none;">⏳</span>
            </button>',
            esc_attr($button_class),
            $lesson_id,
            esc_attr($action),
            wp_create_nonce('simple-lms-nonce'),
            $inlineStyle,
            esc_html($button_text)
        );
    }

    /**
     * Builder-friendly: returns a minimal span with data attributes to be targeted by a builder button.
     * Usage: place this shortcode near the button, set the button link to "#simple-lms-toggle".
     */
    public static function lessonCompleteToggleAnchorShortcode($atts = []): string {
        global $post;
        $lesson_id = isset($atts['lesson_id']) ? intval($atts['lesson_id']) : ($post ? $post->ID : 0);
        if ($lesson_id) { $lesson_id = self::mapId($lesson_id, 'lesson'); }
        if (!$lesson_id) return '';
        $is_completed = false;
        if (is_user_logged_in()) {
            $completed = get_user_meta(get_current_user_id(), 'simple_lms_completed_lessons', true);
            $is_completed = is_array($completed) && in_array($lesson_id, $completed, true);
        }
        $action = $is_completed ? 'simple_lms_uncomplete_lesson' : 'simple_lms_complete_lesson';
        return sprintf(
            '<span id="simple-lms-toggle" class="simple-lms-toggle-anchor" data-lesson-toggle="1" data-lesson-id="%d" data-action="%s" data-nonce="%s" aria-hidden="true" style="display:none"></span>',
            $lesson_id,
            esc_attr($action),
            wp_create_nonce('simple-lms-nonce')
        );
    }

    /**
     * Text-only shortcode for builder labels; outputs proper text based on completion state.
     * Usage: [simple_lms_toggle_text lesson_id="123" complete="Oznacz jako nieukończoną" incomplete="Oznacz jako ukończoną"]
     */
    public static function lessonToggleTextShortcode($atts = []): string {
        global $post;
        $atts = shortcode_atts([
            'lesson_id'  => 0,
            'complete'   => 'Oznacz jako nieukończoną',
            'incomplete' => 'Oznacz jako ukończoną',
        ], $atts);

        $lesson_id = intval($atts['lesson_id']) ?: ($post ? intval($post->ID) : 0);
        if ($lesson_id) { $lesson_id = self::mapId($lesson_id, 'lesson'); }
        if (!$lesson_id) return esc_html((string)$atts['incomplete']);

        $is_completed = false;
        if (is_user_logged_in()) {
            $completed = get_user_meta(get_current_user_id(), 'simple_lms_completed_lessons', true);
            $is_completed = is_array($completed) && in_array($lesson_id, $completed, true);
        }
        return esc_html($is_completed ? (string)$atts['complete'] : (string)$atts['incomplete']);
    }

    /**
     * Extract YouTube video ID from URL
     */
    private static function getYouTubeVideoId($url): ?string {
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches);
        return $matches[1] ?? null;
    }

    /**
     * Extract Vimeo video ID from URL
     */
    private static function getVimeoVideoId($url): ?string {
        preg_match('/vimeo\.com\/(\d+)/', $url, $matches);
        return $matches[1] ?? null;
    }

    /**
     * Get correct Polish declension for lessons count
     * 
     * @param int $count Number of lessons
     * @return string Properly declined word form
     */
    public static function getLessonsCountText(int $count): string {
        if ($count == 0) {
            return '0 lekcji';
        } elseif ($count == 1) {
            return '1 lekcja';
        } elseif ($count >= 2 && $count <= 4) {
            return $count . ' lekcje';
        } else {
            return $count . ' lekcji';
        }
    }

    /**
     * Check if lesson is completed by current user
     */
    public static function isLessonCompleted($lesson_id): bool {
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();
        
        // Use Progress_Tracker if available
        if (class_exists('SimpleLMS\Progress_Tracker')) {
            $result = Progress_Tracker::isLessonCompleted($user_id, $lesson_id);
            // If Progress_Tracker found a result, use it
            if ($result) {
                return true;
            }
        }
        
        // Check user meta with correct key used by ajax-handlers
        $completed_lessons = get_user_meta($user_id, 'simple_lms_completed_lessons', true);
        
        if (!is_array($completed_lessons)) {
            $completed_lessons = [];
        }

        return in_array($lesson_id, $completed_lessons);
    }

    /**
     * Access control shortcode
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string
     */
    public static function accessControlShortcode($atts, $content = ''): string {
        $atts = shortcode_atts([
            'course_id' => 0,
            'access' => 'with', // 'with' or 'without'
            'class' => ''
        ], $atts);

        $course_id = (int) ($atts['course_id'] ?: self::getCurrentCourseId());
        if ($course_id) {
            $course_id = self::mapId($course_id, 'course');
        }
        
        if (!$course_id) {
            return '';
        }

        // Use Access_Control class for tag-based access (backward compatibility: admin always has access)
        $has_access = false;
        if (class_exists('SimpleLMS\Access_Control')) {
            $has_access = \SimpleLMS\Access_Control::userHasAccessToCourse($course_id);
        }
        
        $show_content = ($atts['access'] === 'with') ? $has_access : !$has_access;
        
        if (!$show_content) {
            return '';
        }

        $class = $atts['class'] ? ' ' . esc_attr($atts['class']) : '';
        
        return '<div class="simple-lms-access-controlled' . $class . '">' . do_shortcode($content) . '</div>';
    }
}
