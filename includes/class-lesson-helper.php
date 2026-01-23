<?php
namespace SimpleLMS;

if (!defined('ABSPATH')) {
    exit;
}

class Lesson_Helper
{
    /**
     * Check if a lesson is completed for a given user (defaults to current user).
     */
    public static function isLessonCompleted(int $lesson_id, ?int $user_id = null): bool
    {
        $lesson_id = absint($lesson_id);
        if (!$lesson_id) {
            return false;
        }

        $user_id = $user_id !== null ? absint($user_id) : (int) get_current_user_id();
        if (!$user_id) {
            return false;
        }

        if (!class_exists(Progress_Tracker::class) || !method_exists(Progress_Tracker::class, 'isLessonCompleted')) {
            return false;
        }

        return (bool) Progress_Tracker::isLessonCompleted($user_id, $lesson_id);
    }

    /**
     * Return a localized lesson count label that includes the number.
     */
    public static function getLessonsCountText(int $count): string
    {
        $count = max(0, (int) $count);

        $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
        if (is_string($locale) && str_starts_with($locale, 'pl')) {
            if ($count === 0) {
                return 'brak lekcji';
            }

            $mod10 = $count % 10;
            $mod100 = $count % 100;
            if ($count === 1) {
                $form = 'lekcja';
            } elseif ($mod10 >= 2 && $mod10 <= 4 && !($mod100 >= 12 && $mod100 <= 14)) {
                $form = 'lekcje';
            } else {
                $form = 'lekcji';
            }

            return sprintf('%s %s', number_format_i18n($count), $form);
        }

        if ($count === 0) {
            return sprintf(
                /* translators: %s: number of lessons */
                _n('%s lesson', '%s lessons', $count, 'simple-lms'),
                number_format_i18n($count)
            );
        }

        $label = _n('Lesson', 'Lessons', $count, 'simple-lms');

        return sprintf(
            /* translators: 1: number of lessons, 2: lesson label */
            '%1$s %2$s',
            number_format_i18n($count),
            $label
        );
    }

    /**
     * Get previous lesson in course sequence.
     */
    public static function getPreviousLesson(int $lesson_id): ?\WP_Post
    {
        return self::getAdjacentLesson($lesson_id, -1);
    }

    /**
     * Get next lesson in course sequence.
     */
    public static function getNextLesson(int $lesson_id): ?\WP_Post
    {
        return self::getAdjacentLesson($lesson_id, 1);
    }

    /**
     * @return ?\WP_Post
     */
    private static function getAdjacentLesson(int $lesson_id, int $direction): ?\WP_Post
    {
        $lesson_id = absint($lesson_id);
        if (!$lesson_id) {
            return null;
        }

        $module_id = (int) get_post_meta($lesson_id, 'parent_module', true);
        $course_id = 0;

        if ($module_id) {
            $course_id = (int) get_post_meta($module_id, 'parent_course', true);
        }

        // Prefer full course sequence (cross-module navigation).
        if ($course_id) {
            $sequence = self::getCourseLessonSequence($course_id);
            if ($sequence) {
                $count = count($sequence);
                for ($i = 0; $i < $count; $i++) {
                    if ((int) $sequence[$i]->ID !== $lesson_id) {
                        continue;
                    }
                    $target_index = $i + $direction;
                    if ($target_index >= 0 && $target_index < $count) {
                        return $sequence[$target_index];
                    }
                    return null;
                }
            }
        }

        // Fallback: navigate only within module.
        if ($module_id) {
            $lessons = self::getModuleLessons($module_id);
            if ($lessons) {
                $count = count($lessons);
                for ($i = 0; $i < $count; $i++) {
                    if ((int) $lessons[$i]->ID !== $lesson_id) {
                        continue;
                    }
                    $target_index = $i + $direction;
                    if ($target_index >= 0 && $target_index < $count) {
                        return $lessons[$target_index];
                    }
                    return null;
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, \WP_Post>
     */
    private static function getCourseLessonSequence(int $course_id): array
    {
        static $cache = [];
        $course_id = absint($course_id);
        if (!$course_id) {
            return [];
        }

        if (isset($cache[$course_id])) {
            return $cache[$course_id];
        }

        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        $modules = get_posts([
            'post_type'              => 'module',
            'posts_per_page'         => -1,
            'post_status'            => 'publish',
            'meta_key'               => 'parent_course',
            'meta_value'             => $course_id,
            'orderby'                => ['menu_order' => 'ASC', 'ID' => 'ASC'],
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        ]);

        if (!$modules) {
            $cache[$course_id] = [];
            return $cache[$course_id];
        }

        $module_ids = array_map('intval', wp_list_pluck($modules, 'ID'));
        if (!$module_ids) {
            $cache[$course_id] = [];
            return $cache[$course_id];
        }

        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        $all_lessons = get_posts([
            'post_type'              => 'lesson',
            'posts_per_page'         => -1,
            'post_status'            => 'publish',
            'meta_query'             => [
                [
                    'key'     => 'parent_module',
                    'value'   => $module_ids,
                    'compare' => 'IN',
                    'type'    => 'NUMERIC',
                ],
            ],
            'orderby'                => ['menu_order' => 'ASC', 'ID' => 'ASC'],
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        ]);

        $lessons_by_module = [];
        foreach ($all_lessons as $lesson) {
            if (!$lesson instanceof \WP_Post) {
                continue;
            }
            $parent_module = (int) get_post_meta($lesson->ID, 'parent_module', true);
            if (!$parent_module) {
                continue;
            }
            if (!isset($lessons_by_module[$parent_module])) {
                $lessons_by_module[$parent_module] = [];
            }
            $lessons_by_module[$parent_module][] = $lesson;
        }

        $sequence = [];
        foreach ($module_ids as $module_id) {
            if (!empty($lessons_by_module[$module_id])) {
                foreach ($lessons_by_module[$module_id] as $lesson) {
                    $sequence[] = $lesson;
                }
            }
        }

        $cache[$course_id] = $sequence;
        return $cache[$course_id];
    }

    /**
     * @return array<int, \WP_Post>
     */
    private static function getModuleLessons(int $module_id): array
    {
        $module_id = absint($module_id);
        if (!$module_id) {
            return [];
        }

        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        $lessons = get_posts([
            'post_type'              => 'lesson',
            'posts_per_page'         => -1,
            'post_status'            => 'publish',
            'meta_key'               => 'parent_module',
            'meta_value'             => $module_id,
            'orderby'                => ['menu_order' => 'ASC', 'ID' => 'ASC'],
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        ]);

        return is_array($lessons) ? $lessons : [];
    }
}
