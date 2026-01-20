<?php
namespace SimpleLMS;

/**
 * Analytics tracking for Simple LMS
 * 
 * Provides event tracking and integration with external analytics services.
 * 
 * @package SimpleLMS
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Analytics Tracker class
 * 
 * Tracks user interactions and learning events for analytics purposes.
 */
class Analytics_Tracker {

    /** @var Logger|null */
    private ?Logger $logger = null;

    /**
     * Constructor with optional Logger injection
     */
    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger;
    }
    
    /**
     * Database table name for analytics events
     */
    private const TABLE_NAME = 'simple_lms_analytics';
    
    /**
     * Supported event types
     */
    public const EVENT_LESSON_STARTED = 'lesson_started';
    public const EVENT_LESSON_COMPLETED = 'lesson_completed';
    public const EVENT_VIDEO_WATCHED = 'video_watched';
    public const EVENT_COURSE_ENROLLED = 'course_enrolled';
    public const EVENT_COURSE_PROGRESS_MILESTONE = 'course_progress_milestone';
    public const EVENT_QUIZ_COMPLETED = 'quiz_completed';
    
    /**
     * Initialize analytics tracking
     * 
     * @return void
     */
    public static function init(): void {
        // Backward compatibility shim â€“ prefer container managed instance
        try {
            $container = ServiceContainer::getInstance();
            if ($container->has(self::class)) {
                $instance = $container->get(self::class);
                if (method_exists($instance, 'register')) {
                    $instance->register();
                    return;
                }
            }
        } catch (\Throwable $e) {
            // fall through
        }
        // Legacy fallback
        (new self())->register();
    }

    /**
     * Instance registration of hooks
     */
    public function register(): void
    {
        add_action('simple_lms_lesson_progress_updated', [__CLASS__, 'trackLessonProgress'], 10, 3);
        add_action('init', [__CLASS__, 'maybeCreateAnalyticsTable']);
        if ($this->logger) {
            $this->logger->debug('Analytics_Tracker hooks registered');
        }
    }
    
    /**
     * Track a custom event
     * 
     * @param string $event_type Event type constant
     * @param int $user_id User ID
     * @param array $data Additional event data
     * @return bool Success status
     */
    public static function track_event(string $event_type, int $user_id, array $data = []): bool {
        try {
            // Fire WordPress action for external integrations
            do_action('simple_lms_analytics_event', $event_type, $user_id, $data);
            do_action("simple_lms_analytics_{$event_type}", $user_id, $data);
            
            // Check if analytics is enabled
            $enabled = (bool) get_option('simple_lms_analytics_enabled', false);
            if (!$enabled) {
                return true; // Don't store but don't fail
            }
            
            // Store event in database
            global $wpdb;
            $table_name = $wpdb->prefix . self::TABLE_NAME;
            
            $result = $wpdb->insert(
                $table_name,
                [
                    'event_type' => $event_type,
                    'user_id' => $user_id,
                    'event_data' => json_encode($data),
                    'created_at' => current_time('mysql'),
                    'ip_address' => self::get_user_ip(),
                    'user_agent' => self::get_user_agent()
                ],
                ['%s', '%d', '%s', '%s', '%s', '%s']
            );
            
            return $result !== false;
            
        } catch (\Exception $e) {
            self::log('error', 'Analytics track_event failed', ['event' => $event_type, 'userId' => $user_id, 'error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Track lesson progress (hooked automatically)
     * 
     * @param int $user_id User ID
     * @param int $lesson_id Lesson ID
     * @param bool $completed Whether lesson was completed
     * @return void
     */
    public static function trackLessonProgress(int $user_id, int $lesson_id, bool $completed): void {
        $event_type = $completed ? self::EVENT_LESSON_COMPLETED : self::EVENT_LESSON_STARTED;
        
        $module_id = (int) get_post_meta($lesson_id, 'parent_module', true);
        $course_id = $module_id ? (int) get_post_meta($module_id, 'parent_course', true) : 0;
        
        self::track_event($event_type, $user_id, [
            'lesson_id' => $lesson_id,
            'lesson_title' => get_the_title($lesson_id),
            'module_id' => $module_id,
            'course_id' => $course_id,
            'completed' => $completed
        ]);
        
        // Check for progress milestones (25%, 50%, 75%, 100%)
        if ($completed && $course_id > 0) {
            $progress = Progress_Tracker::getCourseProgress($user_id, $course_id);
            $milestones = [25, 50, 75, 100];
            
            foreach ($milestones as $milestone) {
                if ($progress >= $milestone && !self::has_milestone_been_tracked($user_id, $course_id, $milestone)) {
                    self::track_event(self::EVENT_COURSE_PROGRESS_MILESTONE, $user_id, [
                        'course_id' => $course_id,
                        'milestone' => $milestone,
                        'progress' => $progress
                    ]);
                    self::mark_milestone_tracked($user_id, $course_id, $milestone);
                }
            }
        }
    }
    
    /**
     * Get user analytics data
     * 
     * @param int $user_id User ID
     * @param string|null $event_type Optional event type filter
     * @param int $limit Number of events to retrieve
     * @return array Array of events
     */
    public static function get_user_analytics_data(int $user_id, ?string $event_type = null, int $limit = 100): array {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        // Check if table exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
            return [];
        }
        
        $where_clauses = ['user_id = %d'];
        $where_values = [$user_id];
        
        if ($event_type !== null) {
            $where_clauses[] = 'event_type = %s';
            $where_values[] = $event_type;
        }
        
        $where_values[] = $limit;
        
        $query = $wpdb->prepare(
            "SELECT * FROM `{$table_name}` WHERE " . implode(' AND ', $where_clauses) . " ORDER BY created_at DESC LIMIT %d",
            ...$where_values
        );
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Decode JSON data
        foreach ($results as &$row) {
            $row['event_data'] = json_decode($row['event_data'], true);
        }
        
        return $results;
    }
    
    /**
     * Get course analytics summary
     * 
     * @param int $course_id Course ID
     * @return array Analytics summary
     */
    public static function get_course_analytics(int $course_id): array {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
            return ['total_events' => 0];
        }
        
        $data = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                event_type,
                COUNT(*) as event_count,
                COUNT(DISTINCT user_id) as unique_users
            FROM `{$table_name}`
            WHERE JSON_EXTRACT(event_data, '$.course_id') = %d
            GROUP BY event_type",
            $course_id
        ), ARRAY_A);
        
        $summary = [
            'course_id' => $course_id,
            'total_events' => 0,
            'unique_users' => 0,
            'events_by_type' => []
        ];
        
        foreach ($data as $row) {
            $summary['events_by_type'][$row['event_type']] = [
                'count' => (int) $row['event_count'],
                'unique_users' => (int) $row['unique_users']
            ];
            $summary['total_events'] += (int) $row['event_count'];
            $summary['unique_users'] = max($summary['unique_users'], (int) $row['unique_users']);
        }
        
        return $summary;
    }
    
    /**
     * Create analytics table if needed
     * 
     * @return void
     */
    public static function maybeCreateAnalyticsTable(): void {
        // Only create if analytics is enabled
        $enabled = (bool) get_option('simple_lms_analytics_enabled', false);
        if (!$enabled) {
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        // Check if table exists
        $existing_table = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
        if ($existing_table === $table_name) {
            return;
        }
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            user_id bigint(20) NOT NULL,
            event_data longtext DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY created_at (created_at),
            KEY user_event (user_id, event_type)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        update_option('simple_lms_analytics_db_version', '1.0');
        self::log('info', 'Analytics table created');
    }
    
    /**
     * Check if milestone has been tracked
     * 
     * @param int $user_id User ID
     * @param int $course_id Course ID
     * @param int $milestone Milestone percentage
     * @return bool
     */
    private static function has_milestone_been_tracked(int $user_id, int $course_id, int $milestone): bool {
        $key = "simple_lms_milestone_{$course_id}_{$milestone}";
        $tracked = get_user_meta($user_id, $key, true);
        return (bool) $tracked;
    }
    
    /**
     * Mark milestone as tracked
     * 
     * @param int $user_id User ID
     * @param int $course_id Course ID
     * @param int $milestone Milestone percentage
     * @return void
     */
    private static function mark_milestone_tracked(int $user_id, int $course_id, int $milestone): void {
        $key = "simple_lms_milestone_{$course_id}_{$milestone}";
        update_user_meta($user_id, $key, 1);
    }
    
    /**
     * Get user IP address
     * 
     * @return string IP address
     */
    private static function get_user_ip(): string {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return sanitize_text_field($ip);
    }
    
    /**
     * Get user agent string
     * 
     * @return string User agent
     */
    private static function get_user_agent(): string {
        return !empty($_SERVER['HTTP_USER_AGENT']) 
            ? substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']), 0, 255)
            : '';
    }
    
    /**
     * Send event to Google Analytics 4 (if configured)
     * 
     * @param string $event_type Event type
     * @param int $user_id User ID
     * @param array $data Event data
     * @return bool Success status
     */
    public static function send_to_ga4(string $event_type, int $user_id, array $data): bool {
        $measurement_id = (string) get_option('simple_lms_ga4_measurement_id', '');
        $api_secret = (string) get_option('simple_lms_ga4_api_secret', '');
        
        if (empty($measurement_id) || empty($api_secret)) {
            return false;
        }
        
        $url = "https://www.google-analytics.com/mp/collect?measurement_id={$measurement_id}&api_secret={$api_secret}";
        
        $payload = [
            'client_id' => (string) $user_id,
            'events' => [
                [
                    'name' => str_replace('_', '.', $event_type),
                    'params' => $data
                ]
            ]
        ];
        
        $response = wp_remote_post($url, [
            'body' => json_encode($payload),
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'timeout' => 5
        ]);
        
        $ok = !is_wp_error($response);
        if (!$ok) {
            self::log('warning', 'GA4 send failed', ['event' => $event_type, 'userId' => $user_id]);
        }
        return $ok;
    }

    /**
     * Static logging helper
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        try {
            $container = ServiceContainer::getInstance();
            if ($container->has(Logger::class)) {
                /** @var Logger $logger */
                $logger = $container->get(Logger::class);
                if (method_exists($logger, $level)) {
                    $logger->{$level}($message, $context);
                } else {
                    $logger->info($message, $context);
                }
            }
        } catch (\Throwable $e) {
            // silent
        }
    }
}

// Hook to send events to GA4 if configured
add_action('simple_lms_analytics_event', function($event_type, $user_id, $data) {
    $ga4_enabled = (bool) get_option('simple_lms_ga4_enabled', false);
    if ($ga4_enabled) {
        \SimpleLMS\Analytics_Tracker::send_to_ga4($event_type, $user_id, $data);
    }
}, 10, 3);
