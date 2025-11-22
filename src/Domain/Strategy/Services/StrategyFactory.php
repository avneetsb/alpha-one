<?php

namespace TradingPlatform\Domain\Strategy\Services;

class StrategyFactory
{
    public function create(string $name, array $config)
    {
        // Mock factory
        return new class($config) {
            public function __construct(private array $config) {}
        };
    }
}
