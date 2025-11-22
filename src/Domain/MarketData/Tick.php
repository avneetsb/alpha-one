<?php

namespace TradingPlatform\Domain\MarketData;

use Illuminate\Database\Eloquent\Model;

/**
 * Market Tick Model
 *
 * Represents a single tick event (last traded price/time and quantities).
 * Useful for high-frequency processing and real-time strategy evaluation.
 *
 * @package TradingPlatform\Domain\MarketData
 * @version 1.0.0
 */
class Tick extends Model
{
    protected $table = 'ticks';
    public $timestamps = false;

    protected $fillable = [
        'instrument_id',
        'ts',
        'ltp',
        'ltt',
        'buy_qty',
        'sell_qty',
        'volume'
    ];

    protected $casts = [
        'instrument_id' => 'integer',
        'ts' => 'datetime',
        'ltp' => 'decimal:2',
        'ltt' => 'datetime',
        'buy_qty' => 'integer',
        'sell_qty' => 'integer',
        'volume' => 'integer',
    ];
}
