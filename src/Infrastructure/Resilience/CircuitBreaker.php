<?php

namespace TradingPlatform\Infrastructure\Resilience;

use TradingPlatform\Infrastructure\Cache\RedisAdapter;

class CircuitBreaker
{
    private RedisAdapter $redis;
    private string $serviceName;
    private int $failureThreshold;
    private int $timeoutSeconds;

    public function __construct(string $serviceName, int $failureThreshold = 5, int $timeoutSeconds = 60)
    {
        $this->redis = RedisAdapter::getInstance();
        $this->serviceName = $serviceName;
        $this->failureThreshold = $failureThreshold;
        $this->timeoutSeconds = $timeoutSeconds;
    }

    public function isAvailable(): bool
    {
        $state = $this->redis->get("circuit:{$this->serviceName}:state");
        return $state !== 'OPEN';
    }

    public function reportSuccess(): void
    {
        $this->redis->set("circuit:{$this->serviceName}:failures", '0');
        $this->redis->set("circuit:{$this->serviceName}:state", 'CLOSED');
    }

    public function reportFailure(): void
    {
        $failures = $this->redis->getClient()->incr("circuit:{$this->serviceName}:failures");
        
        if ($failures >= $this->failureThreshold) {
            $this->redis->set("circuit:{$this->serviceName}:state", 'OPEN', $this->timeoutSeconds);
        }
    }
}
