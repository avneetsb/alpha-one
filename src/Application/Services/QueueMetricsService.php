<?php

namespace TradingPlatform\Application\Services;

use TradingPlatform\Infrastructure\Cache\RedisAdapter;

/**
 * Advanced queue metrics and monitoring
 */
class QueueMetricsService
{
    private RedisAdapter $redis;
    private const METRICS_KEY_PREFIX = 'metrics:queue:';

    public function __construct()
    {
        $this->redis = RedisAdapter::getInstance();
    }

    /**
     * Record queue depth
     */
    public function recordQueueDepth(string $queueName, int $depth): void
    {
        $key = self::METRICS_KEY_PREFIX . 'depth:' . $queueName;
        $this->redis->getClient()->zadd($key, [time() => $depth]);
        $this->redis->getClient()->expire($key, 86400); // 24hr retention
    }

    /**
     * Record processing latency
     */
    public function recordProcessingLatency(string $queueName, float $latencyMs): void
    {
        $key = self::METRICS_KEY_PREFIX . 'latency:' . $queueName;
        $this->redis->getClient()->zadd($key, [time() => $latencyMs]);
        $this->redis->getClient()->expire($key, 86400);
    }

    /**
     * Get queue metrics
     */
    public function getQueueMetrics(string $queueName, int $hours = 1): array
    {
        $fromTime = time() - ($hours * 3600);
        
        $depthKey = self::METRICS_KEY_PREFIX . 'depth:' . $queueName;
        $latencyKey = self::METRICS_KEY_PREFIX . 'latency:' . $queueName;

        $depthData = $this->redis->getClient()->zrangebyscore($depthKey, $fromTime, '+inf', ['withscores' => true]);
        $latencyData = $this->redis->getClient()->zrangebyscore($latencyKey, $fromTime, '+inf', ['withscores' => true]);

        return [
            'queue_name' => $queueName,
            'depth' => $this->calculateStats(array_values($depthData)),
            'latency_ms' => $this->calculateStats(array_values($latencyData)),
        ];
    }

    private function calculateStats(array $values): array
    {
        if (empty($values)) {
            return ['min' => 0, 'max' => 0, 'avg' => 0, 'p95' => 0, 'p99' => 0];
        }

        sort($values);
        $count = count($values);

        return [
            'min' => min($values),
            'max' => max($values),
            'avg' => array_sum($values) / $count,
            'p95' => $values[(int)($count * 0.95)],
            'p99' => $values[(int)($count * 0.99)],
        ];
    }

    /**
     * Check if auto-scaling is needed
     */
    public function shouldAutoScale(string $queueName): ?string
    {
        $metrics = $this->getQueueMetrics($queueName);
        
        // Scale up if depth > 1000 or p95 latency > 5000ms
        if ($metrics['depth']['avg'] > 1000 || $metrics['latency_ms']['p95'] > 5000) {
            return 'SCALE_UP';
        }

        // Scale down if depth < 100 and p95 latency < 500ms
        if ($metrics['depth']['avg'] < 100 && $metrics['latency_ms']['p95'] < 500) {
            return 'SCALE_DOWN';
        }

        return null;
    }
}
