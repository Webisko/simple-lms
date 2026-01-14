<?php
/**
 * Cache and optimization handler class
 * 
 * @package SimpleLMS
 * @since 1.0.1
 */

declare(strict_types=1);

namespace SimpleLMS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cache and optimization handler class
 * 
 * Handles caching of course data to improve performance
 */
class Cache_Handler {
    /**
     * Cache group for plugin
     */
    public const CACHE_GROUP = 'simple_lms';

    /**
     * Default cache expiration time in seconds (12 hours)
     */
    public const DEFAULT_CACHE_EXPIRATION = 43200;

    /**
     * Cache expiration time - configurable via filter
     * 
     * @var int
     */
    private static int $cacheExpiration;

    /**
     * Initialize the handler
     * 
     * @return void
     */
    public static function init(): void {
        self::$cacheExpiration = (int) apply_filters('simple_lms_cache_expiration', self::DEFAULT_CACHE_EXPIRATION);
        
        add_action('save_post', [__CLASS__, 'flushCourseCache'], 10, 3);
        add_action('deleted_post', [__CLASS__, 'flushCourseCacheOnDelete']);
        add_action('trashed_post', [__CLASS__, 'flushCourseCacheOnDelete']);
        add_action('untrashed_post', [__CLASS__, 'flushCourseCache']);
        add_action('before_delete_post', [__CLASS__, 'flushCourseCacheBeforeDelete']);

        // Capture old meta values before an update so we can invalidate previous parents
        add_filter('pre_update_post_meta', [__CLASS__, 'captureOldMetaValue'], 10, 4);
        // Invalidate caches when relationships change via post meta
        add_action('updated_post_meta', [__CLASS__, 'flushOnMetaChange'], 10, 4);
        add_action('added_post_meta', [__CLASS__, 'flushOnMetaChange'], 10, 4);
        add_action('deleted_post_meta', [__CLASS__, 'flushOnMetaChange'], 10, 4);
    }

    /**
     * Get modules for a course with caching
     * 
     * @param int $courseId Course ID
     * @return array Array of WP_Post objects
     */
    public static function getCourseModules(int $courseId): array {
        if ($courseId <= 0) {
            self::log('warning', 'Invalid course ID for getCourseModules: {courseId}', ['courseId' => $courseId]);
            return [];
        }

        $cacheKey = self::generateCacheKey('course_modules', $courseId);
        $modules = wp_cache_get($cacheKey, self::CACHE_GROUP);

        if (false === $modules) {
            try {
                $modules = get_posts([
                    'post_type'      => 'module',
                    'posts_per_page' => -1,
                    'meta_key'       => 'parent_course',
                    'meta_value'     => $courseId,
                    'orderby'        => 'menu_order',
                    'order'          => 'ASC',
                    'post_status'    => ['publish', 'draft']
                ]);

                if (is_array($modules)) {
                    wp_cache_set($cacheKey, $modules, self::CACHE_GROUP, self::$cacheExpiration);
                    self::log('debug', 'Cached modules for course {courseId} (key: {key})', ['courseId' => $courseId, 'key' => $cacheKey]);
                } else {
                    self::log('error', 'Invalid modules data for course {courseId}', ['courseId' => $courseId]);
                    $modules = [];
                }
            } catch (\Exception $e) {
                self::log('error', 'Error fetching modules for course {courseId}: {error}', ['courseId' => $courseId, 'error' => $e]);
                return [];
            }
        }

        return is_array($modules) ? $modules : [];
    }

