<?php
declare(strict_types=1);

namespace SimpleLMS;

/**
 * Access control for Simple LMS (clean)
 */

if (!defined('ABSPATH')) { exit; }

use const \DAY_IN_SECONDS;
use const \HOUR_IN_SECONDS;
use function \add_action;
use function \add_filter;
use function \add_shortcode;
use function \current_time;
use function \current_user_can;
use function \delete_transient;
use function \do_shortcode;
use function \esc_attr;
use function \get_current_user_id;
use function \get_permalink;
use function \get_post_meta;
use function \get_post_type;
use function \get_the_ID;
use function \get_transient;
use function \get_user_meta;
use function \is_singular;
use function \is_user_logged_in;
use function \set_transient;
use function \shortcode_atts;
use function \update_user_meta;
use function \wp_add_inline_style;
use function \wp_enqueue_style;
use function \wp_register_style;
use function \wp_style_is;
use function \wp_redirect;

/**
 * Get the user meta key for course access
 * 
 * @return string Meta key used to store course access array
 */
function simple_lms_get_course_access_meta_key(): string { return 'simple_lms_course_access'; }

/**
 * Assign course access to a user
 * 
 * Grants a user access to a course by adding it to their access list.
 * Handles access expiration and drip schedule initialization.
 * 
 * @param int $user_id User ID to grant access to
 * @param int $course_id Course ID to grant access for
 * @return bool True on success, false if invalid IDs
 */
function simple_lms_assign_course_access_tag(int $user_id, int $course_id): bool {
    if ($user_id <= 0 || $course_id <= 0) return false;
    $key = simple_lms_get_course_access_meta_key();
    $courses = (array) get_user_meta($user_id, $key, true);
    if (!in_array($course_id, array_map('intval', $courses), true)) {
        $courses[] = (int) $course_id;
        update_user_meta($user_id, $key, array_values(array_unique(array_map('intval', $courses))));
        
        // Set expiration date if course has access duration configured
        $duration_value = (int) get_post_meta($course_id, '_access_duration_value', true);
        $duration_unit = get_post_meta($course_id, '_access_duration_unit', true) ?: 'days';
        
        // Backward compatibility: migrate old _access_duration_days to new format
        if ($duration_value === 0) {
            $legacy_days = (int) get_post_meta($course_id, '_access_duration_days', true);
            if ($legacy_days > 0) {
                $duration_value = $legacy_days;
                $duration_unit = 'days';
            }
        }
        
        if ($duration_value > 0) {
            // Convert to days based on unit
            $days_multiplier = [
                'days' => 1,
                'weeks' => 7,
                'months' => 30,
                'years' => 365
            ];
            $duration_days = $duration_value * ($days_multiplier[$duration_unit] ?? 1);
            
            // Determine start date based on access schedule mode
            $access_mode = get_post_meta($course_id, '_access_schedule_mode', true) ?: 'purchase';
            $start_timestamp = current_time('timestamp');
            
            if ($access_mode === 'fixed_date') {
                // If fixed_date mode, start counting from the fixed date (not purchase date)
                $fixed_date = get_post_meta($course_id, '_access_fixed_date', true);
                if (!empty($fixed_date)) {
                    $fixed_timestamp = strtotime($fixed_date . ' 00:00:00');
                    if ($fixed_timestamp !== false && $fixed_timestamp > 0) {
                        // If fixed date is in the future, start from fixed date
                        // If fixed date is in the past, start from now (already unlocked)
                        $start_timestamp = max($fixed_timestamp, current_time('timestamp'));
                    }
                }
            }
            // For 'purchase' and 'drip' modes, start from now (purchase/assignment time)
            
            $expiration_timestamp = $start_timestamp + ($duration_days * DAY_IN_SECONDS);
            update_user_meta($user_id, 'simple_lms_course_access_expiration_' . $course_id, $expiration_timestamp);
        } else {
            // Remove any existing expiration if duration is 0 (unlimited)
            delete_user_meta($user_id, 'simple_lms_course_access_expiration_' . $course_id);
        }
        
        // Invalidate cache
        delete_transient('slms_access_' . $user_id . '_' . $course_id);
    }
    return true;
}

/**
 * Remove course access from a user
 * 
 * Revokes a user's access to a course by removing it from their access list.
 * 
 * @param int $user_id User ID to revoke access from
 * @param int $course_id Course ID to revoke access for
 * @return bool True on success, false if invalid IDs
 */
