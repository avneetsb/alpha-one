<?php

namespace TradingPlatform\Infrastructure\Cache;

use Predis\Client;

/**
 * Class: Redis Adapter
 *
 * Singleton wrapper around the Predis client.
 * Provides a centralized point for Redis connection management and common operations
 * like caching, sets, and distributed locking.
 */
class RedisAdapter
{
    private static ?RedisAdapter $instance = null;

    private Client $client;

    private function __construct()
    {
        $this->client = new Client([
            'scheme' => 'tcp',
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', null),
        ]);
    }

    /**
     * Get the singleton instance of the adapter.
     *
     * @return RedisAdapter The shared adapter instance.
     */
    public static function getInstance(): RedisAdapter
    {
        if (self::$instance === null) {
            self::$instance = new RedisAdapter;
        }

        return self::$instance;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function set(string $key, string $value, ?int $ttl = null): void
    {
        if ($ttl) {
            $this->client->setex($key, $ttl, $value);
        } else {
            $this->client->set($key, $value);
        }
    }

    public function get(string $key): ?string
    {
        return $this->client->get($key);
    }

    public function sadd(string $key, array $members): void
    {
        if (! empty($members)) {
            $this->client->sadd($key, $members);
        }
    }

    public function srem(string $key, array $members): void
    {
        if (! empty($members)) {
            $this->client->srem($key, $members);
        }
    }

    public function smembers(string $key): array
    {
        return $this->client->smembers($key);
    }

    /**
     * Acquire a distributed lock.
     *
     * Uses the "RedLock" pattern (simplified) by setting a key with NX (only if not exists)
     * and EX (expiration) options.
     *
     * @param  string  $key  The lock key identifier.
     * @param  int  $ttl  Time to live in seconds (default: 10).
     * @return bool True if lock acquired, false otherwise.
     *
     * @example
     * ```php
     * if ($redis->acquireLock('order_processing_123')) {
     *     // Process order...
     *     $redis->releaseLock('order_processing_123');
     * }
     * ```
     */
    public function acquireLock(string $key, int $ttl = 10): bool
    {
        $token = uniqid();
        $lockKey = "lock:{$key}";

        // SET lockKey token NX EX ttl
        $result = $this->client->set($lockKey, $token, 'EX', $ttl, 'NX');

        return $result !== null;
    }

    public function releaseLock(string $key): void
    {
        $lockKey = "lock:{$key}";
        $this->client->del([$lockKey]);
    }
}