    /**
     * Get lessons for a module with caching
     * 
     * @param int $moduleId Module ID
     * @return array Array of WP_Post objects
     */
    public static function getModuleLessons(int $moduleId): array {
        if ($moduleId <= 0) {
            self::log('warning', 'Invalid module ID for getModuleLessons: {moduleId}', ['moduleId' => $moduleId]);
            return [];
        }

        $cacheKey = self::generateCacheKey('module_lessons', $moduleId);
        $lessons = wp_cache_get($cacheKey, self::CACHE_GROUP);

        if (false === $lessons) {
            try {
                $lessons = get_posts([
                    'post_type'      => 'lesson',
                    'posts_per_page' => -1,
                    'meta_key'       => 'parent_module',
                    'meta_value'     => $moduleId,
                    'orderby'        => 'menu_order',
                    'order'          => 'ASC',
                    'post_status'    => ['publish', 'draft']
                ]);

                if (is_array($lessons)) {
                    wp_cache_set($cacheKey, $lessons, self::CACHE_GROUP, self::$cacheExpiration);
                    self::log('debug', 'Cached lessons for module {moduleId} (key: {key})', ['moduleId' => $moduleId, 'key' => $cacheKey]);
                } else {
                    self::log('error', 'Invalid lessons data for module {moduleId}', ['moduleId' => $moduleId]);
                    $lessons = [];
                }
            } catch (\Exception $e) {
                self::log('error', 'Error fetching lessons for module {moduleId}: {error}', ['moduleId' => $moduleId, 'error' => $e]);
                return [];
            }
        }

        return is_array($lessons) ? $lessons : [];
    }

    /**
     * Get course statistics with caching
     * 
     * @param int $courseId Course ID
     * @return array Statistics array with module_count and lesson_count
     */
    public static function getCourseStats(int $courseId): array {
        if ($courseId <= 0) {
            self::log('warning', 'Invalid course ID for getCourseStats: {courseId}', ['courseId' => $courseId]);
            return ['module_count' => 0, 'lesson_count' => 0];
        }

        $cacheKey = self::generateCacheKey('course_stats', $courseId);
        $stats = wp_cache_get($cacheKey, self::CACHE_GROUP);

        if (false === $stats) {
            try {
                $modules = self::getCourseModules($courseId);
                $lessonCount = 0;

                foreach ($modules as $module) {
                    $lessons = self::getModuleLessons($module->ID);
                    $lessonCount += count($lessons);
                }

                $stats = [
                    'module_count' => count($modules),
                    'lesson_count' => $lessonCount
                ];

                wp_cache_set($cacheKey, $stats, self::CACHE_GROUP, self::$cacheExpiration);
                self::log('debug', 'Cached stats for course {courseId} (key: {key})', ['courseId' => $courseId, 'key' => $cacheKey]);
            } catch (\Exception $e) {
                self::log('error', 'Error calculating stats for course {courseId}: {error}', ['courseId' => $courseId, 'error' => $e]);
                return ['module_count' => 0, 'lesson_count' => 0];
            }
        }

        return is_array($stats) ? $stats : ['module_count' => 0, 'lesson_count' => 0];
    }

    /**
     * Flush course-related caches when content is updated
     * 
     * @param int $postId Post ID
     * @param \WP_Post|null $post Post object
     * @param bool|null $update Whether this is an update
     * @return void
     */
    public static function flushCourseCache(int $postId, ?\WP_Post $post = null, ?bool $update = null): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Get post type without additional queries if possible
        $postType = $post ? $post->post_type : get_post_type($postId);
        if (!$postType) {
            return;
        }

        static $alreadyFlushed = [];
        if (isset($alreadyFlushed[$postId])) {
            return;
        }
        $alreadyFlushed[$postId] = true;

