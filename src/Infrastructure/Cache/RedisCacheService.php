<?php

namespace TradingPlatform\Infrastructure\Cache;

use TradingPlatform\Domain\Cache\CacheInterface;
use Predis\Client as RedisClient;

class RedisCacheService implements CacheInterface
{
    private RedisClient $redis;
    private string $prefix;

    public function __construct(array $config = [])
    {
        $this->redis = new RedisClient($config);
        $this->prefix = $config['prefix'] ?? 'trader:';
    }

    private function getKey(string $key): string
    {
        return $this->prefix . $key;
    }

    public function get(string $key, $default = null)
    {
        $value = $this->redis->get($this->getKey($key));

        if (is_null($value)) {
            return $default;
        }

        return $this->unserialize($value);
    }

    public function put(string $key, $value, $ttl = null): bool
    {
        $serialized = $this->serialize($value);
        $fullKey = $this->getKey($key);

        if ($ttl) {
            $seconds = $this->getSeconds($ttl);
            return (bool) $this->redis->setex($fullKey, $seconds, $serialized);
        }

        return (bool) $this->redis->set($fullKey, $serialized);
    }

    public function add(string $key, $value, $ttl = null): bool
    {
        $serialized = $this->serialize($value);
        $fullKey = $this->getKey($key);

        if ($ttl) {
            $seconds = $this->getSeconds($ttl);
            // setnx doesn't support TTL natively in older Redis, using set with NX EX
            return (bool) $this->redis->set($fullKey, $serialized, 'EX', $seconds, 'NX');
        }

        return (bool) $this->redis->setnx($fullKey, $serialized);
    }

    public function increment(string $key, $value = 1)
    {
        return $this->redis->incrby($this->getKey($key), $value);
    }

    public function decrement(string $key, $value = 1)
    {
        return $this->redis->decrby($this->getKey($key), $value);
    }

    public function forever(string $key, $value): bool
    {
        return $this->put($key, $value);
    }

    public function forget(string $key): bool
    {
        return (bool) $this->redis->del([$this->getKey($key)]);
    }

    public function flush(): bool
    {
        return (bool) $this->redis->flushdb();
    }

    public function remember(string $key, $ttl, \Closure $callback)
    {
        $value = $this->get($key);

        if (!is_null($value)) {
            return $value;
        }

        $value = $callback();

        $this->put($key, $value, $ttl);

        return $value;
    }

    private function serialize($value): string
    {
        return is_numeric($value) ? $value : serialize($value);
    }

    private function unserialize($value)
    {
        return is_numeric($value) ? $value : unserialize($value);
    }

    private function getSeconds($ttl): int
    {
        if ($ttl instanceof \DateTimeInterface) {
            return $ttl->getTimestamp() - time();
        }

        if ($ttl instanceof \DateInterval) {
            return (int) $ttl->format('%s');
        }

        return (int) $ttl;
    }
}
