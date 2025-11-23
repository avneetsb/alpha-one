<?php

namespace TradingPlatform\Application\Services;

use TradingPlatform\Infrastructure\Cache\RedisAdapter;

/**
 * Class QueueMetricsService
 *
 * Advanced queue metrics and monitoring.
 * Tracks queue depth, latency, and provides auto-scaling recommendations based
 * on real-time load. Uses Redis time-series data for trend analysis.
 *
 * @version 1.0.0
 */
class QueueMetricsService
{
    private RedisAdapter $redis;

    private const METRICS_KEY_PREFIX = 'metrics:queue:';

    /**
     * QueueMetricsService constructor.
     */
    public function __construct()
    {
        $this->redis = RedisAdapter::getInstance();
    }

    /**
     * Record queue depth.
     *
     * Captures the current number of pending jobs in a specific queue.
     *
     * @param  string  $queueName  Name of the queue (e.g., 'orders', 'notifications').
     * @param  int  $depth  Current number of jobs.
     *
     * @example Recording depth
     * ```php
     * $service->recordQueueDepth('orders', 150);
     * ```
     */
    public function recordQueueDepth(string $queueName, int $depth): void
    {
        $key = self::METRICS_KEY_PREFIX.'depth:'.$queueName;
        $this->redis->getClient()->zadd($key, [time() => $depth]);
        $this->redis->getClient()->expire($key, 86400); // 24hr retention
    }

    /**
     * Record processing latency.
     *
     * Captures the time taken to process a job in the queue.
     *
     * @param  string  $queueName  Name of the queue.
     * @param  float  $latencyMs  Processing time in milliseconds.
     *
     * @example Recording latency
     * ```php
     * $service->recordProcessingLatency('orders', 45.5);
     * ```
     */
    public function recordProcessingLatency(string $queueName, float $latencyMs): void
    {
        $key = self::METRICS_KEY_PREFIX.'latency:'.$queueName;
        $this->redis->getClient()->zadd($key, [time() => $latencyMs]);
        $this->redis->getClient()->expire($key, 86400);
    }

    /**
     * Get queue metrics for a specified period.
     *
     * Aggregates depth and latency metrics to provide statistical insights
     * (min, max, avg, p95, p99).
     *
     * @param  string  $queueName  Name of the queue.
     * @param  int  $hours  Lookback period in hours (default: 1).
     * @return array Metrics including depth and latency stats.
     *
     * @example Fetching metrics
     * ```php
     * $metrics = $service->getQueueMetrics('orders');
     * echo "Avg Depth: " . $metrics['depth']['avg'];
     * echo "P95 Latency: " . $metrics['latency_ms']['p95'];
     * ```
     */
    public function getQueueMetrics(string $queueName, int $hours = 1): array
    {
        $fromTime = time() - ($hours * 3600);

        $depthKey = self::METRICS_KEY_PREFIX.'depth:'.$queueName;
        $latencyKey = self::METRICS_KEY_PREFIX.'latency:'.$queueName;

        $depthData = $this->redis->getClient()->zrangebyscore($depthKey, $fromTime, '+inf', ['withscores' => true]);
        $latencyData = $this->redis->getClient()->zrangebyscore($latencyKey, $fromTime, '+inf', ['withscores' => true]);

        return [
            'queue_name' => $queueName,
            'depth' => $this->calculateStats(array_values($depthData)),
            'latency_ms' => $this->calculateStats(array_values($latencyData)),
        ];
    }

    /**
     * Calculate statistics from a list of values.
     *
     * @return array Stats (min, max, avg, p95, p99).
     */
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
            'p95' => $values[(int) ($count * 0.95)],
            'p99' => $values[(int) ($count * 0.99)],
        ];
    }

    /**
     * Check if auto-scaling is needed based on metrics.
     *
     * Analyzes current load against thresholds to recommend scaling actions.
     *
     * @param  string  $queueName  Name of the queue.
     * @return string|null 'SCALE_UP', 'SCALE_DOWN', or null.
     *
     * @example Auto-scaling check
     * ```php
     * $action = $service->shouldAutoScale('orders');
     * if ($action === 'SCALE_UP') {
     *     // Trigger worker provisioning
     * }
     * ```
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
