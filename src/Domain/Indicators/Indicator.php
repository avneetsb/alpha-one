<?php

namespace TradingPlatform\Domain\Indicators;

/**
 * Technical Indicator Base
 *
 * Provides common utilities for indicator implementations including caching
 * and OHLCV value extraction helpers. Concrete indicators should implement
 * the `calculate` method and may leverage internal caching to avoid
 * recomputation for identical inputs.
 *
 * @version 1.0.0
 */
abstract class Indicator
{
    protected array $cache = [];

    protected string $cacheKey;

    public function __construct()
    {
        $this->cacheKey = static::class;
    }

    /**
     * Calculate indicator values for given OHLCV dataset.
     *
     * @param  array  $data  Array of candles: [{open,high,low,close,volume}, ...]
     * @param  array  $params  Indicator-specific parameters
     * @return array Calculated values (shape defined by indicator)
     */
    abstract public function calculate(array $data, array $params = []): array;

    /**
     * Get cached result if available.
     */
    protected function getCached(string $key): ?array
    {
        return $this->cache[$key] ?? null;
    }

    /**
     * Cache result.
     */
    protected function setCached(string $key, array $result): void
    {
        $this->cache[$key] = $result;
    }

    /**
     * Extract close prices from OHLCV data.
     */
    protected function extractClosePrices(array $data): array
    {
        return array_column($data, 'close');
    }

    /**
     * Extract high prices.
     */
    protected function extractHighPrices(array $data): array
    {
        return array_column($data, 'high');
    }

    /**
     * Extract low prices.
     */
    protected function extractLowPrices(array $data): array
    {
        return array_column($data, 'low');
    }

    /**
     * Extract volumes.
     */
    protected function extractVolumes(array $data): array
    {
        return array_column($data, 'volume');
    }
}
