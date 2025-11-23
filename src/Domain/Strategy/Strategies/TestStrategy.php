<?php

namespace TradingPlatform\Domain\Strategy\Strategies;

use TradingPlatform\Domain\MarketData\Candle;
use TradingPlatform\Domain\MarketData\Tick;
use TradingPlatform\Domain\Strategy\AbstractStrategy;
use TradingPlatform\Domain\Strategy\Signal;

/**
 * Test Strategy
 *
 * A simple strategy for verifying system connectivity and execution flow.
 * Generates signals based on trivial logic (price threshold) to test
 * the order lifecycle without complex market analysis.
 *
 * **Purpose:**
 * - System health checks
 * - Pipeline verification (Tick -> Strategy -> Signal -> Order)
 * - Latency testing
 *
 * **Logic:**
 * - BUY if price > 100
 * - Includes fixed Stop Loss (95) and Take Profit (105)
 *
 * @version 1.0.0
 */
class TestStrategy extends AbstractStrategy
{
    /**
     * Process a new tick and generate test signals.
     *
     * Evaluates the incoming tick against a simple price threshold to generate
     * a BUY signal. This method is used to verify that the strategy pipeline
     * correctly receives ticks and can produce valid Signal objects.
     *
     * @param  Tick  $tick  The incoming market tick.
     * @return Signal|null A BUY signal if price > 100, otherwise null.
     *
     * @example Signal generation
     * ```php
     * $tick = new Tick(['price' => 101.5, 'instrument_id' => 1]);
     * $signal = $strategy->onTick($tick);
     * // Returns Signal object with side=BUY
     * ```
     */
    public function onTick(Tick $tick): ?Signal
    {
        // Simple logic: if price > 100, buy
        if ($tick->price > 100) {
            return new Signal(
                $tick->instrument_id,
                Signal::BUY,
                $tick->price,
                1,
                $this->getName(),
                95.0,
                105.0
            );
        }

        return null;
    }

    /**
     * Process a candle (not used in this test strategy).
     *
     * This method is implemented to satisfy the AbstractStrategy contract but
     * performs no logic for this specific test strategy.
     *
     * @param  Candle  $candle  The incoming candle.
     * @return Signal|null Always null.
     */
    public function onCandle(Candle $candle): ?Signal
    {
        return null;
    }
}
