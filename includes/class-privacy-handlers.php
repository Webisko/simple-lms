<?php
namespace SimpleLMS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * GDPR Privacy Compliance Handlers
 * 
 * Implements WordPress privacy tools for personal data export and erasure
 */
class Privacy_Handlers {

    /** @var Logger|null */
    private ?Logger $logger = null;

    /**
     * Constructor with optional Logger
     */
    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Instance registration of privacy hooks
     */
    public function register(): void
    {
        \add_filter('wp_privacy_personal_data_exporters', [__CLASS__, 'register_exporters']);
        \add_filter('wp_privacy_personal_data_erasers', [__CLASS__, 'register_erasers']);
        \add_action('wp_privacy_personal_data_export_file', [__CLASS__, 'export_user_data']);
        if ($this->logger) {
            $this->logger->debug('Privacy_Handlers hooks registered');
        }
    }

    /**
     * Initialize privacy handlers (backward compatibility shim)
     */
    public static function init() {
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
        (new self())->register();
    }

    /**
     * Legacy init (kept for compat, not needed after register())
     */
    private static function legacyInit() {
        \add_filter('wp_privacy_personal_data_exporters', [__CLASS__, 'register_exporters']);
        \add_filter('wp_privacy_personal_data_erasers', [__CLASS__, 'register_erasers']);
        \add_action('wp_privacy_personal_data_export_file', [__CLASS__, 'export_user_data']);
    }

    /**
     * Register personal data exporters
     * 
     * @param array $exporters Existing exporters
     * @return array Modified exporters
     */
    public static function register_exporters($exporters) {
        $exporters['simple-lms-progress'] = [
            'exporter_friendly_name' => \__('Simple LMS - Course Progress', 'simple-lms'),
            'callback' => [__CLASS__, 'export_progress_data'],
        ];

        $exporters['simple-lms-analytics'] = [
            'exporter_friendly_name' => \__('Simple LMS - Analytics Events', 'simple-lms'),
            'callback' => [__CLASS__, 'export_analytics_data'],
        ];

        return $exporters;
    }

    /**
     * Register personal data erasers
     * 
     * @param array $erasers Existing erasers
     * @return array Modified erasers
     */
    public static function register_erasers($erasers) {
        $erasers['simple-lms-progress'] = [
            'eraser_friendly_name' => \__('Simple LMS - Course Progress', 'simple-lms'),
            'callback' => [__CLASS__, 'erase_progress_data'],
        ];

        $erasers['simple-lms-analytics'] = [
            'eraser_friendly_name' => \__('Simple LMS - Analytics Events', 'simple-lms'),
            'callback' => [__CLASS__, 'erase_analytics_data'],
        ];

        return $erasers;
    }