function simple_lms_remove_course_access_tag(int $user_id, int $course_id): bool {
    if ($user_id <= 0 || $course_id <= 0) return false;
    $key = simple_lms_get_course_access_meta_key();
    $courses = (array) get_user_meta($user_id, $key, true);
    $filtered = array_values(array_filter($courses, static fn($c) => (int) $c !== (int) $course_id));
    update_user_meta($user_id, $key, $filtered);
    
    // Invalidate cache
    delete_transient('slms_access_' . $user_id . '_' . $course_id);
    
    return true;
}

/**
 * Check if a user has access to a course
 * 
 * Verifies user has active access including expiration checks.
 * Results are cached for 12 hours. Filterable via 'simple_lms_user_has_course_access'.
 * 
 * @param int $user_id User ID to check
 * @param int $course_id Course ID to check access for
 * @return bool True if user has valid access, false otherwise
 */
function simple_lms_user_has_course_access(int $user_id, int $course_id): bool {
    if ($user_id <= 0 || $course_id <= 0) return false;
    
    // Use transient cache (12h TTL)
    $cache_key = 'slms_access_' . $user_id . '_' . $course_id;
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return (bool) $cached;
    }
    
    $courses = (array) get_user_meta($user_id, simple_lms_get_course_access_meta_key(), true);
    $has_access = in_array((int) $course_id, array_map('intval', $courses), true);
    
    // Check if access has expired
    if ($has_access) {
        $expiration = (int) get_user_meta($user_id, 'simple_lms_course_access_expiration_' . $course_id, true);
        if ($expiration > 0 && current_time('timestamp') > $expiration) {
            // Access expired - remove it
            simple_lms_remove_course_access_tag($user_id, $course_id);
            $has_access = false;
        }
    }
    
    // Cache result for 12 hours
    set_transient($cache_key, $has_access ? 1 : 0, 12 * HOUR_IN_SECONDS);
    
    /**
     * Filter: simple_lms_user_has_course_access
     *
     * Allows external code to override or extend course access logic.
     * Should return a boolean.
     *
     * @param bool $has_access Computed access
     * @param int  $user_id    User ID
     * @param int  $course_id  Course ID
     */
    $has_access = (bool) apply_filters('simple_lms_user_has_course_access', $has_access, $user_id, $course_id);

    return $has_access;
}

/**
 * Access Control class
 * 
 * Handles course access verification, drip content scheduling,
 * and access-related shortcodes.
 */
class Access_Control {
    private static function userCourseStartMetaKey(int $course_id): string { return 'simple_lms_course_access_start_' . $course_id; }

    /**
     * Backward compatibility static instance (legacy init())
     * @var Access_Control|null
     */
    private static ?Access_Control $instance = null;

    /**
     * Logger instance
     */
    private Logger $logger;

