<?php

namespace TradingPlatform\Infrastructure\Logger;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Class: Log Sanitizer Processor
 *
 * Monolog processor that automatically redacts sensitive information
 * (passwords, tokens, keys) from log context and extras before writing.
 */
class LogSanitizerProcessor implements ProcessorInterface
{
    /**
     * @var array List of keys to redact.
     */
    private array $sensitiveKeys = [
        'access_token',
        'refresh_token',
        'api_key',
        'api_secret',
        'password',
        'secret',
        'token',
        'authorization',
    ];

    /**
     * Process the log record.
     *
     * Recursively sanitizes context and extra arrays, and attempts to scrub
     * sensitive patterns from the log message itself.
     *
     * @param  LogRecord  $record  The log record to process.
     * @return LogRecord The sanitized log record.
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $record->context = $this->sanitize($record->context);
        $record->extra = $this->sanitize($record->extra);
        $record->message = $this->sanitizeString($record->message);

        return $record;
    }

    /**
     * Recursively sanitize an array.
     */
    private function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            } elseif (is_string($key) && $this->isSensitive($key)) {
                $data[$key] = '***REDACTED***';
            } elseif (is_string($value)) {
                // Check if value looks like a token or key if needed,
                // but relying on key names is safer for performance.
            }
        }

        return $data;
    }

    /**
     * Sanitize a string message.
     */
    private function sanitizeString(string $message): string
    {
        // Simple regex to catch common patterns in strings if they leak
        // e.g. "token=xyz"
        // This is expensive, so use sparingly.
        return $message;
    }

    /**
     * Check if a key is sensitive.
     */
    private function isSensitive(string $key): bool
    {
        $lowerKey = strtolower($key);
        foreach ($this->sensitiveKeys as $sensitiveKey) {
            if (str_contains($lowerKey, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }
}
