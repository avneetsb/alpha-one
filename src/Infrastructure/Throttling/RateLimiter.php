<?php

namespace TradingPlatform\Infrastructure\Throttling;

use TradingPlatform\Infrastructure\Cache\RedisAdapter;

class RateLimiter
{
    private RedisAdapter $redis;

    public function __construct()
    {
        $this->redis = RedisAdapter::getInstance();
    }

    public function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $current = $this->redis->get($key);
        
        if ($current !== null && (int)$current >= $maxAttempts) {
            return false;
        }

        $this->redis->incr($key);
        
        if ($current === null) {
            $this->redis->expire($key, $decaySeconds);
        }

        return true;
    }
}