    /**
     * Export user progress data
     * 
     * @param string $email_address User email
     * @param int $page Page number
     * @return array Export data
     */
    public static function export_progress_data($email_address, $page = 1) {
        $export_items = [];
        $user = \get_user_by('email', $email_address);

        if (!$user) {
            return [
                'data' => [],
                'done' => true,
            ];
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'simple_lms_progress';

        // Check if table exists
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return [
                'data' => [],
                'done' => true,
            ];
        }

        $number = 100; // Process 100 items per page
        $offset = ($page - 1) * $number;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $progress_records = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE user_id = %d LIMIT %d OFFSET %d",
                $user->ID,
                $number,
                $offset
            )
        );

        foreach ($progress_records as $record) {
            $course_title = \get_the_title($record->course_id);
            $lesson_title = \get_the_title($record->lesson_id);

            $item_id = "simple-lms-progress-{$record->id}";

            $data = [
                [
                    'name' => \__('Course', 'simple-lms'),
                    'value' => $course_title ? $course_title : $record->course_id,
                ],
                [
                    'name' => \__('Lesson', 'simple-lms'),
                    'value' => $lesson_title ? $lesson_title : $record->lesson_id,
                ],
                [
                    'name' => \__('Completion Status', 'simple-lms'),
                    'value' => $record->completed ? \__('Completed', 'simple-lms') : \__('In Progress', 'simple-lms'),
                ],
                [
                    'name' => \__('Started At', 'simple-lms'),
                    'value' => $record->started_at,
                ],
            ];

            if ($record->completed_at) {
                $data[] = [
                    'name' => \__('Completed At', 'simple-lms'),
                    'value' => $record->completed_at,
                ];
            }

            $export_items[] = [
                'group_id' => 'simple-lms-progress',
                'group_label' => \__('Simple LMS - Course Progress', 'simple-lms'),
                'item_id' => $item_id,
                'data' => $data,
            ];
        }

        $done = count($progress_records) < $number;

        return [
            'data' => $export_items,
            'done' => $done,
        ];
    }

    /**
     * Export user analytics data
     * 
     * @param string $email_address User email
     * @param int $page Page number
     * @return array Export data
     */
    public static function export_analytics_data($email_address, $page = 1) {
        $export_items = [];
        $user = \get_user_by('email', $email_address);

        if (!$user) {
            return [
                'data' => [],
                'done' => true,
            ];
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'simple_lms_analytics';

        // Check if table exists
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return [
                'data' => [],
                'done' => true,
            ];
        }

        $number = 100; // Process 100 items per page
        $offset = ($page - 1) * $number;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $analytics_records = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY event_time DESC LIMIT %d OFFSET %d",
                $user->ID,
                $number,
                $offset
            )
        );

        foreach ($analytics_records as $record) {
            $item_id = "simple-lms-analytics-{$record->id}";

            $data = [
                [
                    'name' => \__('Event Type', 'simple-lms'),
                    'value' => $record->event_type,
                ],
                [
                    'name' => \__('Event Time', 'simple-lms'),
                    'value' => $record->event_time,
                ],
            ];

            if ($record->course_id) {
                $course_title = \get_the_title($record->course_id);
                $data[] = [
                    'name' => \__('Course', 'simple-lms'),
                    'value' => $course_title ? $course_title : $record->course_id,
                ];
            }

            if ($record->lesson_id) {
                $lesson_title = \get_the_title($record->lesson_id);
                $data[] = [
                    'name' => \__('Lesson', 'simple-lms'),
                    'value' => $lesson_title ? $lesson_title : $record->lesson_id,
                ];
            }

            if ($record->event_data) {
                $data[] = [
                    'name' => \__('Additional Data', 'simple-lms'),
                    'value' => $record->event_data,
                ];
            }

            $export_items[] = [
                'group_id' => 'simple-lms-analytics',
                'group_label' => \__('Simple LMS - Analytics Events', 'simple-lms'),
                'item_id' => $item_id,
                'data' => $data,
            ];
        }

        $done = count($analytics_records) < $number;

        return [
            'data' => $export_items,
            'done' => $done,
        ];
    }

    /**
     * Erase user progress data
     * 
     * @param string $email_address User email
     * @param int $page Page number
     * @return array Erasure response
     */
    public static function erase_progress_data($email_address, $page = 1) {
        $user = \get_user_by('email', $email_address);

        if (!$user) {
            return [
                'items_removed' => false,
                'items_retained' => false,
                'messages' => [],
                'done' => true,
            ];
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'simple_lms_progress';

        // Check if table exists
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return [
                'items_removed' => false,
                'items_retained' => false,
                'messages' => [],
                'done' => true,
            ];
        }

        // Delete user progress records
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $deleted = $wpdb->delete(
            $table_name,
            ['user_id' => $user->ID],
            ['%d']
        );

        // Delete user meta
        \delete_user_meta($user->ID, 'simple_lms_course_access');
        \delete_user_meta($user->ID, 'simple_lms_course_access_expiration');

        $messages = [];
        if ($deleted > 0) {
            $messages[] = \sprintf(
                \__('Removed %d progress records', 'simple-lms'),
                $deleted
            );
        }

        return [
            'items_removed' => $deleted > 0,
            'items_retained' => false,
            'messages' => $messages,
            'done' => true,
        ];
    }

    /**
     * Erase user analytics data
     * 
     * @param string $email_address User email
     * @param int $page Page number
     * @return array Erasure response
     */
    public static function erase_analytics_data($email_address, $page = 1) {
        $user = \get_user_by('email', $email_address);

        if (!$user) {
            return [
                'items_removed' => false,
                'items_retained' => false,
                'messages' => [],
                'done' => true,
            ];
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'simple_lms_analytics';

        // Check if table exists
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return [
                'items_removed' => false,
                'items_retained' => false,
                'messages' => [],
                'done' => true,
            ];
        }

        // Delete analytics events for this user
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $deleted = $wpdb->delete(
            $table_name,
            ['user_id' => $user->ID],
            ['%d']
        );

        $messages = [];
        if ($deleted > 0) {
            $messages[] = \sprintf(
                \__('Removed %d analytics events', 'simple-lms'),
                $deleted
            );
            
            // Clear analytics cache
            Cache_Handler::clear_analytics_cache();
        }

        return [
            'items_removed' => $deleted > 0,
            'items_retained' => false,
            'messages' => $messages,
            'done' => true,
        ];
    }

    /**
     * Hook to export user data file creation
     * 
     * @param string $archive_pathname Path to export archive
     */
    public static function export_user_data($archive_pathname) {
        self::log('info', 'Personal data export created', ['path' => $archive_pathname]);
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

