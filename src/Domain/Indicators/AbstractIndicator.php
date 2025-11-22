<?php

namespace TradingPlatform\Domain\Indicators;

use TradingPlatform\Domain\MarketData\Models\Candle;

abstract class AbstractIndicator implements IndicatorInterface
{
    protected string $id;
    protected array $config;

    public function __construct(array $config = [])
    {
        if (!$this->validateConfig($config)) {
            throw new \InvalidArgumentException("Invalid configuration for indicator " . static::class);
        }
        $this->config = $config;
        $this->id = uniqid(static::class . '_');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    abstract public function validateConfig(array $config): bool;
}
