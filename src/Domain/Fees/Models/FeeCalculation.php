<?php

namespace TradingPlatform\Domain\Fees\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fee Calculation Model
 * 
 * Stores calculated fee breakdown for each order/trade
 */
class FeeCalculation extends Model
{
    protected $fillable = [
        'order_id',
        'trade_id',
        'broker_id',
        'instrument_id',
        'asset_class',
        'segment',
        'order_value',
        'quantity',
        'brokerage',
        'stt',
        'ctt',
        'exchange_transaction_charges',
        'gst',
        'sebi_charges',
        'stamp_duty',
        'total_fees',
        'calculation_timestamp',
    ];

    protected $casts = [
        'order_value' => 'decimal:2',
        'quantity' => 'integer',
        'brokerage' => 'decimal:2',
        'stt' => 'decimal:2',
        'ctt' => 'decimal:2',
        'exchange_transaction_charges' => 'decimal:2',
        'gst' => 'decimal:2',
        'sebi_charges' => 'decimal:2',
        'stamp_duty' => 'decimal:2',
        'total_fees' => 'decimal:2',
        'calculation_timestamp' => 'datetime',
    ];

    /**
     * Get the order this fee calculation belongs to
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo('TradingPlatform\Domain\Order\Order');
    }

    /**
     * Get the instrument
     */
    public function instrument(): BelongsTo
    {
        return $this->belongsTo('TradingPlatform\Domain\Exchange\Models\Instrument');
    }

    /**
     * Get fee breakdown as array
     */
    public function getBreakdown(): array
    {
        return [
            'brokerage' => $this->brokerage,
            'stt' => $this->stt,
            'ctt' => $this->ctt,
            'exchange_transaction_charges' => $this->exchange_transaction_charges,
            'gst' => $this->gst,
            'sebi_charges' => $this->sebi_charges,
            'stamp_duty' => $this->stamp_duty,
            'total_fees' => $this->total_fees,
        ];
    }

    /**
     * Scope for date range
     */
    public function scopeForDateRange($query, \DateTime $from, \DateTime $to)
    {
        return $query->whereBetween('calculation_timestamp', [
            $from->format('Y-m-d H:i:s'),
            $to->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Scope for broker
     */
    public function scopeForBroker($query, string $brokerId)
    {
        return $query->where('broker_id', $brokerId);
    }
}
