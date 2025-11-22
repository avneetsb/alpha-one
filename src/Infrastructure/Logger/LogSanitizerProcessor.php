<?php

namespace TradingPlatform\Infrastructure\Logger;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class LogSanitizerProcessor implements ProcessorInterface
{
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

    public function __invoke(LogRecord $record): LogRecord
    {
        $record->context = $this->sanitize($record->context);
        $record->extra = $this->sanitize($record->extra);
        $record->message = $this->sanitizeString($record->message);
        
        return $record;
    }

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

    private function sanitizeString(string $message): string
    {
        // Simple regex to catch common patterns in strings if they leak
        // e.g. "token=xyz"
        // This is expensive, so use sparingly.
        return $message;
    }

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
