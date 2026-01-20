<?php
namespace SimpleLMS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Error handler to capture PHP errors and forward to Logger.
 */
class Error_Handler
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError(int $errno, string $errstr, string $errfile = '', int $errline = 0): bool
    {
        $level = in_array($errno, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true) ? 'error' : 'warning';
        $this->logger->log($level, 'PHP error: {message} in {file}:{line}', [
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'errno' => $errno,
        ]);
        return false; // Allow default PHP error handler too
    }

    public function handleException(\Throwable $e): void
    {
        $this->logger->critical('Uncaught exception: {exception} in {file}:{line}', [
            'exception' => $e,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $this->logger->critical('Shutdown error: {message} in {file}:{line}', [
                'message' => $error['message'] ?? '',
                'file' => $error['file'] ?? '',
                'line' => $error['line'] ?? 0,
            ]);
        }
    }
}
