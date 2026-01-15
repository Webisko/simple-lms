<?php
/**
 * Progress tracking system for Simple LMS
 * 
 * @package SimpleLMS
 * @since 1.1.0
 */

declare(strict_types=1);

namespace SimpleLMS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Progress tracking handler class
 * 
 * Manages user progress through courses, modules, and lessons
 */
class Progress_Tracker {
    /**
     * Logger instance
     * @var Logger|null
     */
    private ?Logger $logger;

    /**
     * Constructor (DI)
     * @param Logger|null $logger
     */
    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger;
    }
    
    /**
     * Database table name for progress tracking
     */
    private const TABLE_NAME = 'simple_lms_progress';
    
    /**
     * Initialize progress tracking
     * 
     * @return void
     */
    public static function init(): void {
        // Backward compatibility static init delegates to instance
        try {
            $container = ServiceContainer::getInstance();
            /** @var Progress_Tracker $instance */
            $instance = $container->get(Progress_Tracker::class);
            $instance->register();
        } catch (\Throwable $e) {
            if (function_exists('error_log')) {
                error_log('Simple LMS Progress_Tracker init failure: ' . $e->getMessage());
            }
        }
    }

    /**
     * Register hooks (instance)
     */
    public function register(): void
    {
        add_action('init', [$this, 'createProgressTable']);
        add_action('wp_ajax_mark_lesson_complete', [$this, 'handleMarkLessonComplete']);
        add_action('wp_ajax_nopriv_mark_lesson_complete', [$this, 'handleMarkLessonComplete']);
        add_action('wp_ajax_get_user_progress', [$this, 'handleGetUserProgress']);
        add_action('wp_ajax_nopriv_get_user_progress', [$this, 'handleGetUserProgress']);

        add_filter('manage_users_columns', [$this, 'addProgressColumn']);
        add_action('manage_users_custom_column', [$this, 'displayProgressColumn'], 10, 3);
        add_action('show_user_profile', [$this, 'addUserProgressMetaBox']);
        add_action('edit_user_profile', [$this, 'addUserProgressMetaBox']);
    }
    
    /**
     * Create progress tracking table
     * 
     * @return void
     */
    public function createProgressTable(): void {
        global $wpdb;
        
        $tableName = $wpdb->prefix . self::TABLE_NAME;
        
        // Check if table exists (prepared statement for security)
        $existingTable = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tableName));
        if ($existingTable === $tableName) {
            return;
        }
        
        $charset = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE `{$tableName}` (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            lesson_id bigint(20) NOT NULL,
            course_id bigint(20) NOT NULL,
            module_id bigint(20) NOT NULL,
            completed tinyint(1) DEFAULT 0,
            completion_date datetime DEFAULT NULL,
            time_spent int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_lesson (user_id, lesson_id),
            KEY user_course (user_id, course_id),
            KEY user_module (user_id, module_id),
            KEY completion_date (completion_date),
            KEY updated_at (updated_at),
            KEY user_lesson_completed (user_id, lesson_id, completed),
            KEY course_stats (course_id, completed, user_id),
            KEY user_course_updated (user_id, course_id, updated_at)
        ) {$charset};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Check and upgrade schema if needed
        $currentVersion = get_option('simple_lms_progress_db_version', '0');
        if (version_compare($currentVersion, '1.3', '<')) {
            self::upgradeSchema();
        }
        
        // Add version option to track schema changes
        update_option('simple_lms_progress_db_version', '1.3');
    }
    
    /**
     * Upgrade database schema (add new indexes for better performance)
     * 
     * @return void
     */
    private static function upgradeSchema(): void {
        global $wpdb;
        
        $tableName = $wpdb->prefix . self::TABLE_NAME;
        $tableNameSafe = esc_sql($tableName);
        
        // Check if indexes exist before adding (to avoid errors)
        $indexes = $wpdb->get_results("SHOW INDEX FROM `{$tableNameSafe}`", ARRAY_A);
        $existingIndexes = array_column($indexes, 'Key_name');
        
        // Add composite index for user+lesson+completed queries
        if (!in_array('user_lesson_completed', $existingIndexes)) {
            $wpdb->query("ALTER TABLE `{$tableNameSafe}` ADD INDEX user_lesson_completed (user_id, lesson_id, completed)");
        }
        
        // Add composite index for course stats
        if (!in_array('course_stats', $existingIndexes)) {
            $wpdb->query("ALTER TABLE `{$tableNameSafe}` ADD INDEX course_stats (course_id, completed, user_id)");
        }

        // Ensure index for user+course exists (older versions might miss it)
        if (!in_array('user_course', $existingIndexes)) {
            $wpdb->query("ALTER TABLE `{$tableNameSafe}` ADD INDEX user_course (user_id, course_id)");
        }

        // Add index for updated_at to speed up ORDER BY and MAX(updated_at)
        if (!in_array('updated_at', $existingIndexes)) {
            $wpdb->query("ALTER TABLE `{$tableNameSafe}` ADD INDEX updated_at (updated_at)");
        }

        // Composite index for fast last-lesson lookups per user+course
        if (!in_array('user_course_updated', $existingIndexes)) {
            $wpdb->query("ALTER TABLE `{$tableNameSafe}` ADD INDEX user_course_updated (user_id, course_id, updated_at)");
        }
    }
    
    /**
     * Update lesson progress for user
     * 
     * @param int $userId User ID
     * @param int $lessonId Lesson ID
     * @param bool $completed Whether lesson is completed
     * @param int $timeSpent Time spent in seconds
     * @return bool Success status
     */
    public static function updateLessonProgress(int $userId, int $lessonId, bool $completed = true, int $timeSpent = 0): bool {
        global $wpdb;
        
        try {
            // Get lesson details
            $moduleId = (int) get_post_meta($lessonId, 'parent_module', true);
            $courseId = (int) get_post_meta($moduleId, 'parent_course', true);
            
            if (!$moduleId || !$courseId) {
                throw new \InvalidArgumentException(__('Invalid lesson structure', 'simple-lms'));
            }
            
            $tableName = $wpdb->prefix . self::TABLE_NAME;
            $now = current_time('mysql');
            
            // Check if record exists (only fetch needed columns)
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id, completed, time_spent FROM {$tableName} WHERE user_id = %d AND lesson_id = %d",
                $userId,
                $lessonId
            ));
            
            if ($existing) {
                // Update existing record
                $result = $wpdb->update(
                    $tableName,
                    [
                        'completed' => $completed ? 1 : 0,
                        'completion_date' => $completed ? $now : null,
                        'time_spent' => max($existing->time_spent, $timeSpent),
                        'updated_at' => $now
                    ],
                    ['user_id' => $userId, 'lesson_id' => $lessonId],
                    ['%d', '%s', '%d', '%s'],
                    ['%d', '%d']
                );
            } else {
                // Insert new record
                $result = $wpdb->insert(
                    $tableName,
                    [
                        'user_id' => $userId,
                        'lesson_id' => $lessonId,
                        'course_id' => $courseId,
                        'module_id' => $moduleId,
                        'completed' => $completed ? 1 : 0,
                        'completion_date' => $completed ? $now : null,
                        'time_spent' => $timeSpent,
                        'created_at' => $now
                    ],
                    ['%d', '%d', '%d', '%d', '%d', '%s', '%d', '%s']
                );
            }
            
            if ($result !== false) {
                // Clear progress cache (pass courseId to avoid extra query)
                self::clearProgressCache($userId, $courseId);
                
                // Trigger action for other plugins
                do_action('simple_lms_lesson_progress_updated', $userId, $lessonId, $completed);
                
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            error_log('Simple LMS Progress Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user progress for specific course
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID (optional)
     * @return array Progress data
     */
    public static function getUserProgress(int $userId, int $courseId = 0): array {
        global $wpdb;
        
        $cacheKey = "simple_lms_progress_{$userId}_{$courseId}";
        $cached = wp_cache_get($cacheKey, \SimpleLMS\Cache_Handler::CACHE_GROUP);
        
        if ($cached !== false) {
            return $cached;
        }
        
        try {
            $tableName = $wpdb->prefix . self::TABLE_NAME;
            
            $where = "WHERE user_id = %d";
            $params = [$userId];
            
            if ($courseId > 0) {
                $where .= " AND course_id = %d";
                $params[] = $courseId;
            }
            
            // Get overall progress
            $progress = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    course_id,
                    COUNT(*) as total_lessons,
                    SUM(completed) as completed_lessons,
                    AVG(completed) * 100 as completion_percentage,
                    SUM(time_spent) as total_time_spent,
                    MAX(updated_at) as last_activity
                FROM {$tableName} 
                {$where}
                GROUP BY course_id",
                ...$params
            ), ARRAY_A);
            
            // Get detailed lesson progress
            $lessons = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    lesson_id,
                    course_id,
                    module_id,
                    completed,
                    completion_date,
                    time_spent
                FROM {$tableName} 
                {$where}
                ORDER BY course_id, module_id, lesson_id",
                ...$params
            ), ARRAY_A);
            
            $result = [
                'user_id' => $userId,
                'overall_progress' => $progress,
                'lessons' => $lessons,
                'summary' => self::calculateProgressSummary($progress)
            ];
            
            // Cache for configurable TTL (default 5 minutes)
            $ttl = (int) apply_filters('simple_lms_progress_cache_ttl', 300, $userId, $courseId);
            wp_cache_set($cacheKey, $result, \SimpleLMS\Cache_Handler::CACHE_GROUP, $ttl);
            
            return $result;
            
        } catch (\Exception $e) {
            error_log('Simple LMS Progress Error (getUserProgress): ' . $e->getMessage());
            return ['user_id' => $userId, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get course completion statistics
     * 
     * @param int $courseId Course ID
     * @return array Course statistics
     */
    public static function getCourseStats(int $courseId): array {
        global $wpdb;
        
        $cacheKey = "simple_lms_course_stats_{$courseId}";
        $cached = wp_cache_get($cacheKey, \SimpleLMS\Cache_Handler::CACHE_GROUP);
        
        if ($cached !== false) {
            return $cached;
        }
        
        try {
            $tableName = $wpdb->prefix . self::TABLE_NAME;
            
            $stats = $wpdb->get_row($wpdb->prepare(
                "SELECT 
                    COUNT(DISTINCT user_id) as enrolled_users,
                    COUNT(DISTINCT CASE WHEN completed = 1 THEN user_id END) as users_with_progress,
                    AVG(completed) * 100 as avg_completion_rate,
                    SUM(time_spent) as total_time_spent
                FROM {$tableName} 
                WHERE course_id = %d",
                $courseId
            ), ARRAY_A);
            
            // Get completion breakdown by module
            $moduleStats = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    module_id,
                    COUNT(*) as total_lessons,
                    SUM(completed) as completed_lessons,
                    AVG(completed) * 100 as completion_percentage
                FROM {$tableName} 
                WHERE course_id = %d
                GROUP BY module_id",
                $courseId
            ), ARRAY_A);
            
            $result = [
                'course_id' => $courseId,
                'overall_stats' => $stats ?: [],
                'module_stats' => $moduleStats
            ];
            
            // Cache for configurable TTL (default 10 minutes)
            $ttl = (int) apply_filters('simple_lms_course_stats_cache_ttl', 600, $courseId);
            wp_cache_set($cacheKey, $result, \SimpleLMS\Cache_Handler::CACHE_GROUP, $ttl);
            
            return $result;
            
        } catch (\Exception $e) {
            error_log('Simple LMS Course Stats Error: ' . $e->getMessage());
            return ['course_id' => $courseId, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Check if user has completed a lesson
     * 
     * @param int $userId User ID
     * @param int $lessonId Lesson ID
     * @return bool Completion status
     */
    public static function isLessonCompleted(int $userId, int $lessonId): bool {
        global $wpdb;
        
        $tableName = $wpdb->prefix . self::TABLE_NAME;
        
        $completed = $wpdb->get_var($wpdb->prepare(
            "SELECT completed FROM {$tableName} WHERE user_id = %d AND lesson_id = %d",
            $userId,
            $lessonId
        ));
        
        return (bool) $completed;
    }

    /**
     * Get number of completed lessons in a course for a user
     *
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return int Completed lessons count
     */
    public static function getCompletedLessonsCount(int $userId, int $courseId): int {
        global $wpdb;
        $tableName = $wpdb->prefix . self::TABLE_NAME;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(completed),0) FROM {$tableName} WHERE user_id = %d AND course_id = %d",
            $userId,
            $courseId
        ));
        return (int) $count;
    }

    /**
     * Get course progress percentage (0-100)
     *
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return int Percentage completed (rounded)
     */
    public static function getCourseProgress(int $userId, int $courseId): int {
        // Reuse existing implementation for percentage
        $percentage = self::getCourseCompletionPercentage($userId, $courseId);
        return (int) round($percentage);
    }

    /**
     * Get last viewed/updated lesson for user in a course
     *
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return int Lesson ID or 0 if none
     */
    public static function getLastViewedLesson(int $userId, int $courseId): int {
        global $wpdb;
        $tableName = $wpdb->prefix . self::TABLE_NAME;
        $lessonId = $wpdb->get_var($wpdb->prepare(
            "SELECT lesson_id FROM {$tableName} WHERE user_id = %d AND course_id = %d ORDER BY updated_at DESC LIMIT 1",
            $userId,
            $courseId
        ));
        return (int) ($lessonId ?: 0);
    }
    
    /**
     * Get course completion percentage for user
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return float Completion percentage (0-100)
     */
    public static function getCourseCompletionPercentage(int $userId, int $courseId): float {
        global $wpdb;
        
        $tableName = $wpdb->prefix . self::TABLE_NAME;
        
        $percentage = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(completed) * 100 
            FROM {$tableName} 
            WHERE user_id = %d AND course_id = %d",
            $userId,
            $courseId
        ));
        
        return (float) ($percentage ?: 0);
    }
    
    /**
     * Handle AJAX request to mark lesson as complete
     * 
     * @return void
     */
    public function handleMarkLessonComplete(): void {
        try {
            check_ajax_referer('simple-lms-progress', 'nonce');
            
            $userId = get_current_user_id();
            if (!$userId) {
                throw new \Exception(__('You must be logged in', 'simple-lms'));
            }
            
            $lessonId = (int) ($_POST['lesson_id'] ?? 0);
            $completed = isset($_POST['completed']) ? (bool) $_POST['completed'] : true;
            $timeSpent = (int) ($_POST['time_spent'] ?? 0);
            
            if (!$lessonId) {
                throw new \Exception(__('Invalid lesson ID', 'simple-lms'));
            }
            
            // Check if user has access to this lesson
            if (!self::userCanAccessLesson($userId, $lessonId)) {
                throw new \Exception(__('No access to this lesson', 'simple-lms'));
            }
            
            $result = self::updateLessonProgress($userId, $lessonId, $completed, $timeSpent);
            
            if ($result) {
                wp_send_json_success([
                    'message' => $completed ? __('Lesson marked as completed', 'simple-lms') : __('Progress saved', 'simple-lms'),
                    'completed' => $completed
                ]);
            } else {
                throw new \Exception(__('Failed to save progress', 'simple-lms'));
            }
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle AJAX request to get user progress
     * 
     * @return void
     */
    public function handleGetUserProgress(): void {
        try {
            check_ajax_referer('simple-lms-progress', 'nonce');
            
            $userId = get_current_user_id();
            if (!$userId) {
                throw new \Exception(__('You must be logged in', 'simple-lms'));
            }
            
            $courseId = (int) ($_POST['course_id'] ?? 0);
            $progress = self::getUserProgress($userId, $courseId);
            
            wp_send_json_success($progress);
            
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Add progress column to users table
     * 
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function addProgressColumn(array $columns): array {
        $columns['lms_progress'] = __('LMS Progress', 'simple-lms');
        return $columns;
    }
    
    /**
     * Display progress in users table
     * 
     * @param string $value Column value
     * @param string $columnName Column name
     * @param int $userId User ID
     * @return string Column content
     */
    public function displayProgressColumn(string $value, string $columnName, int $userId): string {
        if ($columnName === 'lms_progress') {
            $progress = self::getUserProgress($userId);
            
            if (!empty($progress['summary'])) {
                $summary = $progress['summary'];
                return sprintf(
                    '%d kursów (%.1f%% Mediumo)',
                    $summary['total_courses'],
                    $summary['avg_completion']
                );
            }
            
            return __('No activity', 'simple-lms');
        }
        
        return $value;
    }
    
    /**
     * Add progress meta box to user profile
     * 
     * @param \WP_User $user User object
     * @return void
     */
    public function addUserProgressMetaBox(\WP_User $user): void {
        $progress = self::getUserProgress($user->ID);
        
        echo '<h3>' . __('LMS Course Progress', 'simple-lms') . '</h3>';
        echo '<table class="form-table">';
        
        if (!empty($progress['overall_progress'])) {
            foreach ($progress['overall_progress'] as $courseProgress) {
                $courseTitle = get_the_title($courseProgress['course_id']);
                echo '<tr>';
                echo '<th>' . esc_html($courseTitle) . '</th>';
                echo '<td>';
                echo sprintf(
                    __('%d/%d lessons completed (%.1f%%)', 'simple-lms'),
                    $courseProgress['completed_lessons'],
                    $courseProgress['total_lessons'],
                    $courseProgress['completion_percentage']
                );
                echo '<br><small>' . sprintf(__('Total time: %s', 'simple-lms'), self::formatTime($courseProgress['total_time_spent'])) . '</small>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="2">' . __('No saved progress', 'simple-lms') . '</td></tr>';
        }
        
        echo '</table>';
    }
    
    /**
     * Check if user can access lesson
     * 
     * @param int $userId User ID
     * @param int $lessonId Lesson ID
     * @return bool Access permission
     */
    private static function userCanAccessLesson(int $userId, int $lessonId): bool {
        $moduleId = (int) get_post_meta($lessonId, 'parent_module', true);
        $courseId = (int) get_post_meta($moduleId, 'parent_course', true);
        
        if (!$courseId) {
            return false;
        }
        // Admins / editors have universal access
        if (user_can($userId, 'edit_posts')) {
            return true;
        }
        // Tag-based access: user must have course ID stored in user_meta array
        $access = (array) get_user_meta($userId, 'simple_lms_course_access', true);
        return in_array($courseId, $access, true);
    }
    
    /**
     * Calculate progress summary
     * 
     * @param array $progress Progress data
     * @return array Summary statistics
     */
    private static function calculateProgressSummary(array $progress): array {
        if (empty($progress)) {
            return ['total_courses' => 0, 'avg_completion' => 0];
        }
        
        $totalCourses = count($progress);
        $avgCompletion = array_sum(array_column($progress, 'completion_percentage')) / $totalCourses;
        
        return [
            'total_courses' => $totalCourses,
            'avg_completion' => round($avgCompletion, 1)
        ];
    }
    
    /**
     * Clear progress cache for user
     * 
     * @param int $userId User ID
     * @param int|null $courseId Optional course ID to clear only specific course cache
     * @return void
     */
    private static function clearProgressCache(int $userId, ?int $courseId = null): void {
        // Clear all progress cache for this user
        wp_cache_delete("simple_lms_progress_{$userId}_0", \SimpleLMS\Cache_Handler::CACHE_GROUP);
        
        // If courseId provided, clear only that course's cache (optimization)
        if ($courseId !== null && $courseId > 0) {
            wp_cache_delete("simple_lms_progress_{$userId}_{$courseId}", \SimpleLMS\Cache_Handler::CACHE_GROUP);
            wp_cache_delete("simple_lms_course_stats_{$courseId}", \SimpleLMS\Cache_Handler::CACHE_GROUP);
            do_action('simple_lms_progress_cache_cleared', $userId, $courseId);
            return;
        }
        
        // Otherwise, get all user's courses and clear their specific caches
        global $wpdb;
        $tableName = $wpdb->prefix . self::TABLE_NAME;
        
        $courseIds = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT course_id FROM {$tableName} WHERE user_id = %d",
            $userId
        ));
        
        foreach ($courseIds as $cId) {
            wp_cache_delete("simple_lms_progress_{$userId}_{$cId}", \SimpleLMS\Cache_Handler::CACHE_GROUP);
            wp_cache_delete("simple_lms_course_stats_{$cId}", \SimpleLMS\Cache_Handler::CACHE_GROUP);

            /**
             * Action: simple_lms_progress_cache_cleared
             * Fires after user progress cache is cleared for a course.
             *
             * @param int $userId
             * @param int $courseId
             */
            do_action('simple_lms_progress_cache_cleared', $userId, (int) $cId);
        }
    }

    /**
     * Get total lessons count for a course
     *
     * @param int $courseId Course ID
     * @return int Total lessons in course
     */
    public static function getTotalLessonsCount(int $courseId): int {
        if ($courseId <= 0) { return 0; }
        $modules = \SimpleLMS\Cache_Handler::getCourseModules($courseId);
        $total = 0;
        foreach ($modules as $module) {
            $lessons = \SimpleLMS\Cache_Handler::getModuleLessons((int) $module->ID);
            $total += is_array($lessons) ? count($lessons) : 0;
        }
        return $total;
    }
    
    /**
     * Format time in human readable format
     * 
     * @param int $seconds Time in seconds
     * @return string Formatted time
     */
    private static function formatTime(int $seconds): string {
        if ($seconds < 60) {
            return sprintf(__('%d secund', 'simple-lms'), $seconds);
        } elseif ($seconds < 3600) {
            return sprintf(__('%d minut', 'simple-lms'), floor($seconds / 60));
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return sprintf(__('%d hr. %d min.', 'simple-lms'), $hours, $minutes);
        }
    }
}

// Static init now delegates to instance via container (BC)
