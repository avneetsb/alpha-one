<?php

namespace TradingPlatform\Infrastructure\Logger;

use Monolog\Handler\AbstractProcessingHandler;
use TradingPlatform\Infrastructure\Cache\RedisAdapter;

/**
 * Async log handler that queues logs for background processing
 */
class AsyncLogHandler extends AbstractProcessingHandler
{
    private const LOG_QUEUE_KEY = 'queue:logs:pending';
    private RedisAdapter $redis;
    private bool $fallbackToSync;

    public function __construct($level = \Monolog\Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->redis = RedisAdapter::getInstance();
        $this->fallbackToSync = false;
    }

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
            if (!$this->fallbackToSync) {
                $this->fallbackToSync = true;
                error_log("AsyncLogHandler failed, falling back to sync: " . $e->getMessage());
            }
            
            // Write directly to error_log as fallback
            error_log(sprintf(
                "[%s] %s: %s %s",
                $record['datetime']->format('Y-m-d H:i:s'),
                $record['level_name'],
                $record['message'],
                json_encode($record['context'] ?? [])
            ));
        }
    }
}
