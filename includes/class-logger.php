<?php
/**
 * Simple PSR-3 compatible logger with context interpolation.
 *
 * @package SimpleLMS
 * @since 1.0.0
 */

namespace SimpleLMS;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Logger implementation.
 */
class Logger implements LoggerInterface {
    /**
     * Logger channel.
     *
     * @var string
     */
    private string $channel;

    /**
     * Whether debug logging is enabled.
     *
     * @var bool
     */
    private bool $debugEnabled;

    /**
     * Logger constructor.
     *
     * @param string $channel      Channel name.
     * @param bool   $debugEnabled Debug flag.
     */
    public function __construct( string $channel = 'simple-lms', bool $debugEnabled = true ) {
        $this->channel      = $channel;
        $this->debugEnabled = $debugEnabled;
    }

    /**
     * System is unusable.
     *
     * @param string $message Message.
     * @param array  $context Context.
     * @return void
     */
    public function emergency( $message, array $context = [] ): void {
        $this->log( 'emergency', $message, $context );
    }

    /**
     * Action must be taken immediately.
     *
     * @param string $message Message.
     * @param array  $context Context.
     * @return void
     */
    public function alert( $message, array $context = [] ): void {
        $this->log( 'alert', $message, $context );
    }

    /**
     * Critical conditions.
     *
     * @param string $message Message.
     * @param array  $context Context.
     * @return void
     */
    public function critical( $message, array $context = [] ): void {
        $this->log( 'critical', $message, $context );
    }

    /**
     * Runtime errors that do not require immediate action.
     *
     * @param string $message Message.
     * @param array  $context Context.
     * @return void
     */
    public function error( $message, array $context = [] ): void {
        $this->log( 'error', $message, $context );
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * @param string $message Message.
     * @param array  $context Context.
     * @return void
     */
    public function warning( $message, array $context = [] ): void {
        $this->log( 'warning', $message, $context );
    }

    /**
     * Normal but significant events.
     *
     * @param string $message Message.
     * @param array  $context Context.
     * @return void
     */
    public function notice( $message, array $context = [] ): void {
        $this->log( 'notice', $message, $context );
    }

    /**
     * Interesting events.
     *
     * @param string $message Message.
     * @param array  $context Context.
     * @return void
     */
    public function info( $message, array $context = [] ): void {
        $this->log( 'info', $message, $context );
    }

    /**
     * Detailed debug information.
     *
     * @param string $message Message.
     * @param array  $context Context.
     * @return void
     */
    public function debug( $message, array $context = [] ): void {
        if ( $this->debugEnabled ) {
            $this->log( 'debug', $message, $context );
        }
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param string $level   Log level.
     * @param string $message Message.
     * @param array  $context Context.
     * @return void
     */
    public function log( $level, $message, array $context = [] ): void {
        if ( ! $this->debugEnabled && in_array( $level, [ 'debug', 'info', 'notice' ], true ) ) {
            return;
        }
        $timestamp = gmdate( 'Y-m-d H:i:s' );
        $msg       = $this->interpolate( (string) $message, $context );
        $line      = sprintf( '[%s] %s.%s: %s', $timestamp, $this->channel, $level, $msg );

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'error_log' ) ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( $line );
        }
    }

    /**
     * Interpolate context values into the message placeholders.
     *
     * @param string $message Message template.
     * @param array  $context Context values.
     * @return string
     */
    private function interpolate( string $message, array $context ): string {
        $replace = [];
        foreach ( $context as $key => $val ) {
            if ( is_scalar( $val ) ) {
                $replace[ '{' . $key . '}' ] = (string) $val;
            } elseif ( $val instanceof \Throwable ) {
                $replace[ '{' . $key . '}' ] = get_class( $val ) . ': ' . $val->getMessage();
            } else {
                $replace[ '{' . $key . '}' ] = wp_json_encode( $val );
            }
        }
        return strtr( $message, $replace );
    }
}
