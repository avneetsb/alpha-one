<?php

namespace TradingPlatform\Domain\Portfolio;

use Illuminate\Database\Eloquent\Model;

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
