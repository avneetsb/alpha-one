<?php

namespace TradingPlatform\Domain\Exchange\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Instrument
 *
 * Represents a tradable financial instrument across all asset classes.
 * Stores exchange-specific details, derivative specifications, and trading parameters.
 *
 * **Instrument Types:**
 * - 'equity': Stocks, ETFs
 * - 'futures': Index/stock futures
 * - 'options': Call/Put options
 * - 'currency': Currency derivatives
 * - 'commodity': Commodity derivatives
 *
 * **Key Attributes:**
 * - Broker symbol: Exchange-specific identifier
 * - Lot size: Minimum tradable quantity
 * - Tick size: Minimum price movement
 * - Expiry: For derivatives (futures/options)
 * - Strike: For options contracts
 *
 * **Use Cases:**
 * - Order placement validation
 * - Price/quantity rounding
 * - Derivative chain construction
 * - Instrument search and filtering
 * - Trading eligibility checks
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Equity instrument
 * ```php
 * $stock = Instrument::create([
 *     'broker_id' => 'DHAN',
 *     'broker_symbol' => 'RELIANCE-EQ',
 *     'symbol' => 'RELIANCE',
 *     'name' => 'Reliance Industries Ltd',
 *     'exchange' => 'NSE',
 *     'type' => 'equity',
 *     'lot_size' => 1,
 *     'tick_size' => 0.05,
 *     'is_tradable' => true,
 * ]);
 * ```
 * @example Futures contract
 * ```php
 * $future = Instrument::create([
 *     'broker_symbol' => 'NIFTY24JANFUT',
 *     'symbol' => 'NIFTY',
 *     'exchange' => 'NFO',
 *     'type' => 'futures',
 *     'lot_size' => 50,
 *     'tick_size' => 0.05,
 *     'expiry' => '2024-01-25',
 *     'underlying_id' => 1,  // NIFTY index
 * ]);
 * ```
 *
 * @property int $id Primary key
 * @property string $broker_id Broker identifier
 * @property string $broker_symbol Exchange-specific symbol
 * @property string $symbol Standard symbol
 * @property string $name Full instrument name
 * @property string $exchange Exchange code (NSE, BSE, NFO, MCX)
 * @property string $type Instrument type
 * @property int $lot_size Minimum tradable quantity
 * @property float $tick_size Minimum price movement
 * @property \DateTime $expiry Expiry date (derivatives)
 * @property float $strike Strike price (options)
 * @property string $option_type 'CE' or 'PE' (options)
 * @property int $underlying_id Foreign key to underlying instrument
 * @property bool $is_tradable Trading enabled flag
 * @property string $status 'active', 'expired', 'suspended'
 * @property \DateTime $last_synced_at Last sync with broker
 */
class Instrument extends Model
{
    protected $fillable = [
        'broker_id',
        'broker_symbol',
        'symbol',
        'name',
        'exchange',
        'type',
        'lot_size',
        'tick_size',
        'expiry',
        'strike',
        'option_type',
        'underlying_id',
        'is_tradable',
        'status',
        'last_synced_at',
    ];

    protected $casts = [
        'lot_size' => 'integer',
        'tick_size' => 'decimal:4',
        'expiry' => 'date',
        'strike' => 'decimal:2',
        'is_tradable' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the underlying instrument (for derivatives).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function underlying()
    {
        return $this->belongsTo(Instrument::class, 'underlying_id');
    }

    /**
     * Get the derivatives linked to this instrument.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function derivatives()
    {
        return $this->hasMany(Instrument::class, 'underlying_id');
    }

    /**
     * Scope a query to only include tradable instruments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTradable($query)
    {
        return $query->where('is_tradable', true);
    }

    /**
     * Scope a query to filter by instrument type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by expiry date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $from
     * @param  mixed  $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpiryBetween($query, $from, $to)
    {
        return $query->whereBetween('expiry', [$from, $to]);
    }
}
