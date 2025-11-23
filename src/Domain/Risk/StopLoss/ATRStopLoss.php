<?php

namespace TradingPlatform\Domain\Risk\StopLoss;

/**
 * Class ATRStopLoss
 *
 * Implements a volatility-based stop loss using Average True Range (ATR).
 * Unlike fixed-percentage stops, ATR stops adapt to market volatility,
 * providing tighter stops in calm markets and wider stops in volatile markets.
 *
 * **How It Works:**
 * Stop distance = ATR × Multiplier
 * - Higher volatility (larger ATR) → Wider stop (more room)
 * - Lower volatility (smaller ATR) → Tighter stop (less room)
 *
 * **Benefits:**
 * - Adapts to changing market conditions
 * - Reduces whipsaws in volatile markets
 * - Tighter stops in calm markets for better risk/reward
 * - Widely used by professional traders
 *
 * **Modes:**
 * - Static: Stop price set at entry, doesn't move
 * - Trailing: Stop price adjusts as market moves favorably
 *
 * **Use Cases:**
 * - Trend-following in volatile markets
 * - Swing trading with dynamic risk management
 * - Breakout strategies
 * - Position sizing based on volatility
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Long Position with 2x ATR Stop
 * ```php
 * use TradingPlatform\Domain\Risk\StopLoss\ATRStopLoss;
 *
 * // Buy AAPL at ₹100, ATR = 3.0, use 2x ATR stop
 * $stop = new ATRStopLoss('BUY', 100.0, 3.0, 2.0, false);
 * echo "Stop: ₹" . $stop->getStopPrice();  // ₹94.00 (100 - 2×3)
 *
 * // If volatility increases, update ATR
 * $stop->updateATR(4.0);  // ATR increased to 4.0
 * // Stop would be ₹92.00 for new positions (100 - 2×4)
 * ```
 * @example Trailing ATR Stop
 * ```php
 * // Buy at ₹100, ATR = 2.5, 2x multiplier, trailing enabled
 * $stop = new ATRStopLoss('BUY', 100.0, 2.5, 2.0, true);
 * echo "Initial stop: ₹" . $stop->getStopPrice();  // ₹95.00
 *
 * // Price moves to ₹110
 * $stop->updatePrice(110.0);
 * echo "Updated stop: ₹" . $stop->getStopPrice();  // ₹105.00 (trails up)
 *
 * // ATR decreases (less volatility)
 * $stop->updateATR(2.0);
 * $stop->updatePrice(115.0);
 * echo "Tighter stop: ₹" . $stop->getStopPrice();  // ₹111.00 (115 - 2×2)
 * ```
 * @example Comparing ATR vs Fixed Percentage
 * ```php
 * // Volatile stock: ATR = 5.0, Price = 100
 * $atrStop = new ATRStopLoss('BUY', 100.0, 5.0, 2.0);
 * // Stop at ₹90 (10% below) - adapts to volatility
 *
 * // Calm stock: ATR = 1.0, Price = 100
 * $atrStop2 = new ATRStopLoss('BUY', 100.0, 1.0, 2.0);
 * // Stop at ₹98 (2% below) - tighter for less volatile stock
 *
 * // Fixed 5% stop doesn't adapt:
 * $fixedStop = new TrailingStopLoss('BUY', 100.0, 0.05);
 * // Always ₹95 regardless of volatility
 * ```
 *
 * @see TrailingStopLoss For percentage-based trailing stops
 * @see VolatilityIndicators For ATR calculation
 */
class ATRStopLoss
{
    /**
     * @var float ATR multiplier (e.g., 2.0 for 2x ATR)
     */
    private float $multiplier;

    /**
     * @var float Current Average True Range value
     */
    private float $currentATR;

    /**
     * @var float Current stop price
     */
    private float $currentStopPrice;

    /**
     * @var string Position side: 'BUY' or 'SELL'
     */
    private string $side;

