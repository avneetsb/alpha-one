<?php

namespace TradingPlatform\Domain\Portfolio;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Position
 *
 * Represents an active trading position (open or partially closed).
 * Tracks buy/sell quantities, average prices, and P&L for real-time position management.
 *
 * **Position Types:**
 * - LONG: Bought with expectation of price increase
 * - SHORT: Sold with expectation of price decrease
 *
 * **Product Types:**
 * - INTRADAY (MIS): Must be squared off same day
 * - CNC (Delivery): Can be held overnight
 * - NRML (Normal): For derivatives
 *
 * **P&L Calculation:**
 * - Realized P&L: Profit/loss from closed trades
 * - Unrealized P&L: Mark-to-market on open position
 * - Total P&L: Realized + Unrealized
 *
 * **Position Lifecycle:**
 * 1. Open: First buy/sell creates position
 * 2. Add: Additional trades increase position
 * 3. Reduce: Opposite trades decrease position
 * 4. Close: Position fully squared off (net_qty = 0)
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Long position
 * ```php
 * $position = Position::create([
 *     'user_id' => 1,
 *     'instrument_id' => 100,
 *     'position_type' => 'LONG',
 *     'product_type' => 'INTRADAY',
 *     'buy_qty' => 100,
 *     'buy_avg' => 2510.50,
 *     'sell_qty' => 0,
 *     'sell_avg' => 0,
 *     'net_qty' => 100,
 *     'realized_pnl' => 0,
 *     'unrealized_pnl' => 500,  // (2520 - 2510.50) * 100
 * ]);
 * ```
 *
 * @property int $id Primary key
 * @property int $user_id User identifier
 * @property string $broker_id Broker identifier
 * @property int $instrument_id Foreign key to instruments
 * @property string $position_type 'LONG' or 'SHORT'
 * @property string $product_type 'INTRADAY', 'CNC', 'NRML'
 * @property int $buy_qty Total quantity bought
 * @property float $buy_avg Average buy price
 * @property int $sell_qty Total quantity sold
 * @property float $sell_avg Average sell price
 * @property int $net_qty Net position (buy_qty - sell_qty)
 * @property float $realized_pnl Realized profit/loss
 * @property float $unrealized_pnl Unrealized profit/loss (MTM)
 * @property \DateTime $created_at Creation timestamp
 * @property \DateTime $updated_at Last update timestamp
 */
class Position extends Model
{
    protected $table = 'positions';

    protected $fillable = [
        'user_id',
        'broker_id',
        'instrument_id',
        'position_type', // LONG/SHORT
        'product_type', // INTRADAY/CNC
        'buy_qty',
        'buy_avg',
        'sell_qty',
        'sell_avg',
        'net_qty',
        'realized_pnl',
        'unrealized_pnl',
    ];

    protected $casts = [
        'buy_qty' => 'integer',
        'sell_qty' => 'integer',
        'net_qty' => 'integer',
        'buy_avg' => 'decimal:2',
        'sell_avg' => 'decimal:2',
        'realized_pnl' => 'decimal:2',
        'unrealized_pnl' => 'decimal:2',
    ];
}
