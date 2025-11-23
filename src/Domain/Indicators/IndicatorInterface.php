<?php

namespace TradingPlatform\Domain\Indicators;

use TradingPlatform\Domain\MarketData\Models\Candle;

/**
 * Interface IndicatorInterface
 *
 * Defines the contract for all technical indicators.
 * Ensures consistent calculation interface across momentum, trend,
 * volatility, and volume indicators.
 *
 * **Indicator Types:**
 * - Momentum: RSI, Stochastic, MACD
 * - Trend: SMA, EMA, SuperTrend
 * - Volatility: ATR, Bollinger Bands
 * - Volume: VWAP, Volume Profile
 *
 * **Implementation Pattern:**
 * 1. Implement calculate() with indicator logic
 * 2. Define unique ID for caching
 * 3. Specify configuration parameters
 * 4. Validate configuration on initialization
 *
 * **Calculation Requirements:**
 * - Stateless: Same inputs produce same outputs
 * - Efficient: Minimize redundant calculations
 * - Accurate: Match industry-standard formulas
 * - Documented: Clear parameter meanings
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Implementing a simple indicator
 * ```php
 * class SimpleMovingAverage implements IndicatorInterface
 * {
 *     private int $period;
 *
 *     public function __construct(int $period = 20)
 *     {
 *         $this->period = $period;
 *     }
 *
 *     public function calculate(Candle $candle, array $previousCandles)
 *     {
 *         $prices = array_map(fn($c) => $c->close, $previousCandles);
 *         $prices[] = $candle->close;
 *
 *         return array_sum(array_slice($prices, -$this->period)) / $this->period;
 *     }
 *
 *     public function getId(): string
 *     {
 *         return "SMA_{$this->period}";
 *     }
 *
 *     public function getConfig(): array
 *     {
 *         return ['period' => $this->period];
 *     }
 *
 *     public function validateConfig(array $config): bool
 *     {
 *         return isset($config['period']) && $config['period'] > 0;
 *     }
 * }
 * ```
 */
interface IndicatorInterface
{
    /**
     * Calculate the indicator value for the given candle
     *
     * @param  Candle  $candle  The latest candle
     * @param  array  $previousCandles  Historical candles needed for calculation
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
