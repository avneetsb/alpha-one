<?php

namespace TradingPlatform\Domain\Portfolio;

use Illuminate\Database\Eloquent\Model;

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
