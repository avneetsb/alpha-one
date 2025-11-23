<?php

namespace TradingPlatform\Domain\Portfolio;

/**
 * Corporate Action Service
 *
 * Handles the impact of corporate actions (Dividends, Splits, Mergers) on
 * open positions and portfolio value. Ensures that positions are adjusted
 * correctly to reflect the economic reality of these events.
 *
 * **Supported Actions:**
 * - **Dividend**: Cash payout per share, increases realized P&L
 * - **Stock Split**: Increases quantity and reduces price, neutral to value
 * - **Merger/Acquisition**: Converts position to new instrument based on ratio
 *
 * @version 1.0.0
 *
 * @example Stock Split (1:10)
 * ```php
 * $service->processStockSplit($position, 10.0);
 * // Quantity 10 -> 100, Price 1000 -> 100
 * ```
 */
class CorporateActionService
{
    /**
     * Process a cash dividend payment.
     *
     * Credits the dividend amount to the portfolio's cash balance or realized P&L.
     * Does not typically affect the position's quantity or average price, but
     * increases the total return of the investment.
     *
     * @param  Position  $position  The position receiving the dividend.
     * @param  float  $dividendPerShare  The cash amount paid per share.
     *
     * @example Processing a dividend
     * ```php
     * // Position: 100 shares
     * // Dividend: $5.00 per share
     * $service->processDividend($position, 5.00);
     * // Result: $500 credited to account
     * ```
     */
    public function processDividend(Position $position, float $dividendPerShare): void
    {
        // Process dividend payment
        // In a real app, we'd:
        // 1. Calculate total dividend: quantity * dividendPerShare
        // 2. Update position's realized P&L
        // 3. Log the corporate action

        // Mock implementation
    }

    /**
     * Process a stock split or reverse split.
     *
     * Adjusts the quantity and average buy price of a position to maintain
     * the same total value. This is a neutral event for the portfolio value
     * but changes the unit metrics.
     *
     * @param  Position  $position  The position to split.
     * @param  float  $splitRatio  The split factor.
     *                             > 1 for forward split (e.g., 2.0 for 2:1)
     *                             < 1 for reverse split (e.g., 0.5 for 1:2)
     *
     * @example Forward Split (2:1)
     * ```php
     * // Before: 100 shares @ $100
     * $service->processStockSplit($pos, 2.0);
     * // After: 200 shares @ $50
     * ```
     * @example Reverse Split (1:10)
     * ```php
     * // Before: 1000 shares @ $10
     * $service->processStockSplit($pos, 0.1);
     * // After: 100 shares @ $100
     * ```
     */
    public function processStockSplit(Position $position, float $splitRatio): void
    {
        // Process stock split (e.g., 2:1 split means splitRatio = 2.0)
        // In a real app, we'd:
        // 1. Multiply quantity by splitRatio
        // 2. Divide buy_price by splitRatio
        // 3. Update position
        // 4. Log the corporate action

        $position->quantity = (int) ($position->quantity * $splitRatio);
        $position->buy_price = $position->buy_price / $splitRatio;
        $position->save();
    }

    /**
     * Process a merger or acquisition.
     *
     * Converts an existing position in the target company into a new position
     * in the acquiring company based on the agreed conversion ratio. The cost
     * basis is transferred to the new position.
     *
     * @param  Position  $oldPosition  The position in the acquired company.
     * @param  string  $newInstrumentId  The ID/Symbol of the acquiring company.
     * @param  float  $conversionRatio  Shares of new co. received per share of old co.
     *
     * @example Merger processing
     * ```php
     * // HDFC merges into HDFC Bank (Ratio 1.68)
     * // Old Position: 100 shares HDFC
     * $service->processMerger($hdfcPos, 'HDFCBANK', 1.68);
     * // New Position: 168 shares HDFCBANK
     * ```
     */
    public function processMerger(Position $oldPosition, string $newInstrumentId, float $conversionRatio): void
    {
        // Process merger/acquisition
        // In a real app, we'd:
        // 1. Close old position
        // 2. Create new position with converted quantity
        // 3. Transfer cost basis
        // 4. Log the corporate action

        // Mock implementation
    }
}
