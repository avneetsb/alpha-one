<?php

namespace TradingPlatform\Infrastructure\Resilience;

use TradingPlatform\Infrastructure\Cache\RedisAdapter;

/**
 * Class: Circuit Breaker
 *
 * Implements the Circuit Breaker pattern to prevent cascading failures.
 * Monitors service failures and "opens" the circuit (stops requests) when
 * a threshold is reached, allowing the failing service time to recover.
 */
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

    /**
     * Record a failure event.
     *
     * Increments the failure count. If the threshold is exceeded,
     * opens the circuit for the specified timeout duration.
     */
    public function reportFailure(): void
    {
        $failures = $this->redis->getClient()->incr("circuit:{$this->serviceName}:failures");

        if ($failures >= $this->failureThreshold) {
            $this->redis->set("circuit:{$this->serviceName}:state", 'OPEN', $this->timeoutSeconds);
        }
    }
}
