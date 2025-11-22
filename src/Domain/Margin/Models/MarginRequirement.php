<?php

namespace TradingPlatform\Domain\Margin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarginRequirement extends Model
{
    protected $fillable = [
        'broker_id',
        'instrument_id',
        'margin_type',
        'margin_percentage',
        'span_margin_amount',
        'margin_parameters',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'margin_percentage' => 'decimal:2',
        'span_margin_amount' => 'decimal:2',
        'margin_parameters' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function instrument(): BelongsTo
    {
        return $this->belongsTo('TradingPlatform\Domain\Exchange\Models\Instrument');
    }

    public static function getActiveRequirement(string $brokerId, int $instrumentId, string $marginType): ?self
    {
        return static::where('broker_id', $brokerId)
            ->where('instrument_id', $instrumentId)
            ->where('margin_type', $marginType)
            ->where('effective_from', '<=', now())
            ->where(function ($query) {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            })
            ->first();
    }
}
