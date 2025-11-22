<?php

namespace TradingPlatform\Infrastructure\Coordination;

use TradingPlatform\Infrastructure\Cache\RedisAdapter;

class LeaderElection
{
    private RedisAdapter $redis;
    private string $resource;
    private string $ownerId;
    private int $ttl;

    public function __construct(string $resource, int $ttl = 30)
    {
        $this->redis = RedisAdapter::getInstance();
        $this->resource = "leader:{$resource}";
        $this->ownerId = uniqid(gethostname() . '-', true);
        $this->ttl = $ttl;
    }

    public function acquire(): bool
    {
        // SET resource ownerId NX EX ttl
        // Predis set arguments: key, value, 'EX', ttl, 'NX'
        $client = $this->redis->getClient();
        $result = $client->set($this->resource, $this->ownerId, 'EX', $this->ttl, 'NX');
        
        return $result !== null;
    }

    public function release(): void
    {
        $client = $this->redis->getClient();
        // Lua script to safely release lock only if we own it
        $script = '
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
        ';
        
        $client->eval($script, 1, $this->resource, $this->ownerId);
    }

    public function keepAlive(): bool
    {
        $client = $this->redis->getClient();
        // Lua script to extend TTL only if we own it
        $script = '
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("EXPIRE", KEYS[1], ARGV[2])
            else
                return 0
            end
        ';
        
        return (bool) $client->eval($script, 1, $this->resource, $this->ownerId, $this->ttl);
    }
}
