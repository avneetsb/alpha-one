<?php

namespace TradingPlatform\Domain\Indicators;

use TradingPlatform\Domain\MarketData\Models\Candle;

interface IndicatorInterface
{
    /**
     * Calculate the indicator value for the given candle
     * 
     * @param Candle $candle The latest candle
     * @param array $previousCandles Historical candles needed for calculation
     * @return float|array The calculated value(s)
     */
    public function calculate(Candle $candle, array $previousCandles);

    /**
     * Get the unique identifier for this indicator instance
     */
    public function getId(): string;

    /**
     * Get the configuration parameters
     */
    public function getConfig(): array;

    /**
     * Validate configuration parameters
     */
    public function validateConfig(array $config): bool;
}
