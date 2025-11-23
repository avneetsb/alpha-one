<?php

use Predis\Client as PredisClient;
use TradingPlatform\Infrastructure\Cache\RedisCacheService;

class FakeRedisPredisClient extends PredisClient
{
    public array $store = [];

    public array $expiries = [];

    public ?int $lastSetexSeconds = null;

    public array $lastSetOptions = [];

    public function get(string $key)
    {
        if (isset($this->expiries[$key]) && $this->expiries[$key] < time()) {
            unset($this->store[$key], $this->expiries[$key]);

            return null;
        }

        return $this->store[$key] ?? null;
    }

    public function set(string $key, $value, ...$options)
    {
        $this->store[$key] = $value;
        $this->lastSetOptions = $options;
        if ($options && count($options) >= 2) {
            $idxEx = array_search('EX', $options, true);
            if ($idxEx !== false && isset($options[$idxEx + 1])) {
                $seconds = (int) $options[$idxEx + 1];
                $this->expiries[$key] = time() + $seconds;
            }
        }

        return 'OK';
    }

    public function setex(string $key, int $seconds, $value)
    {
        $this->store[$key] = $value;
        $this->expiries[$key] = time() + $seconds;
        $this->lastSetexSeconds = $seconds;

        return 'OK';
    }

    public function setnx(string $key, $value)
    {
        if (! array_key_exists($key, $this->store)) {
            $this->store[$key] = $value;

            return 1;
        }

        return 0;
    }

    public function incrby(string $key, $value)
    {
        $current = (int) ($this->store[$key] ?? 0);
        $current += (int) $value;
        $this->store[$key] = (string) $current;

        return $current;
    }

    public function decrby(string $key, $value)
    {
        $current = (int) ($this->store[$key] ?? 0);
        $current -= (int) $value;
        $this->store[$key] = (string) $current;

        return $current;
    }

    public function del(array $keys)
    {
        $count = 0;
        foreach ($keys as $key) {
            if (isset($this->store[$key])) {
                unset($this->store[$key], $this->expiries[$key]);
                $count++;
            }
        }

        return $count;
    }

    public function flushdb()
    {
        $this->store = [];
        $this->expiries = [];

        return 'OK';
    }
}

it('stores and retrieves numeric values', function () {
    $client = new FakeRedisPredisClient;
    $service = new RedisCacheService(['prefix' => 'test:'], $client);

    expect($service->put('n', 42))->toBeTrue();
    expect($service->get('n'))->toBe('42');
});

it('stores and retrieves complex values', function () {
    $client = new FakeRedisPredisClient;
    $service = new RedisCacheService(['prefix' => 'test:'], $client);

    $value = ['a' => 1, 'b' => ['c' => 2]];
    $service->put('obj', $value);

    expect($service->get('obj'))->toEqual($value);
});

it('adds only if not exists and supports ttl via EX NX', function () {
    $client = new FakeRedisPredisClient;
    $service = new RedisCacheService(['prefix' => 'test:'], $client);

    $added = $service->add('k', 'v', 5);
    expect($added)->toBeTrue();
    expect($client->lastSetOptions)->toContain('EX');
    expect($client->lastSetOptions)->toContain('NX');

    $addedAgain = $service->add('k', 'v2');
    expect($addedAgain)->toBeFalse();
});

it('increments and decrements counters', function () {
    $client = new FakeRedisPredisClient;
    $service = new RedisCacheService(['prefix' => 'test:'], $client);

    expect($service->increment('ctr', 2))->toBe(2);
    expect($service->increment('ctr', 3))->toBe(5);
    expect($service->decrement('ctr', 1))->toBe(4);
});

it('forgets and flushes keys', function () {
    $client = new FakeRedisPredisClient;
    $service = new RedisCacheService(['prefix' => 'test:'], $client);

    $service->put('x', 'y');
    expect($service->forget('x'))->toBeTrue();
    expect($service->get('x'))->toBeNull();

    $service->put('a', 'b');
    $service->put('c', 'd');
    expect($service->flush())->toBeTrue();
    expect($service->get('a'))->toBeNull();
    expect($service->get('c'))->toBeNull();
});

it('remembers computed value for ttl', function () {
    $client = new FakeRedisPredisClient;
    $service = new RedisCacheService(['prefix' => 'test:'], $client);

    $result = $service->remember('rem', 10, function () {
        return ['v' => 1];
    });

    expect($result)->toEqual(['v' => 1]);
    expect($service->get('rem'))->toEqual(['v' => 1]);
});
