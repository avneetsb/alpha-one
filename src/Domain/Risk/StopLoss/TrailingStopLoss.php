<?php

namespace TradingPlatform\Domain\Risk\StopLoss;

/**
 * Class TrailingStopLoss
 *
 * Implements a trailing stop loss mechanism that dynamically adjusts the stop price
 * as the market moves in favor of the position, locking in profits while limiting losses.
 *
 * **How It Works:**
 * - For LONG positions: Stop price trails below market price by a fixed percentage
 * - For SHORT positions: Stop price trails above market price by a fixed percentage
 * - Stop price only moves in favorable direction (never moves against you)
 * - Triggers when market price crosses the stop price
 *
 * **Benefits:**
 * - Locks in profits as position moves favorably
 * - Provides downside protection
 * - Removes emotion from exit decisions
 * - Allows trends to run while protecting gains
 *
 * **Use Cases:**
 * - Trend-following strategies
 * - Profit protection in volatile markets
 * - Automated position management
 * - Risk management for swing trades
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Long Position with 5% Trailing Stop
 * ```php
 * use TradingPlatform\Domain\Risk\StopLoss\TrailingStopLoss;
 *
 * // Buy AAPL at ₹100, set 5% trailing stop
 * $stop = new TrailingStopLoss('BUY', 100.0, 0.05);
 * echo "Initial stop: ₹" . $stop->getStopPrice();  // ₹95.00
 *
 * // Price moves to ₹110 (profit!)
 * $stop->update(110.0);
 * echo "Updated stop: ₹" . $stop->getStopPrice();  // ₹104.50 (locked in profit)
 *
 * // Price moves to ₹115 (more profit!)
 * $stop->update(115.0);
 * echo "Updated stop: ₹" . $stop->getStopPrice();  // ₹109.25
 *
 * // Price drops to ₹108 - stop triggers!
 * if ($stop->shouldTrigger(108.0)) {
 *     echo "Stop triggered! Exit at ₹108 with ₹8 profit";
 * }
 * ```
 * @example Short Position with 3% Trailing Stop
 * ```php
 * // Short GOOGL at ₹2800, set 3% trailing stop
 * $stop = new TrailingStopLoss('SELL', 2800.0, 0.03);
 * echo "Initial stop: ₹" . $stop->getStopPrice();  // ₹2884.00
 *
 * // Price drops to ₹2700 (profit on short!)
 * $stop->update(2700.0);
 * echo "Updated stop: ₹" . $stop->getStopPrice();  // ₹2781.00 (locked in profit)
 *
 * // Price rallies to ₹2790 - stop triggers!
 * if ($stop->shouldTrigger(2790.0)) {
 *     echo "Stop triggered! Cover short at ₹2790 with ₹10 profit";
 * }
 * ```
 *
 * @see ATRStopLoss For volatility-based stop loss
 * @see RiskLimitManager For portfolio-level risk management
 */
class TrailingStopLoss
{
    /**
     * @var float Trailing percentage (e.g., 0.05 for 5%)
     */
    private float $trailingPercent;

    /**
     * @var float Highest price reached for LONG, lowest price for SHORT
     */
    private float $highestPrice;

    /**
     * @var float Current stop price that triggers exit
     */
    private float $currentStopPrice;

    /**
     * @var string Position side: 'BUY' for long, 'SELL' for short
     */
    private string $side;

    /**
     * Initialize trailing stop loss for a position.
     *
     * Sets up the initial stop price based on entry price and trailing percentage.
     * The stop will trail the market price, locking in profits as price moves favorably.
     *
     * **Stop Price Calculation:**
     * - LONG: Stop = Entry Price × (1 - Trailing %)
     * - SHORT: Stop = Entry Price × (1 + Trailing %)
     *
     * @param  string  $side  Position side: 'BUY' for long positions, 'SELL' for short positions
     * @param  float  $entryPrice  Entry price of the position
     * @param  float  $trailingPercent  Trailing percentage as decimal (e.g., 0.05 for 5%)
     *                                  Typical range: 0.02 to 0.10 (2% to 10%)
     *
     * @example Conservative 2% trailing stop
     * ```php
     * $stop = new TrailingStopLoss('BUY', 1000.0, 0.02);
     * // Initial stop at ₹980 (2% below entry)
     * ```
     * @example Aggressive 10% trailing stop for volatile stocks
     * ```php
     * $stop = new TrailingStopLoss('BUY', 500.0, 0.10);
     * // Initial stop at ₹450 (10% below entry)
     * // Gives more room for price swings
     * ```
     */
    public function __construct(string $side, float $entryPrice, float $trailingPercent)
    {
        $this->side = strtoupper($side);
        $this->trailingPercent = $trailingPercent;
        $this->highestPrice = $entryPrice; // Or lowest price for SELL

        $this->updateStopPrice($entryPrice);
    }

