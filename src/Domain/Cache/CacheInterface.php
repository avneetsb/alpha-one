<?php

namespace TradingPlatform\Domain\Cache;

interface CacheInterface
{
    /**
     * Get an item from the cache.
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Store an item in the cache.
     *
     * @param  mixed  $value
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return bool
     */
    public function put(string $key, $value, $ttl = null);

    /**
     * Store an item in the cache if the key does not exist.
     *
     * @param  mixed  $value
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return bool
     */
    public function add(string $key, $value, $ttl = null);

    /**
     * Increment the value of an item in the cache.
     *
     * @param  mixed  $value
     * @return int|bool
     */
    public function increment(string $key, $value = 1);

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  mixed  $value
     * @return int|bool
     */
    public function decrement(string $key, $value = 1);

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function forever(string $key, $value);

    /**
     * Remove an item from the cache.
     *
     * @return bool
     */
    public function forget(string $key);

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush();

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return mixed
     */
    public function remember(string $key, $ttl, \Closure $callback);
}
