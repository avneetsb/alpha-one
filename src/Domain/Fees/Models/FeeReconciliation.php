<?php

namespace TradingPlatform\Domain\Fees\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Fee Reconciliation Model
 * 
 * Tracks fee reconciliation between calculated and broker statement fees
 */
class FeeReconciliation extends Model
{
    protected $table = 'fee_reconciliation';

    protected $fillable = [
        'broker_id',
        'date',
        'calculated_fees_total',
        'broker_statement_fees_total',
        'discrepancy',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'calculated_fees_total' => 'decimal:2',
        'broker_statement_fees_total' => 'decimal:2',
        'discrepancy' => 'decimal:2',
    ];

    /**
     * Check if fees match
     */
    public function isMatched(): bool
    {
        return $this->status === 'matched';
    }

    /**
     * Check if there's a mismatch
     */
    public function hasMismatch(): bool
    {
        return $this->status === 'mismatch';
    }

    /**
     * Get discrepancy percentage
     */
    public function getDiscrepancyPercentage(): float
    {
        if ($this->broker_statement_fees_total == 0) {
            return 0.0;
        }

        return abs(($this->discrepancy / $this->broker_statement_fees_total) * 100);
    }

    /**
     * Scope for mismatched reconciliations
     */
    public function scopeMismatched($query)
    {
        return $query->where('status', 'mismatch');
    }

    /**
     * Scope for date range
     */
    public function scopeForDateRange($query, \DateTime $from, \DateTime $to)
    {
        return $query->whereBetween('date', [
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
        ]);
    }
}
