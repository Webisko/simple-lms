<?php
namespace SimpleLMS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * PSR-3 Logger Interface (fallback for when Composer not available)
 *
 * Describes a logger instance.
 */
interface LoggerInterface
{
    public function emergency($message, array $context = []);
    public function alert($message, array $context = []);
    public function critical($message, array $context = []);
    public function error($message, array $context = []);
    public function warning($message, array $context = []);
    public function notice($message, array $context = []);
    public function info($message, array $context = []);
    public function debug($message, array $context = []);
    public function log($level, $message, array $context = []);
}

/**
 * Simple PSR-3 compatible logger with context interpolation.
 */
class Logger implements LoggerInterface
{
    private string $channel;
    private bool $debugEnabled;

    public function __construct(string $channel = 'simple-lms', bool $debugEnabled = true)
    {
        $this->channel = $channel;
        $this->debugEnabled = $debugEnabled;
    }

    public function emergency($message, array $context = []): void { $this->log('emergency', $message, $context); }
    public function alert($message, array $context = []): void { $this->log('alert', $message, $context); }
    public function critical($message, array $context = []): void { $this->log('critical', $message, $context); }
    public function error($message, array $context = []): void { $this->log('error', $message, $context); }
    public function warning($message, array $context = []): void { $this->log('warning', $message, $context); }
    public function notice($message, array $context = []): void { $this->log('notice', $message, $context); }
    public function info($message, array $context = []): void { $this->log('info', $message, $context); }
    public function debug($message, array $context = []): void {
        if ($this->debugEnabled) {
            $this->log('debug', $message, $context);
        }
    }

    public function log($level, $message, array $context = []): void
    {
        if (!$this->debugEnabled && in_array($level, ['debug', 'info', 'notice'], true)) {
            return;
        }
        $timestamp = gmdate('Y-m-d H:i:s');
        $msg = $this->interpolate((string) $message, $context);
        $line = sprintf('[%s] %s.%s: %s', $timestamp, $this->channel, $level, $msg);

        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
            error_log($line);
        }
    }

    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_scalar($val)) {
                $replace['{'.$key.'}'] = (string) $val;
            } elseif ($val instanceof \Throwable) {
                $replace['{'.$key.'}'] = get_class($val) . ': ' . $val->getMessage();
            } else {
                $replace['{'.$key.'}'] = wp_json_encode($val);
            }
        }
        return strtr($message, $replace);
    }
}
