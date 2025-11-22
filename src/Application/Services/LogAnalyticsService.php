<?php

namespace TradingPlatform\Application\Services;

use TradingPlatform\Infrastructure\Cache\RedisAdapter;
use TradingPlatform\Infrastructure\Logger\LoggerService;

/**
 * Log analytics and processing pipeline
 */
class LogAnalyticsService
{
    private RedisAdapter $redis;
    private \Monolog\Logger $logger;
    
    private const ERROR_PATTERN_KEY = 'analytics:log:error_patterns';
    private const PERF_METRICS_KEY = 'analytics:log:performance';
    private const SECURITY_EVENTS_KEY = 'analytics:log:security';

    public function __construct()
    {
        $this->redis = RedisAdapter::getInstance();
        $this->logger = LoggerService::getInstance()->getLogger();
    }

    /**
     * Process log entry for analytics
     */
    public function processLogEntry(array $logEntry): void
    {
        // Extract error patterns
        if (in_array($logEntry['level'] ?? '', ['ERROR', 'CRITICAL'])) {
            $this->trackErrorPattern($logEntry);
        }

        // Extract performance metrics
        if (isset($logEntry['context']['duration_ms'])) {
            $this->trackPerformanceMetric($logEntry);
        }

        // Detect security events
        if ($this->isSecurityEvent($logEntry)) {
            $this->trackSecurityEvent($logEntry);
        }

        // Check for alert conditions
        $this->checkAlertConditions($logEntry);
    }

    private function trackErrorPattern(array $logEntry): void
    {
        $pattern = $this->extractErrorPattern($logEntry);
        
        // Increment error count for this pattern
        $key = self::ERROR_PATTERN_KEY . ':' . md5($pattern);
        $this->redis->getClient()->hincrby($key, 'count', 1);
        $this->redis->getClient()->hset($key, 'last_seen', time());
        $this->redis->getClient()->hset($key, 'pattern', $pattern);
        $this->redis->getClient()->expire($key, 86400); // 24 hour TTL
    }

    private function extractErrorPattern(array $logEntry): string
    {
        $message = $logEntry['message'] ?? '';
        
        // Normalize error message to extract pattern
        // Remove dynamic parts like IDs, timestamps, numbers
        $pattern = preg_replace('/\d+/', 'N', $message);
        $pattern = preg_replace('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', 'UUID', $pattern);
        
        return substr($pattern, 0, 200); // Limit length
    }

    private function trackPerformanceMetric(array $logEntry): void
    {
        $operation = $logEntry['context']['operation'] ?? 'unknown';
        $duration = $logEntry['context']['duration_ms'] ?? 0;
        
        // Store in time-series format for trending
        $timestamp = time();
        $key = self::PERF_METRICS_KEY . ':' . $operation;
        
        $this->redis->getClient()->zadd($key, [$timestamp => $duration]);
        $this->redis->getClient()->expire($key, 86400); // 24 hour retention
    }

    private function isSecurityEvent(array $logEntry): bool
    {
       $message = strtolower($logEntry['message'] ?? '');
        $securityKeywords = ['unauthorized', 'forbidden', 'auth failed', 'invalid token', 'brute force', 'sql injection'];
        
        foreach ($securityKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }
        
        return false;
    }

    private function trackSecurityEvent(array $logEntry): void
    {
        $event = [
            'timestamp' => time(),
            'message' => $logEntry['message'] ?? '',
            'ip' => $logEntry['context']['ip'] ?? 'unknown',
            'user_id' => $logEntry['context']['user_id'] ?? null,
        ];
        
        $this->redis->getClient()->lpush(self::SECURITY_EVENTS_KEY, [json_encode($event)]);
        $this->redis->getClient()->ltrim(self::SECURITY_EVENTS_KEY, 0, 999); // Keep last 1000
        
        // Alert on security events
        $this->logger->alert('Security event detected', $event);
    }

    private function checkAlertConditions(array $logEntry): void
    {
        // Check error rate threshold
        $errorCount = $this->redis->getClient()->hget(self::ERROR_PATTERN_KEY . ':count', 'total') ?? 0;
        
        if ($errorCount > 100) { // Alert if more than 100 errors in window
            $this->logger->alert('High error rate detected', [
                'error_count' => $errorCount,
                'window' => '1 minute'
            ]);
        }
    }

    /**
     * Get error patterns for analysis
     */
    public function getErrorPatterns(int $limit = 10): array
    {
        // This is a simplified implementation
        // In production, would query Redis sorted sets
        return [];
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(string $operation, int $hours = 24): array
    {
        $key = self::PERF_METRICS_KEY . ':' . $operation;
        $fromTime = time() - ($hours * 3600);
        
        $metrics = $this->redis->getClient()->zrangebyscore($key, $fromTime, '+inf', ['withscores' => true]);
        
        if (empty($metrics)) {
            return [];
        }

        // Calculate p50, p95, p99
        $durations = array_values($metrics);
        sort($durations);
        
        return [
            'count' => count($durations),
            'p50' => $this->percentile($durations, 50),
            'p95' => $this->percentile($durations, 95),
            'p99' => $this->percentile($durations, 99),
            'max' => max($durations),
        ];
    }

    private function percentile(array $values, float $percentile): float
    {
        $count = count($values);
        if ($count === 0) return 0;
        
        $index = ceil(($percentile / 100) * $count) - 1;
        return $values[max(0, min($index, $count - 1))];
    }
}
