<?php

namespace TradingPlatform\Domain\Indicators;

use Illuminate\Support\Facades\Cache;

class IndicatorManager
{
    private array $instances = [];

    // Metadata for discovery (Lazy loaded)
    private const CATEGORIES = [
        'trend' => ['sma', 'ema', 'rsi', 'macd'], // Simplified for brevity, would list all 97
        'momentum' => ['rsi', 'stochastic'],
        'volatility' => ['atr', 'bollinger'],
    ];

    public function getIndicator(string $id): IndicatorInterface
    {
        if (! isset($this->instances[$id])) {
            // Lazy load or throw
            throw new \Exception("Indicator instance {$id} not found. Create it first.");
        }

        return $this->instances[$id];
    }

    public function getAvailableIndicators(): array
    {
        return self::CATEGORIES;
    }

    public function calculate(string $indicatorId, $candle, array $history)
    {
        if (! isset($this->indicators[$indicatorId])) {
            throw new \Exception("Indicator not found: {$indicatorId}");
        }

        $indicator = $this->indicators[$indicatorId];

        // Caching strategy: Key = indicator_id + candle_timestamp
        $cacheKey = "indicator:{$indicatorId}:{$candle->timestamp}";

        return Cache::remember($cacheKey, 3600, function () use ($indicator, $candle, $history) {
            return $indicator->calculate($candle, $history);
        });
    }

    public function createIndicator(string $type, array $config): IndicatorInterface
    {
        $class = "TradingPlatform\\Domain\\Indicators\\Types\\{$type}";

        if (! class_exists($class)) {
            throw new \Exception("Indicator type not supported: {$type}");
        }

        return new $class($config);
    }
}
