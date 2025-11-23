<?php

namespace TradingPlatform\Infrastructure\Logger;

use Monolog\Handler\AbstractProcessingHandler;
use TradingPlatform\Infrastructure\Cache\RedisAdapter;

/**
 * Class: Async Log Handler
 *
 * Monolog handler that offloads log writing to a Redis queue.
 * Ensures that logging operations do not block the main application thread,
 * improving performance for high-throughput systems.
 */
class AsyncLogHandler extends AbstractProcessingHandler
{
    private const LOG_QUEUE_KEY = 'queue:logs:pending';

    private RedisAdapter $redis;

    private bool $fallbackToSync;

    /**
     * Create a new async log handler.
     *
     * @param  int|string  $level  The minimum logging level (default: DEBUG).
     * @param  bool  $bubble  Whether messages that are handled can bubble up the stack (default: true).
     */
    public function __construct($level = \Monolog\Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->redis = RedisAdapter::getInstance();
        $this->fallbackToSync = false;
    }

    /**
     * Write the log record to the queue.
     *
     * Serializes the log record and pushes it to the Redis list.
     * Falls back to synchronous `error_log` if Redis is unavailable.
     *
     * @param  array  $record  The log record to write.
     */
    protected function write(array $record): void
    {
        try {
            // Create log entry with required fields
            $logEntry = [
                'level' => $record['level_name'],
                'message' => $record['message'],
                'context' => $record['context'] ?? [],
                'trace_id' => $record['extra']['trace_id'] ?? null,
                'timestamp' => $record['datetime']->format('Y-m-d H:i:s'),
                'channel' => $record['channel'] ?? 'app',
            ];

            // Queue the log for async processing
            $this->redis->getClient()->lpush(self::LOG_QUEUE_KEY, [json_encode($logEntry)]);

        } catch (\Exception $e) {
            // Fallback to synchronous logging if Redis fails
            if (! $this->fallbackToSync) {
                $this->fallbackToSync = true;
                error_log('AsyncLogHandler failed, falling back to sync: '.$e->getMessage());
            }

            // Write directly to error_log as fallback
            error_log(sprintf(
                '[%s] %s: %s %s',
                $record['datetime']->format('Y-m-d H:i:s'),
                $record['level_name'],
                $record['message'],
                json_encode($record['context'] ?? [])
            ));
        }
    }
}