    /**
     * Constructor with dependency injection
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Register hooks (instance-based)
     */
    public function register(): void
    {
        add_action('init', [$this, 'registerShortcodes']);
        add_filter('body_class', [$this, 'addAccessControlBodyClasses']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAccessControlAssets']);
        add_action('template_redirect', [$this, 'enforceDripAccess']);
    }

    /**
     * Legacy static init() shim for backward compatibility
     */
    public static function init(): void
    {
        if (self::$instance instanceof self) {
            return; // Already initialized via container
        }
        // Fallback minimal logger if container not used yet
        $debug = defined('WP_DEBUG') ? (bool) \WP_DEBUG : false;
        $verboseOpt = (string) \get_option('simple_lms_verbose_logging', 'no') === 'yes';
        $debugEnabled = (bool) \apply_filters('simple_lms_debug_enabled', ($debug || $verboseOpt));
        $logger = new Logger('simple-lms', $debugEnabled);
        self::$instance = new self($logger);
        self::$instance->register();
    }

    public function registerShortcodes(): void {
        add_shortcode('simple_lms_access_control', [$this, 'accessControlShortcode']);
        add_shortcode('simple_lms_access', [$this, 'accessControlShortcode']);
        add_shortcode('simple_lms_access_expiration', [$this, 'accessExpirationShortcode']);
    }

    /**
     * Get courses the user has access to (public helper)
     *
     * @param int $user_id User ID
     * @return array<int, \WP_Post> List of course posts
     */
    public static function getUserCourses(int $user_id): array {
        if ($user_id <= 0) {
            return [];
        }

        // Read course IDs from user meta
        $course_ids = (array) get_user_meta($user_id, simple_lms_get_course_access_meta_key(), true);
        $course_ids = array_values(array_filter(array_map('intval', $course_ids), static fn($id) => $id > 0));

        if (empty($course_ids)) {
            return [];
        }

        // Fetch existing course posts only
        $courses = get_posts([
            'post_type'      => 'course',
            'post__in'       => $course_ids,
            'posts_per_page' => -1,
            'orderby'        => 'post__in',
        ]);

        return is_array($courses) ? $courses : [];
    }

    /**
     * Check if user has course access (admin bypass enabled)
     * 
     * Unified helper for checking course access with automatic admin bypass.
     * Delegates to simple_lms_user_has_course_access() with filter hook support.
     * 
     * @param int $user_id User ID to check
     * @param int $course_id Course ID to check
     * @return bool True if user has access or is admin, false otherwise
     */
    public static function userHasCourseAccess(int $user_id, int $course_id): bool {
        if ($course_id <= 0 || $user_id <= 0) return false;
        if (user_can($user_id, 'manage_options')) return true;
        return simple_lms_user_has_course_access($user_id, $course_id);
    }

    public static function userHasAccessToCourse(int $course_id): bool {
        if ($course_id <= 0) return false;
        $uid = (int) get_current_user_id();
        if ($uid <= 0) return false;
        return self::userHasCourseAccess($uid, $course_id);
    }

    /**
     * Check if current user has access to a lesson
     * 
     * Verifies both course access and drip schedule unlocking for lesson's module.
     * 
     * @param int $lesson_id Lesson post ID
     * @return bool True if user has access to the lesson
     */
    public static function userHasAccessToLesson(int $lesson_id): bool {
        if ($lesson_id <= 0) return false;
        $module_id = (int) get_post_meta($lesson_id, 'parent_module', true);
        if ($module_id <= 0) return false;
        $course_id = (int) get_post_meta($module_id, 'parent_course', true);
        if ($course_id <= 0) return false;
        if (!self::userHasAccessToCourse($course_id)) return false;
        return self::isModuleUnlocked($module_id);
    }

    /**
     * Check if a module is unlocked for current user
     * 
     * Evaluates drip schedule rules (immediate, delay, fixed date) to determine
     * if module content should be accessible.
     * 
     * @param int $module_id Module post ID
     * @return bool True if module is unlocked, false if locked by drip schedule
     */
    public static function isModuleUnlocked(int $module_id): bool {
        if ($module_id <= 0) return false;
        $course_id = (int) get_post_meta($module_id, 'parent_course', true);
        if ($course_id <= 0) return false;

        $mode = get_post_meta($course_id, '_access_schedule_mode', true) ?: 'purchase';
        if ($mode === 'purchase') return true;

        if ($mode === 'fixed_date') {
            $date = (string) get_post_meta($course_id, '_access_fixed_date', true);
            if ($date === '') return true;
            $unlock_ts = strtotime($date . ' 00:00:00');
            if ($unlock_ts === false) return true;
            return (int) current_time('timestamp') >= (int) $unlock_ts;
        }

        if ($mode === 'drip') {
            $strategy = get_post_meta($course_id, '_drip_strategy', true) ?: 'interval';
            $uid = (int) get_current_user_id();
            if ($uid <= 0) return false;
            $start_ts = self::ensureUserCourseAccessStart($uid, $course_id);
            if ($start_ts <= 0) return false;

            if ($strategy === 'interval') {
                $interval_days = (int) get_post_meta($course_id, '_drip_interval_days', true);
                if ($interval_days <= 0) return true;
                $modules = Cache_Handler::getCourseModules($course_id);
                $idx = 0;
                foreach ($modules as $i => $m) {
                    if ((int) $m->ID === $module_id) { $idx = (int) $i; break; }
                }
                $required_days = $idx * $interval_days;
                $elapsed_days = (int) floor(((int) current_time('timestamp') - $start_ts) / (int) DAY_IN_SECONDS);
                return $elapsed_days >= $required_days;
            }

            $mode_module = get_post_meta($module_id, '_module_drip_mode', true) ?: 'days';
            if ($mode_module === 'manual') {
                return ((int) get_post_meta($module_id, '_module_manual_unlocked', true)) === 1;
            }
            $drip_days = (int) get_post_meta($module_id, '_module_drip_days', true);
            $elapsed_days = (int) floor(((int) current_time('timestamp') - $start_ts) / (int) DAY_IN_SECONDS);
            return $elapsed_days >= $drip_days;
        }

        return true;
    }

    public static function getModuleUnlockInfo(int $module_id): array {
        if ($module_id <= 0) return ['unlock_ts' => null, 'mode' => 'none'];
        $course_id = (int) get_post_meta($module_id, 'parent_course', true);
        if ($course_id <= 0) return ['unlock_ts' => null, 'mode' => 'none'];

        $mode = get_post_meta($course_id, '_access_schedule_mode', true) ?: 'purchase';
        if ($mode === 'purchase') return ['unlock_ts' => null, 'mode' => 'purchase'];

        if ($mode === 'fixed_date') {
            $date = (string) get_post_meta($course_id, '_access_fixed_date', true);
            if ($date === '') return ['unlock_ts' => null, 'mode' => 'fixed_date'];
            $unlock_ts = strtotime($date . ' 00:00:00');
            return ['unlock_ts' => ($unlock_ts === false ? null : (int) $unlock_ts), 'mode' => 'fixed_date'];
        }

        if ($mode === 'drip') {
            $uid = (int) get_current_user_id();
            if ($uid <= 0) return ['unlock_ts' => null, 'mode' => 'drip'];
            $start_ts = self::ensureUserCourseAccessStart($uid, $course_id);
            if ($start_ts <= 0) return ['unlock_ts' => null, 'mode' => 'drip'];

            $strategy = get_post_meta($course_id, '_drip_strategy', true) ?: 'interval';
            if ($strategy === 'interval') {
                $interval_days = (int) get_post_meta($course_id, '_drip_interval_days', true);
                if ($interval_days <= 0) return ['unlock_ts' => (int) $start_ts, 'mode' => 'drip'];
                $modules = Cache_Handler::getCourseModules($course_id);
                $idx = 0;
                foreach ($modules as $i => $m) {
                    if ((int) $m->ID === $module_id) { $idx = (int) $i; break; }
                }
                $required_days = $idx * $interval_days;
                $unlock_ts = (int) $start_ts + ($required_days * (int) DAY_IN_SECONDS);
                return ['unlock_ts' => $unlock_ts, 'mode' => 'drip'];
            }

            $mode_module = get_post_meta($module_id, '_module_drip_mode', true) ?: 'days';
            if ($mode_module === 'manual') {
                return ['unlock_ts' => null, 'mode' => 'drip'];
            }
            $drip_days = (int) get_post_meta($module_id, '_module_drip_days', true);
            $unlock_ts = (int) $start_ts + ($drip_days * (int) DAY_IN_SECONDS);
            return ['unlock_ts' => $unlock_ts, 'mode' => 'drip'];
        }

        return ['unlock_ts' => null, 'mode' => 'purchase'];
    }

    public static function ensureUserCourseAccessStart(int $user_id, int $course_id): int {
        if ($user_id <= 0 || $course_id <= 0) return 0;
        $key = self::userCourseStartMetaKey($course_id);
        $ts = (int) get_user_meta($user_id, $key, true);
        if ($ts > 0) return $ts;
        if (simple_lms_user_has_course_access($user_id, $course_id)) {
            $now = (int) current_time('timestamp', true);
            update_user_meta($user_id, $key, $now);
            return $now;
        }
        return 0;
    }

    public function enforceDripAccess(): void {
        if (!is_user_logged_in()) return;
        if (!is_singular(['lesson', 'module'])) return;
        if (current_user_can('edit_posts')) return;

        $post_id = (int) get_the_ID();
        if ($post_id <= 0) return;
        $type = get_post_type($post_id);
        if ($type === 'lesson') {
            if (!self::userHasAccessToLesson($post_id)) {
                $module_id = (int) get_post_meta($post_id, 'parent_module', true);
                $course_id = $module_id ? (int) get_post_meta($module_id, 'parent_course', true) : 0;
                if ($course_id) {
                    $this->logger->debug('Redirecting due to locked lesson (drip/access)', [
                        'lesson_id' => $post_id,
                        'module_id' => $module_id,
                        'course_id' => $course_id
                    ]);
                    wp_redirect(get_permalink($course_id)); exit;
                }
            }
        } elseif ($type === 'module') {
            if (!self::isModuleUnlocked($post_id)) {
                $course_id = (int) get_post_meta($post_id, 'parent_course', true);
                if ($course_id) {
                    $this->logger->debug('Redirecting due to locked module (drip/access)', [
                        'module_id' => $post_id,
                        'course_id' => $course_id
                    ]);
                    wp_redirect(get_permalink($course_id)); exit;
                }
            }
        }
    }

    public function addAccessControlBodyClasses(array $classes): array {
        $post_id = (int) get_the_ID();
        $type = $post_id ? get_post_type($post_id) : '';
        $course_id = 0;
        if ($type === 'course') {
            $course_id = $post_id;
        } elseif ($type === 'module') {
            $course_id = (int) get_post_meta($post_id, 'parent_course', true);
        } elseif ($type === 'lesson') {
            $module = (int) get_post_meta($post_id, 'parent_module', true);
            $course_id = $module ? (int) get_post_meta($module, 'parent_course', true) : 0;
        }

        if ($course_id > 0) {
            $classes[] = self::userHasAccessToCourse($course_id) ? 'simple-lms-has-access' : 'simple-lms-no-access';
        }
        return $classes;
    }

    public function enqueueAccessControlAssets(): void {
        $handle = 'simple-lms-access';
        $css = '.simple-lms-course-navigation .accordion-module.locked .accordion-toggle{opacity:.6}';
        if (!wp_style_is($handle, 'registered')) {
            wp_register_style($handle, false, [], defined('SIMPLE_LMS_VERSION') ? SIMPLE_LMS_VERSION : null);
        }
        wp_enqueue_style($handle);
        wp_add_inline_style($handle, $css);
    }

    public function accessControlShortcode($atts, string $content = ''): string {
        $atts = shortcode_atts([
            'course_id' => 0,
            'access' => 'with',
            'class' => '',
        ], $atts);
        $course_id = (int) ($atts['course_id'] ?: self::getCurrentCourseId());
        if ($course_id <= 0) return '';
        $has_access = self::userHasAccessToCourse($course_id);
        $show = ($atts['access'] === 'with') ? $has_access : !$has_access;
        if (!$show) return '';
        $class = $atts['class'] ? ' ' . esc_attr((string) $atts['class']) : '';
        return '<div class="simple-lms-access-controlled' . $class . '">' . do_shortcode($content) . '</div>';
    }

    /**
     * Shortcode to display course access expiration information
     * Usage: [simple_lms_access_expiration course_id="123" format="days"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function accessExpirationShortcode($atts): string {
        $atts = shortcode_atts([
            'course_id' => 0,
            'format' => 'full', // 'full', 'days', 'date'
            'class' => '',
        ], $atts);
        
        $course_id = (int) ($atts['course_id'] ?: self::getCurrentCourseId());
        if ($course_id <= 0) return '';
        
        $user_id = (int) get_current_user_id();
        if ($user_id <= 0) return '';
        
        if (!self::userHasAccessToCourse($course_id)) {
            return '';
        }
        
        $expiration = simple_lms_get_course_access_expiration($user_id, $course_id);
        if ($expiration === null) {
            return ''; // Unlimited access - don't show anything
        }
        
        $days_remaining = simple_lms_get_course_access_days_remaining($user_id, $course_id);
        $date_format = get_option('date_format');
        $expiration_date = date_i18n((string) $date_format, $expiration);
        
        $class = $atts['class'] ? ' ' . esc_attr((string) $atts['class']) : '';
        $warning_class = ($days_remaining !== null && $days_remaining <= 7) ? ' simple-lms-expiration-warning' : '';
        
        $output = '<div class="simple-lms-access-expiration' . $class . $warning_class . '">';
        
        switch ($atts['format']) {
            case 'days':
                if ($days_remaining === 0) {
                    $output .= '<strong>' . __('Access expired', 'simple-lms') . '</strong>';
                } else {
                    $output .= sprintf(
                        _n('Pozostał %d dzień dostępu', 'Pozostało %d dni dostępu', $days_remaining, 'simple-lms'),
                        $days_remaining
                    );
                }
                break;
                
            case 'date':
                $output .= sprintf(
                    __('Access to: %s', 'simple-lms'),
                    '<strong>' . esc_html($expiration_date) . '</strong>'
                );
                break;
                
            case 'full':
            default:
                if ($days_remaining === 0) {
                    $output .= '<strong>' . __('Access expired', 'simple-lms') . '</strong>';
                } else {
                    $output .= sprintf(
                        __('Your access expires in %s (%s)', 'simple-lms'),
                        '<strong>' . sprintf(
                            _n('%d dzień', '%d dni', $days_remaining, 'simple-lms'),
                            $days_remaining
                        ) . '</strong>',
                        esc_html($expiration_date)
                    );
                }
                break;
        }
        
        $output .= '</div>';
        
        return $output;
    }

    public static function getCurrentCourseId(): int {
        $current_id = (int) get_the_ID();
        if ($current_id <= 0) return 0;
        $type = get_post_type($current_id);
        if ($type === 'course') return $current_id;
        if ($type === 'module') return (int) get_post_meta($current_id, 'parent_course', true);
        if ($type === 'lesson') {
            $module = (int) get_post_meta($current_id, 'parent_module', true);
            return $module ? (int) get_post_meta($module, 'parent_course', true) : 0;
        }
        return 0;
    }
}

/**
 * Get user's course access expiration date
 * 
 * @param int $user_id User ID
 * @param int $course_id Course ID
 * @return int|null Expiration timestamp or null if unlimited
 */
function simple_lms_get_course_access_expiration(int $user_id, int $course_id): ?int {
    if ($user_id <= 0 || $course_id <= 0) return null;
    $expiration = (int) get_user_meta($user_id, 'simple_lms_course_access_expiration_' . $course_id, true);
    return $expiration > 0 ? $expiration : null;
}

/**
 * Get days remaining for course access
 * 
 * @param int $user_id User ID
 * @param int $course_id Course ID
 * @return int|null Days remaining or null if unlimited
 */
function simple_lms_get_course_access_days_remaining(int $user_id, int $course_id): ?int {
    $expiration = simple_lms_get_course_access_expiration($user_id, $course_id);
    if ($expiration === null) return null;
    
    $now = current_time('timestamp');
    if ($now >= $expiration) return 0;
    
    $seconds_remaining = $expiration - $now;
    return (int) ceil($seconds_remaining / DAY_IN_SECONDS);
}

/**
 * Cleanup expired course access (run by cron)
 */
function simple_lms_cleanup_expired_access(): void {
    global $wpdb;
    
    // Get all user meta keys for course access expirations
    $meta_keys = $wpdb->get_col(
        "SELECT DISTINCT meta_key 
        FROM {$wpdb->usermeta} 
        WHERE meta_key LIKE 'simple_lms_course_access_expiration_%'"
    );
    
    $now = current_time('timestamp');
    $cleaned_count = 0;
    
    foreach ($meta_keys as $meta_key) {
        // Extract course_id from meta_key
        $course_id = (int) str_replace('simple_lms_course_access_expiration_', '', $meta_key);
        if ($course_id <= 0) continue;
        
        // Get all users with this expiration meta
        $users_with_expiration = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT user_id, meta_value 
                FROM {$wpdb->usermeta} 
                WHERE meta_key = %s",
                $meta_key
            )
        );
        
        foreach ($users_with_expiration as $row) {
            $user_id = (int) $row->user_id;
            $expiration = (int) $row->meta_value;
            
            if ($expiration > 0 && $now > $expiration) {
                // Remove expired access
                simple_lms_remove_course_access_tag($user_id, $course_id);
                delete_user_meta($user_id, $meta_key);
                $cleaned_count++;
                
                error_log(sprintf(
                    'Simple LMS: Removed expired access for user %d to course %d (expired on %s)',
                    $user_id,
                    $course_id,
                    date('Y-m-d H:i:s', $expiration)
                ));
            }
        }
    }
    
    if ($cleaned_count > 0) {
        error_log('Simple LMS: Cleaned up ' . $cleaned_count . ' expired course access entries');
    }
}

/**
 * Setup cron for cleaning expired access
 */
function simple_lms_setup_access_expiration_cron(): void {
    if (!wp_next_scheduled('simple_lms_cleanup_expired_access')) {
        wp_schedule_event(time(), 'daily', 'simple_lms_cleanup_expired_access');
    }
}
add_action('wp', 'SimpleLMS\simple_lms_setup_access_expiration_cron');
add_action('simple_lms_cleanup_expired_access', 'SimpleLMS\simple_lms_cleanup_expired_access');

/**
 * Cleanup cron on plugin deactivation
 */
function simple_lms_deactivate_access_expiration_cron(): void {
    $timestamp = wp_next_scheduled('simple_lms_cleanup_expired_access');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'simple_lms_cleanup_expired_access');
    }
}
register_deactivation_hook(SIMPLE_LMS_PLUGIN_DIR . 'simple-lms.php', 'SimpleLMS\simple_lms_deactivate_access_expiration_cron');
