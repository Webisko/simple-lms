<?php
/**
 * PSR-3 Logger Interface (fallback for when Composer not available).
 *
 * @package SimpleLMS
 * @since 1.0.0
 */

namespace SimpleLMS;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Describes a logger instance.
 */
interface LoggerInterface {
    /**
     * System is unusable.
     *
     * @param string $message Message.
     * @param array  $context Context.
     * @return void
     */
    public function emergency( $message, array $context = [] );

    /**
     * Action must be taken immediately.
     *
     * @param string $message Message.
     * @param array  $context Context.
     * @return void
     */
    public function alert( $message, array $context = [] );

    /**
     * Critical conditions.
     *
     * @param string $message Message.
     * @param array  $context Context.
     * @return void
     */
    public function critical( $message, array $context = [] );

    /**
     * Runtime errors that do not require immediate action.
     *
     * @param string $message Message.
     * @param array  $context Context.
     * @return void
     */
    public function error( $message, array $context = [] );

    /**
     * Exceptional occurrences that are not errors.
     *
     * @param string $message Message.
     * @param array  $context Context.
     * @return void
     */
    public function warning( $message, array $context = [] );

    /**
     * Normal but significant events.
     *
     * @param string $message Message.
     * @param array  $context Context.
     * @return void
     */
    public function notice( $message, array $context = [] );

    /**
     * Interesting events.
     *
     * @param string $message Message.
     * @param array  $context Context.
     * @return void
     */
    public function info( $message, array $context = [] );

    /**
     * Detailed debug information.
     *
     * @param string $message Message.
     * @param array  $context Context.
     * @return void
     */
    public function debug( $message, array $context = [] );

    /**
     * Logs with an arbitrary level.
     *
     * @param string $level   Log level.
     * @param string $message Message.
     * @param array  $context Context.
     * @return void
     */
    public function log( $level, $message, array $context = [] );
}
