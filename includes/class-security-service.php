<?php
/**
 * Centralized security helper service for Simple LMS
 *
 * Responsibilities:
 * - Nonce generation & verification (REST + AJAX contexts)
 * - Capability assertions (generic & post-specific)
 * - Common sanitization helpers for scalar / array inputs
 * - Contextual access checks (course/module/lesson ownership / access)
 */

declare(strict_types=1);

namespace SimpleLMS;

if (!defined('ABSPATH')) { exit; }

class Security_Service {
    /**
     * Default nonce action base (filterable)
     */
    public const NONCE_ACTION_BASE = 'simple_lms';

    /**
     * Create nonce for a given context suffix
     */
    public function createNonce(string $context = 'rest'): string {
        $action = apply_filters('simple_lms_nonce_action', self::NONCE_ACTION_BASE . '_' . $context, $context);
        return wp_create_nonce($action);
    }

    /**
     * Verify nonce value for given context suffix
     */
    public function verifyNonce(?string $nonce, string $context = 'rest'): bool {
        if (!$nonce) { return false; }
        $action = apply_filters('simple_lms_nonce_action', self::NONCE_ACTION_BASE . '_' . $context, $context);
        return (bool) wp_verify_nonce($nonce, $action);
    }

    /**
     * Assert capability; throws \RuntimeException on failure
     */
    public function assertCapability(string $capability, ?int $objectId = null): void {
        $can = $objectId !== null ? current_user_can($capability, $objectId) : current_user_can($capability);
        if (!$can) {
            throw new \RuntimeException(__('Niewystarczające uprawnienia', 'simple-lms'));
        }
    }

    /**
     * Check if current user can view a course (admin bypass + access tags)
     */
    public function currentUserCanViewCourse(int $courseId): bool {
        if ($courseId <= 0) return false;
        if (current_user_can('edit_posts')) return true;
        $access = (array) get_user_meta(get_current_user_id(), 'simple_lms_course_access', true);
        return in_array($courseId, $access, true);
    }

    /**
     * Check if current user can edit a course
     */
    public function currentUserCanEditCourse(int $courseId): bool {
        return $courseId > 0 && current_user_can('edit_post', $courseId);
    }

    public function currentUserCanEditModule(int $moduleId): bool {
        return $moduleId > 0 && current_user_can('edit_post', $moduleId);
    }

    public function currentUserCanEditLesson(int $lessonId): bool {
        return $lessonId > 0 && current_user_can('edit_post', $lessonId);
    }

    /** Sanitization helpers */
    public function sanitizeText(string $value): string { return sanitize_text_field($value); }
    public function sanitizeBool($value): bool { return (bool) $value; }
    public function sanitizeInt($value): int { return absint($value); }

    /** Sanitize array of values */
    public function sanitizeArray(array $values, string $type = 'text'): array {
        return array_map(function ($v) use ($type) {
            switch ($type) {
                case 'int': return $this->sanitizeInt($v);
                case 'bool': return $this->sanitizeBool($v);
                default: return $this->sanitizeText((string) $v);
            }
        }, $values);
    }
}