        try {
            switch ($postType) {
                case 'course':
                    self::flushCacheKeys([
                        self::generateCacheKey('course_modules', $postId),
                    ]);
                    self::flushCourseStatsCaches($postId);
                    break;
                
                case 'module':
                    $courseId = (int) get_post_meta($postId, 'parent_course', true);
                    if ($courseId > 0) {
                        self::flushCacheKeys([
                            self::generateCacheKey('course_modules', $courseId)
                        ]);
                        self::flushCourseStatsCaches($courseId);
                    }
                    self::flushCacheKeys([
                        self::generateCacheKey('module_lessons', $postId)
                    ]);
                    break;
                
                case 'lesson':
                    $moduleId = (int) get_post_meta($postId, 'parent_module', true);
                    if ($moduleId > 0) {
                        self::flushCacheKeys([
                            self::generateCacheKey('module_lessons', $moduleId)
                        ]);
                        
                        $courseId = (int) get_post_meta($moduleId, 'parent_course', true);
                        if ($courseId > 0) {
                            self::flushCourseStatsCaches($courseId);
                        }
                    }
                    break;
            }
        } catch (\Exception $e) {
            self::log('error', 'Error flushing caches for post {postId}: {error}', ['postId' => $postId, 'error' => $e]);
        }
    }

    /**
     * Flush course cache when post is deleted
     * 
     * @param int $postId Post ID
     * @return void
     */
    public static function flushCourseCacheOnDelete(int $postId): void {
        self::flushCourseCache($postId);
    }

    /**
     * Internal logger helper using DI container if available
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    private static function log(string $level, string $message, array $context = []): void {
        try {
            $container = ServiceContainer::getInstance();
            /** @var Logger $logger */
            $logger = $container->get(Logger::class);
            $logger->log($level, $message, $context);
        } catch (\Throwable $t) {
            // Fallback for environments without container/logger
            if (function_exists('error_log')) {
                error_log('[simple-lms.' . $level . '] ' . strtr($message, array_map(function($k){return '{'.$k.'}';}, array_keys($context)), array_values($context)));
            }
        }
    }

    /**
     * Flush caches just before a post is deleted while meta is still available.
     *
     * @param int $postId Post ID
     * @return void
     */
    public static function flushCourseCacheBeforeDelete(int $postId): void {
        try {
            $postType = get_post_type($postId);
            if (!$postType) {
                return;
            }

            switch ($postType) {
                case 'course':
                    self::flushCacheKeys([
                        self::generateCacheKey('course_modules', $postId)
                    ]);
                    self::flushCourseStatsCaches($postId);
                    break;
                case 'module':
                    $courseId = (int) get_post_meta($postId, 'parent_course', true);
                    if ($courseId > 0) {
                        self::flushCacheKeys([
                            self::generateCacheKey('course_modules', $courseId)
                        ]);
                        self::flushCourseStatsCaches($courseId);
                    }
                    self::flushCacheKeys([
                        self::generateCacheKey('module_lessons', $postId)
                    ]);
                    break;
                case 'lesson':
                    $moduleId = (int) get_post_meta($postId, 'parent_module', true);
                    if ($moduleId > 0) {
                        self::flushCacheKeys([
                            self::generateCacheKey('module_lessons', $moduleId)
                        ]);
                        $courseId = (int) get_post_meta($moduleId, 'parent_course', true);
                        if ($courseId > 0) {
                            self::flushCourseStatsCaches($courseId);
                        }
                    }
                    break;
            }
        } catch (\Exception $e) {
            error_log("Simple LMS: Error pre-delete cache flush for post {$postId}: " . $e->getMessage());
        }
    }

    /**
     * Generate standardized cache key
     * 
     * @param string $type Cache key type
     * @param int $id Object ID
     * @return string Generated cache key with versioning
     */
    private static function generateCacheKey(string $type, int $id): string {
        $version = self::getCacheVersion($type, $id);
        return sanitize_key($type . '_' . $id . '_v' . $version);
    }

    /**
     * Get cache version for a specific object
     * 
     * @param string $type Cache key type
     * @param int $id Object ID
     * @return int Cache version number
     */
    private static function getCacheVersion(string $type, int $id): int {
        // For course/module/lesson-specific caches, use their last modified time
        if (in_array($type, ['course_modules', 'course_stats', 'module_lessons'])) {
            $postId = $id;
            $lastModified = get_post_modified_time('U', true, $postId);
            
            if ($lastModified) {
                return (int) $lastModified;
            }
        }
        
        // Fallback: use a global cache version stored in options
        return (int) get_option('simple_lms_cache_version', 1);
    }

    /**
     * Increment global cache version (nuclear option for cache invalidation)
     * 
     * @return void
     */
    public static function incrementCacheVersion(): void {
        $currentVersion = (int) get_option('simple_lms_cache_version', 1);
        update_option('simple_lms_cache_version', $currentVersion + 1, false);
    }

    /**
     * Flush multiple cache keys at once
     * 
     * @param array $keys Array of cache keys to flush
     * @return void
     */
    private static function flushCacheKeys(array $keys): void {
        foreach ($keys as $key) {
            if (is_string($key) && !empty($key)) {
                wp_cache_delete($key, self::CACHE_GROUP);
            }
        }
    }

    /**
     * Storage for previous meta values captured before update
     * @var array<int, array<string, mixed>>
     */
    private static array $oldMetaValues = [];

    /**
     * Capture old meta values before they are updated so we can flush caches for previous parents.
     *
     * @param mixed $check Default null/false to allow update
     * @param int $objectId Post ID whose meta is being updated
     * @param string $metaKey Meta key
     * @param mixed $metaValue New meta value
     * @return mixed Unmodified $check
     */
    public static function captureOldMetaValue($check, int $objectId, string $metaKey, $metaValue) {
        if (in_array($metaKey, ['parent_course', 'parent_module'], true)) {
            if (!isset(self::$oldMetaValues[$objectId])) {
                self::$oldMetaValues[$objectId] = [];
            }
            // Store current value as "old" before it changes
            self::$oldMetaValues[$objectId][$metaKey] = get_post_meta($objectId, $metaKey, true);
        }
        return $check;
    }

    /**
     * Flush caches when course/module relationships change via post meta modifications.
     * Handles added, updated, and deleted meta for parent relations.
     *
     * @param int $metaId Meta row ID
     * @param int $objectId Post ID
     * @param string $metaKey Meta key
     * @param mixed $metaValue Meta value (new for add/update, old for delete)
     * @return void
     */
    public static function flushOnMetaChange(int $metaId, int $objectId, string $metaKey, $metaValue): void {
        if (!in_array($metaKey, ['parent_course', 'parent_module'], true)) {
            return;
        }

        try {
            $postType = get_post_type($objectId);
            if (!$postType) {
                return;
            }

            $newVal = get_post_meta($objectId, $metaKey, true);
            $oldVal = isset(self::$oldMetaValues[$objectId][$metaKey]) ? self::$oldMetaValues[$objectId][$metaKey] : null;
            // Cleanup stored old value after use
            if (isset(self::$oldMetaValues[$objectId][$metaKey])) {
                unset(self::$oldMetaValues[$objectId][$metaKey]);
                if (empty(self::$oldMetaValues[$objectId])) {
                    unset(self::$oldMetaValues[$objectId]);
                }
            }

            if ('module' === $postType && 'parent_course' === $metaKey) {
                $affectedCourses = [];
                if (!empty($oldVal)) {
                    $affectedCourses[] = (int) $oldVal;
                }
                if (!empty($newVal)) {
                    $affectedCourses[] = (int) $newVal;
                }
                $affectedCourses = array_values(array_unique(array_filter($affectedCourses)));
                foreach ($affectedCourses as $courseId) {
                    self::flushCacheKeys([
                        self::generateCacheKey('course_modules', $courseId)
                    ]);
                    self::flushCourseStatsCaches($courseId);
                }
            }

            if ('lesson' === $postType && 'parent_module' === $metaKey) {
                $affectedModules = [];
                if (!empty($oldVal)) {
                    $affectedModules[] = (int) $oldVal;
                }
                if (!empty($newVal)) {
                    $affectedModules[] = (int) $newVal;
                }
                $affectedModules = array_values(array_unique(array_filter($affectedModules)));
                foreach ($affectedModules as $moduleId) {
                    self::flushCacheKeys([
                        self::generateCacheKey('module_lessons', $moduleId)
                    ]);
                    $courseId = (int) get_post_meta($moduleId, 'parent_course', true);
                    if ($courseId > 0) {
                        self::flushCourseStatsCaches($courseId);
                    }
                }
            }
        } catch (\Exception $e) {
            error_log("Simple LMS: Error flushing cache on meta change for post {$objectId}: " . $e->getMessage());
        }
    }

    /**
     * Flush both cache handler and progress tracker course stats caches
     *
     * @param int $courseId Course ID
     * @return void
     */
    private static function flushCourseStatsCaches(int $courseId): void {
        if ($courseId <= 0) {
            return;
        }
        self::flushCacheKeys([
            self::generateCacheKey('course_stats', $courseId)
        ]);
        // Also clear Progress_Tracker course stats cache if present
        wp_cache_delete("simple_lms_course_stats_{$courseId}", self::CACHE_GROUP);
    }
}

// Cache_Handler is managed by ServiceContainer