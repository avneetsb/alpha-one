<?php

namespace TradingPlatform\Domain\Portfolio;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Holding
 *
 * Represents a long-term investment holding (delivery positions).
 * Tracks quantity, cost basis, and current market value for portfolio valuation.
 *
 * **Holding vs Position:**
 * - **Holding**: Long-term delivery stocks (T+2 settled)
 * - **Position**: Active trades (intraday or pending settlement)
 *
 * **Key Metrics:**
 * - Quantity: Number of shares held
 * - Avg Cost: Average acquisition price
 * - LTP: Last traded price (current market price)
 * - Current Value: qty × LTP
 * - P&L: (LTP - avg_cost) × qty
 * - P&L %: ((LTP - avg_cost) / avg_cost) × 100
 *
 * **Use Cases:**
 * - Portfolio valuation
 * - Investment tracking
 * - Tax reporting (LTCG/STCG)
 * - Dividend tracking
 * - Corporate action processing
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Creating a holding
 * ```php
 * $holding = Holding::create([
 *     'user_id' => 1,
 *     'broker_id' => 'DHAN',
 *     'instrument_id' => 100,  // RELIANCE
 *     'qty' => 50,
 *     'avg_cost' => 2400.00,
 *     'ltp' => 2510.50,
 *     'current_value' => 125525.00,  // 50 × 2510.50
 * ]);
 *
 * // P&L = (2510.50 - 2400.00) × 50 = ₹5,525
 * // P&L% = (110.50 / 2400.00) × 100 = 4.60%
 * ```
 */
class Holding extends Model
{
    protected $table = 'holdings';

    protected $fillable = [
        'user_id',
        'broker_id',
        'instrument_id',
        'qty',
        'avg_cost',
        'ltp',
        'current_value',
    ];

    protected $casts = [
        'qty' => 'integer',
        'avg_cost' => 'decimal:2',
        'ltp' => 'decimal:2',
        'current_value' => 'decimal:2',
    ];
}
