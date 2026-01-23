<?php
declare(strict_types=1);

namespace SimpleLMS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WP-CLI commands for Simple LMS.
 */
final class CLI
{
    public static function register(): void
    {
        if (!defined('WP_CLI') || !WP_CLI) {
            return;
        }

        \WP_CLI::add_command('simple-lms cleanup-legacy-access', [self::class, 'cleanupLegacyAccess']);
    }

    /**
     * Cleanup legacy access user meta.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Only show how many records would be deleted.
     *
     * [--yes]
     * : Skip confirmation prompt.
     *
     * ## EXAMPLES
     *
     *     wp simple-lms cleanup-legacy-access --dry-run
     *     wp simple-lms cleanup-legacy-access --yes
     *
     * @when after_wp_load
     */
    public static function cleanupLegacyAccess(array $args, array $assoc_args): void
    {
        global $wpdb;

        $dryRun = isset($assoc_args['dry-run']);
        $skipConfirm = isset($assoc_args['yes']);

        $legacyMetaKey = 'enrolled_courses';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s",
                $legacyMetaKey
            )
        );

        if ($count <= 0) {
            \WP_CLI::success('No legacy access records found (enrolled_courses).');
            return;
        }

        \WP_CLI::log(sprintf('Found %d legacy access record(s) with meta_key=%s.', $count, $legacyMetaKey));

        if ($dryRun) {
            \WP_CLI::success('Dry-run mode: nothing was deleted.');
            return;
        }

        if (!$skipConfirm) {
            \WP_CLI::confirm('Delete these legacy access records?');
        }

        // Delete in one query for speed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $deleted = (int) $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
                $legacyMetaKey
            )
        );

        \WP_CLI::success(sprintf('Deleted %d record(s).', $deleted));
    }
}
