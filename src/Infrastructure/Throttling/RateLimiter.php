<?php

namespace TradingPlatform\Infrastructure\Throttling;

use TradingPlatform\Infrastructure\Cache\RedisAdapter;

/**
 * Class: Rate Limiter
 *
 * Provides a generic rate limiting mechanism using Redis.
 * Useful for throttling API requests, job processing, or any action
 * that needs to be frequency-controlled.
 */
class RateLimiter
{
    private RedisAdapter $redis;

    public function __construct()
    {
        $this->redis = RedisAdapter::getInstance();
    }

    /**
     * Attempt to perform an action within the rate limits.
     *
     * @param  string  $key  Unique identifier for the action/user.
     * @param  int  $maxAttempts  Maximum allowed attempts.
     * @param  int  $decaySeconds  Time window in seconds.
     * @return bool True if allowed, false if limit exceeded.
     *
     * @example
     * ```php
     * if (! $limiter->attempt('api:user:1', 60, 60)) {
     *     throw new Exception('Too many requests');
     * }
     * ```
     */
    public function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $current = $this->redis->get($key);

        if ($current !== null && (int) $current >= $maxAttempts) {
            return false;
        }

        $this->redis->incr($key);

        if ($current === null) {
            $this->redis->expire($key, $decaySeconds);
        }

        return true;
    }
}
