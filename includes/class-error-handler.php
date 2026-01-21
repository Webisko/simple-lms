<?php
/**
 * Error handler to capture PHP errors and forward to Logger.
 *
 * @package SimpleLMS
 * @since 1.0.0
 */

namespace SimpleLMS;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Error handler service.
 */
class Error_Handler {
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private Logger $logger;

    /**
     * Error handler constructor.
     *
     * @param Logger $logger Logger instance.
     */
    public function __construct( Logger $logger ) {
        $this->logger = $logger;
    }

    /**
     * Register PHP error handlers.
     *
     * @return void
     */
    public function register(): void {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
        set_error_handler( [ $this, 'handleError' ] );
        set_exception_handler( [ $this, 'handleException' ] );
        register_shutdown_function( [ $this, 'handleShutdown' ] );
    }

    /**
     * Handle PHP errors.
     *
     * @param int    $errno   Error number.
     * @param string $errstr  Error message.
     * @param string $errfile Error file path.
     * @param int    $errline Error line number.
     * @return bool
     */
    public function handleError( int $errno, string $errstr, string $errfile = '', int $errline = 0 ): bool {
        $level = in_array( $errno, [ E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ], true ) ? 'error' : 'warning';
        $this->logger->log(
            $level,
            'PHP error: {message} in {file}:{line}',
            [
                'message' => $errstr,
                'file'    => $errfile,
                'line'    => $errline,
                'errno'   => $errno,
            ]
        );
        return false; // Allow default PHP error handler too.
    }

    /**
     * Handle uncaught exceptions.
     *
     * @param \Throwable $e Exception instance.
     * @return void
     */
    public function handleException( \Throwable $e ): void {
        $this->logger->critical(
            'Uncaught exception: {exception} in {file}:{line}',
            [
                'exception' => $e,
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]
        );
    }

    /**
     * Handle fatal shutdown errors.
     *
     * @return void
     */
    public function handleShutdown(): void {
        $error = error_get_last();
        if ( $error && in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ], true ) ) {
            $this->logger->critical(
                'Shutdown error: {message} in {file}:{line}',
                [
                    'message' => $error['message'] ?? '',
                    'file'    => $error['file'] ?? '',
                    'line'    => $error['line'] ?? 0,
                ]
            );
        }
    }
}
