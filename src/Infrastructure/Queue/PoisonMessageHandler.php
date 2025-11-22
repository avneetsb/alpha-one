<?php

namespace TradingPlatform\Infrastructure\Queue;

use TradingPlatform\Infrastructure\Cache\RedisAdapter;
use Illuminate\Support\Facades\Log;

class PoisonMessageHandler
{
    private RedisAdapter $redis;
    private int $maxRetries;

    public function __construct(int $maxRetries = 3)
    {
        $this->redis = RedisAdapter::getInstance();
        $this->maxRetries = $maxRetries;
    }

    public function handleFailure(string $jobId, string $payload, string $queue): void
    {
        $retryKey = "job:retries:{$jobId}";
        $retries = $this->redis->getClient()->incr($retryKey);

        if ($retries > $this->maxRetries) {
            $this->moveToDlq($jobId, $payload, $queue, "Max retries exceeded");
            $this->redis->getClient()->del([$retryKey]);
        }
    }

    private function moveToDlq(string $jobId, string $payload, string $originQueue, string $reason): void
    {
        $dlqKey = "queue:dlq:{$originQueue}";
        $dlqPayload = json_encode([
            'original_job_id' => $jobId,
            'payload' => $payload,
            'reason' => $reason,
            'failed_at' => date('Y-m-d H:i:s'),
        ]);

        $this->redis->getClient()->rpush($dlqKey, [$dlqPayload]);
        
        // Log the event
        // In a real app, we'd use the LoggerService
        // echo "Moved job $jobId to DLQ: $dlqKey\n";
    }
}
