<?php
/**
 * Centralized security helper service for Simple LMS.
 *
 * Responsibilities:
 * - Nonce generation & verification (REST + AJAX contexts).
 * - Capability assertions (generic & post-specific).
 * - Common sanitization helpers for scalar/array inputs.
 * - Contextual access checks (course/module/lesson ownership/access).
 *
 * @package SimpleLMS
 * @since 1.0.0
 */

namespace SimpleLMS;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Security service helper.
 *
 * @package SimpleLMS
 * @since 1.0.0
 */
class Security_Service {
    /**
     * Default nonce action base (filterable).
     */
    public const NONCE_ACTION_BASE = 'simple_lms';

    /**
     * Create nonce for a given context suffix.
     *
     * @param string $context Context suffix.
     * @return string
     */
    public function createNonce( string $context = 'rest' ): string {
        $action = apply_filters( 'simple_lms_nonce_action', self::NONCE_ACTION_BASE . '_' . $context, $context );
        return wp_create_nonce( $action );
    }

    /**
     * Verify nonce value for given context suffix.
     *
     * @param string|null $nonce   Nonce value.
     * @param string      $context Context suffix.
     * @return bool
     */
    public function verifyNonce( ?string $nonce, string $context = 'rest' ): bool {
        if ( ! $nonce ) {
            return false;
        }
        $action = apply_filters( 'simple_lms_nonce_action', self::NONCE_ACTION_BASE . '_' . $context, $context );
        return (bool) wp_verify_nonce( $nonce, $action );
    }

    /**
     * Assert capability; throws \RuntimeException on failure.
     *
     * @param string   $capability Capability name.
     * @param int|null $objectId   Optional object ID.
     * @return void
     * @throws \RuntimeException When user lacks capability.
     */
    public function assertCapability( string $capability, ?int $objectId = null ): void {
        $can = null !== $objectId ? current_user_can( $capability, $objectId ) : current_user_can( $capability );
        if ( ! $can ) {
            throw new \RuntimeException( esc_html__( 'Insufficient permissions', 'simple-lms' ) );
        }
    }

    /**
     * Check if current user can view a course (admin bypass + access tags).
     *
     * @param int $courseId Course ID.
     * @return bool
     */
    public function currentUserCanViewCourse( int $courseId ): bool {
        if ( $courseId <= 0 ) {
            return false;
        }
        if ( current_user_can( 'edit_posts' ) ) {
            return true;
        }
        $access = (array) get_user_meta( get_current_user_id(), 'simple_lms_course_access', true );
        return in_array( $courseId, $access, true );
    }

    /**
     * Check if current user can edit a course.
     *
     * @param int $courseId Course ID.
     * @return bool
     */
    public function currentUserCanEditCourse( int $courseId ): bool {
        return $courseId > 0 && current_user_can( 'edit_post', $courseId );
    }

    /**
     * Check if current user can edit a module.
     *
     * @param int $moduleId Module ID.
     * @return bool
     */
    public function currentUserCanEditModule( int $moduleId ): bool {
        return $moduleId > 0 && current_user_can( 'edit_post', $moduleId );
    }

    /**
     * Check if current user can edit a lesson.
     *
     * @param int $lessonId Lesson ID.
     * @return bool
     */
    public function currentUserCanEditLesson( int $lessonId ): bool {
        return $lessonId > 0 && current_user_can( 'edit_post', $lessonId );
    }

    /**
     * Sanitization helpers.
     *
     * @param string $value Input value.
     * @return string
     */
    public function sanitizeText( string $value ): string {
        return sanitize_text_field( $value );
    }

    /**
     * Sanitize boolean-like input.
     *
     * @param mixed $value Input value.
     * @return bool
     */
    public function sanitizeBool( $value ): bool {
        return (bool) $value;
    }

    /**
     * Sanitize integer-like input.
     *
     * @param mixed $value Input value.
     * @return int
     */
    public function sanitizeInt( $value ): int {
        return absint( $value );
    }

    /**
     * Sanitize array of values.
     *
     * @param array  $values Values to sanitize.
     * @param string $type   Sanitization type.
     * @return array
     */
    public function sanitizeArray( array $values, string $type = 'text' ): array {
        return array_map(
            function ( $value ) use ( $type ) {
                switch ( $type ) {
                    case 'int':
                        return $this->sanitizeInt( $value );
                    case 'bool':
                        return $this->sanitizeBool( $value );
                    default:
                        return $this->sanitizeText( (string) $value );
                }
            },
            $values
        );
    }
}
