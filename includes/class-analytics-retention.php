<?php
namespace SimpleLMS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Analytics data retention and cleanup
 */
class Analytics_Retention {

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
     * Initialize retention system (backward compatibility shim)
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
     * Instance registration of hooks
     */
    public function register(): void
    {
        \add_action('wp', [__CLASS__, 'setup_cleanup_cron']);
        \add_action('simple_lms_cleanup_old_analytics', [__CLASS__, 'cleanup_old_analytics']);
        if ($this->logger) {
            $this->logger->debug('Analytics_Retention hooks registered');
        }
    }

    /**
     * Legacy init wrapper (deprecated, kept for compatibility)
     */
    private static function legacyInit() {
        \add_action('wp', [__CLASS__, 'setup_cleanup_cron']);
        \add_action('simple_lms_cleanup_old_analytics', [__CLASS__, 'cleanup_old_analytics']);
    }

    /**
     * Setup cron for cleaning old analytics data
     */
    public static function setup_cleanup_cron() {
        if (!\wp_next_scheduled('simple_lms_cleanup_old_analytics')) {
            \wp_schedule_event(\time(), 'daily', 'simple_lms_cleanup_old_analytics');
        }
    }

    /**
     * Cleanup old analytics events based on retention setting
     */
    public static function cleanup_old_analytics() {
        global $wpdb;

        $retention_days = \get_option('simple_lms_analytics_retention_days', 365);

        // -1 means unlimited retention
        if ($retention_days === -1 || $retention_days < 1) {
            return;
        }

        $table_name = $wpdb->prefix . 'simple_lms_analytics';

        // Check if table exists
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return;
        }

        // Calculate cutoff date
        $cutoff_date = \gmdate('Y-m-d H:i:s', \strtotime("-{$retention_days} days"));

        // Delete old records
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE event_time < %s",
                $cutoff_date
            )
        );

        if ($deleted > 0) {
            self::log('info', 'Cleaned up old analytics events', [
                'deleted' => $deleted,
                'retentionDays' => $retention_days,
                'cutoffDate' => $cutoff_date
            ]);

            // Clear any related transients
            Cache_Handler::clear_analytics_cache();
        }
    }

    /**
     * Cleanup cron on plugin deactivation
     */
    public static function deactivate_cleanup_cron() {
        $timestamp = \wp_next_scheduled('simple_lms_cleanup_old_analytics');
        if ($timestamp) {
            \wp_unschedule_event($timestamp, 'simple_lms_cleanup_old_analytics');
        }
    }

    /**
     * Get analytics retention status for admin display
     *
     * @return array Status information
     */
    public static function get_retention_status() {
        global $wpdb;

        $retention_days = \get_option('simple_lms_analytics_retention_days', 365);
        $table_name = $wpdb->prefix . 'simple_lms_analytics';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return [
                'enabled' => false,
                'message' => \__('Analytics table does not exist', 'simple-lms'),
            ];
        }

        if ($retention_days === -1) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $total_events = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");

            return [
                'enabled' => true,
                'retention_days' => -1,
                'total_events' => (int) $total_events,
                'message' => \sprintf(
                    \__('Unlimited retention: %d events stored', 'simple-lms'),
                    $total_events
                ),
            ];
        }

        $cutoff_date = \gmdate('Y-m-d H:i:s', \strtotime("-{$retention_days} days"));

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $total_events = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $old_events = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE event_time < %s",
                $cutoff_date
            )
        );

        return [
            'enabled' => true,
            'retention_days' => $retention_days,
            'total_events' => (int) $total_events,
            'old_events' => (int) $old_events,
            'cutoff_date' => $cutoff_date,
            'message' => \sprintf(
                \__('Keeping %d days: %d events (%d will be deleted on next cleanup)', 'simple-lms'),
                $retention_days,
                $total_events,
                $old_events
            )
        ];
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