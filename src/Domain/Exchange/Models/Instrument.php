<?php

namespace TradingPlatform\Domain\Exchange\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function underlying()
    {
        return $this->belongsTo(Instrument::class, 'underlying_id');
    }

    public function derivatives()
    {
        return $this->hasMany(Instrument::class, 'underlying_id');
    }

    public function scopeTradable($query)
    {
        return $query->where('is_tradable', true);
    }

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeExpiryBetween($query, $from, $to)
    {
        return $query->whereBetween('expiry', [$from, $to]);
    }
}
