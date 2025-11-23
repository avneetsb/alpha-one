<?php

namespace TradingPlatform\Domain\Instrument;

use Illuminate\Database\Eloquent\Model;

class Instrument extends Model
{
    protected $table = 'instruments';

    public $timestamps = false; // We handle updated_at manually or via migration default

    protected $fillable = [
        'exchange',
        'symbol',
        'instrument_type',
        'series',
        'lot_size',
        'expiry_date',
        'strike_price',
        'option_type',
        'tick_size',
        'bracket_flag',
        'cover_flag',
        'asm_gsm_flag',
        'buy_sell_indicator',
        'updated_at',
    ];

    protected $casts = [
        'lot_size' => 'integer',
        'expiry_date' => 'datetime',
        'strike_price' => 'decimal:2',
        'tick_size' => 'decimal:2',
        'bracket_flag' => 'boolean',
        'cover_flag' => 'boolean',
        'updated_at' => 'datetime',
    ];
}