    /**
     * Update the stop price based on current market price.
     *
     * Adjusts the stop price if the market has moved favorably. The stop price
     * will only move in the favorable direction - it never moves against you.
     *
     * **Behavior:**
     * - LONG: If price > highest price seen, update stop upward
     * - SHORT: If price < lowest price seen, update stop downward
     * - If price moves unfavorably, stop price remains unchanged
     *
     * **When to Call:**
     * Call this method on every price update (tick, candle close, etc.) to
     * keep the stop price current.
     *
     * @param  float  $currentPrice  Current market price
     *
     * @example Updating stop as price moves
     * ```php
     * $stop = new TrailingStopLoss('BUY', 100.0, 0.05);
     *
     * // Price moves up - stop adjusts
     * $stop->update(105.0);  // Stop moves to ₹99.75
     * $stop->update(110.0);  // Stop moves to ₹104.50
     *
     * // Price moves down - stop stays at ₹104.50
     * $stop->update(108.0);  // Stop unchanged (still ₹104.50)
     * $stop->update(106.0);  // Stop unchanged (still ₹104.50)
     * ```
     *
     * @note For real-time trading, call this on every tick
     * @note For swing trading, call this on daily close
     */
    public function update(float $currentPrice): void
    {
        if ($this->side === 'BUY') {
            if ($currentPrice > $this->highestPrice) {
                $this->highestPrice = $currentPrice;
                $this->updateStopPrice($currentPrice);
            }
        } else {
            // For Short positions (SELL), we track the lowest price
            if ($currentPrice < $this->highestPrice) {
                $this->highestPrice = $currentPrice;
                $this->updateStopPrice($currentPrice);
            }
        }
    }

    /**
     * Check if the stop loss should trigger at the current price.
     *
     * Determines whether the current market price has crossed the stop price,
     * indicating that the position should be exited.
     *
     * **Trigger Logic:**
     * - LONG: Triggers when current price ≤ stop price
     * - SHORT: Triggers when current price ≥ stop price
     *
     * @param  float  $currentPrice  Current market price to check
     * @return bool true if stop should trigger (exit position), false otherwise
     *
     * @example Checking for stop trigger
     * ```php
     * $stop = new TrailingStopLoss('BUY', 100.0, 0.05);
     * $stop->update(110.0);  // Stop at ₹104.50
     *
     * if ($stop->shouldTrigger(105.0)) {
     *     echo "Price ₹105 - No trigger, above stop";
     * }
     *
     * if ($stop->shouldTrigger(104.0)) {
     *     echo "TRIGGERED! Exit position at ₹104";
     *     // Place market sell order
     * }
     * ```
     *
     * @note Always check this before placing exit order
     * @note Use market orders for stop exits to ensure execution
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
     * Returns the price level at which the stop loss will trigger.
     * Useful for monitoring, logging, and displaying stop levels.
     *
     * @return float Current stop price
     *
     * @example Monitoring stop price
     * ```php
     * $stop = new TrailingStopLoss('BUY', 100.0, 0.05);
     *
     * echo "Entry: ₹100, Stop: ₹" . $stop->getStopPrice();
     * // Output: Entry: ₹100, Stop: ₹95
     *
     * $stop->update(110.0);
     * echo "Price: ₹110, Stop: ₹" . $stop->getStopPrice();
     * // Output: Price: ₹110, Stop: ₹104.5
     * ```
     */
    public function getStopPrice(): float
    {
        return $this->currentStopPrice;
    }

    /**
     * Update the stop price based on a reference price.
     *
     * Internal method that calculates and sets the new stop price.
     * Ensures stop only moves in favorable direction.
     *
     * **Calculation:**
     * - LONG: New Stop = Highest Price × (1 - Trailing %)
     * - SHORT: New Stop = Lowest Price × (1 + Trailing %)
     *
     * **Ratcheting:**
     * Stop price ratchets (only moves favorably):
     * - LONG: max(current stop, new stop) - never moves down
     * - SHORT: min(current stop, new stop) - never moves up
     *
     * @param  float  $price  Reference price (usually highest/lowest price)
     *
     * @internal Called by constructor and update() method
     */
    private function updateStopPrice(float $price): void
    {
        if ($this->side === 'BUY') {
            // Stop moves UP as price moves UP
            $newStop = $this->highestPrice * (1 - $this->trailingPercent);
            // Ensure stop never moves down
            $this->currentStopPrice = max($this->currentStopPrice ?? 0, $newStop);
        } else {
            // Stop moves DOWN as price moves DOWN
            $newStop = $this->highestPrice * (1 + $this->trailingPercent);
            // Ensure stop never moves up
            $this->currentStopPrice = min($this->currentStopPrice ?? PHP_FLOAT_MAX, $newStop);
        }
    }
}