    /**
     * @var bool Whether stop trails (true) or stays fixed (false)
     */
    private bool $isTrailing;

    /**
     * Initialize ATR-based stop loss.
     *
     * Creates a stop loss based on the Average True Range, which adapts to
     * market volatility. The stop distance is calculated as ATR × multiplier.
     *
     * **Stop Distance Calculation:**
     * Distance = ATR × Multiplier
     *
     * **Common Multipliers:**
     * - 1.0-1.5: Tight stops, frequent exits
     * - 2.0-3.0: Standard range, balanced
     * - 3.0-4.0: Wide stops, trend-following
     *
     * **Trailing vs Static:**
     * - Static (isTrailing=false): Stop set at entry, never moves
     * - Trailing (isTrailing=true): Stop adjusts as price moves favorably
     *
     * @param  string  $side  Position side: 'BUY' for long, 'SELL' for short
     * @param  float  $entryPrice  Entry price of the position
     * @param  float  $initialATR  Current ATR value (in price units)
     * @param  float  $multiplier  ATR multiplier (default: 2.0)
     *                             Typical range: 1.0 to 4.0
     * @param  bool  $isTrailing  Whether stop should trail (default: false)
     *
     * @example Conservative 1.5x ATR stop
     * ```php
     * $stop = new ATRStopLoss('BUY', 1000.0, 10.0, 1.5, false);
     * // Stop at ₹985 (1000 - 1.5×10)
     * // Tighter stop, exits quickly
     * ```
     * @example Aggressive 3x ATR trailing stop
     * ```php
     * $stop = new ATRStopLoss('BUY', 500.0, 8.0, 3.0, true);
     * // Initial stop at ₹476 (500 - 3×8)
     * // Wide stop for trend-following, trails as price moves
     * ```
     */
    public function __construct(string $side, float $entryPrice, float $initialATR, float $multiplier = 2.0, bool $isTrailing = false)
    {
        $this->side = strtoupper($side);
        $this->multiplier = $multiplier;
        $this->currentATR = $initialATR;
        $this->isTrailing = $isTrailing;

        $this->updateStopPrice($entryPrice);
    }

    /**
     * Update the ATR value.
     *
     * Call this method when ATR is recalculated (typically daily or on each candle close)
     * to adjust the stop distance based on current market volatility.
     *
     * **When to Update:**
     * - Daily: Use daily ATR calculation
     * - Intraday: Update on each candle close (1min, 5min, etc.)
     * - Real-time: Update continuously for ultra-responsive stops
     *
     * **Impact:**
     * - Increasing ATR → Wider stop (more room for volatility)
     * - Decreasing ATR → Tighter stop (less room needed)
     *
     * @param  float  $newATR  New ATR value in price units
     *
     * @example Adapting to changing volatility
     * ```php
     * $stop = new ATRStopLoss('BUY', 100.0, 3.0, 2.0);
     * echo "Initial stop: ₹" . $stop->getStopPrice();  // ₹94 (100 - 2×3)
     *
     * // Volatility increases
     * $stop->updateATR(4.0);
     * // For new positions, stop would be ₹92 (100 - 2×4)
     * // Existing stop stays at ₹94 unless trailing
     * ```
     */
    public function updateATR(float $newATR): void
    {
        $this->currentATR = $newATR;
    }

    /**
     * Update stop price based on current market price (for trailing stops).
     *
     * Only has effect if trailing mode is enabled. Updates the stop price
     * to trail the market price at the current ATR distance.
     *
     * **Behavior:**
     * - Static mode (isTrailing=false): No effect
     * - Trailing mode (isTrailing=true): Adjusts stop to trail price
     *
     * @param  float  $currentPrice  Current market price
     *
     * @example Trailing ATR stop in action
     * ```php
     * $stop = new ATRStopLoss('BUY', 100.0, 2.0, 2.0, true);  // Trailing enabled
     *
     * $stop->updatePrice(105.0);  // Stop moves to ₹101 (105 - 2×2)
     * $stop->updatePrice(110.0);  // Stop moves to ₹106 (110 - 2×2)
     * $stop->updatePrice(108.0);  // Stop stays at ₹106 (doesn't move down)
     * ```
     *
     * @note For static stops, use this method but it will have no effect
     */
    public function updatePrice(float $currentPrice): void
    {
        if ($this->isTrailing) {
            $this->updateStopPrice($currentPrice);
        }
    }

    /**
     * Check if stop loss should trigger at current price.
     *
     * Determines whether the current market price has crossed the ATR-based
     * stop price, indicating the position should be exited.
     *
     * **Trigger Logic:**
     * - LONG: Triggers when price ≤ stop price
     * - SHORT: Triggers when price ≥ stop price
     *
     * @param  float  $currentPrice  Current market price to check
     * @return bool true if stop should trigger (exit position), false otherwise
     *
     * @example Checking for stop trigger
     * ```php
     * $stop = new ATRStopLoss('BUY', 100.0, 3.0, 2.0);
     * // Stop at ₹94 (100 - 2×3)
     *
     * if ($stop->shouldTrigger(95.0)) {
     *     echo "Price ₹95 - No trigger, above stop";
     * }
     *
     * if ($stop->shouldTrigger(93.0)) {
     *     echo "TRIGGERED! Exit at ₹93";
     *     // Place market sell order
     * }
     * ```
     */
    public function shouldTrigger(float $currentPrice): bool
    {
        if ($this->side === 'BUY') {
            return $currentPrice <= $this->currentStopPrice;
        } else {
            return $currentPrice >= $this->currentStopPrice;
        }
    }

    /**
     * Get the current stop price.
     *
     * Returns the price level at which the ATR-based stop will trigger.
     * Useful for monitoring, logging, and displaying stop levels.
     *
     * @return float Current stop price
     *
     * @example Monitoring ATR stop price
     * ```php
     * $stop = new ATRStopLoss('BUY', 100.0, 3.0, 2.0);
     * echo "Entry: ₹100, ATR: 3.0, Stop: ₹" . $stop->getStopPrice();
     * // Output: Entry: ₹100, ATR: 3.0, Stop: ₹94
     * ```
     */
    public function getStopPrice(): float
    {
        return $this->currentStopPrice;
    }

    /**
     * Update stop price based on reference price and current ATR.
     *
     * Internal method that calculates the new stop price using the formula:
     * Stop Distance = ATR × Multiplier
     *
     * For trailing stops, ensures stop only moves favorably (ratcheting).
     *
     * **Calculation:**
     * - LONG: Stop = Price - (ATR × Multiplier)
     * - SHORT: Stop = Price + (ATR × Multiplier)
     *
     * **Ratcheting (Trailing Mode):**
     * - LONG: max(current stop, new stop) - never moves down
     * - SHORT: min(current stop, new stop) - never moves up
     *
     * @param  float  $referencePrice  Reference price for calculation
     *
     * @internal Called by constructor and updatePrice()
     */
    private function updateStopPrice(float $referencePrice): void
    {
        $stopDistance = $this->currentATR * $this->multiplier;

        if ($this->side === 'BUY') {
            $newStop = $referencePrice - $stopDistance;

            // If trailing, only move stop UP
            if ($this->isTrailing && isset($this->currentStopPrice)) {
                $this->currentStopPrice = max($this->currentStopPrice, $newStop);
            } else {
                $this->currentStopPrice = $newStop;
            }
        } else {
            $newStop = $referencePrice + $stopDistance;

            // If trailing, only move stop DOWN
            if ($this->isTrailing && isset($this->currentStopPrice)) {
                $this->currentStopPrice = min($this->currentStopPrice, $newStop);
            } else {
                $this->currentStopPrice = $newStop;
            }
        }
    }
}
