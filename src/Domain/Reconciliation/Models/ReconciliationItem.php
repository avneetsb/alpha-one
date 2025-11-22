<?php

namespace TradingPlatform\Domain\Reconciliation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationItem extends Model
{
    protected $fillable = [
        'reconciliation_run_id',
        'item_type',
        'item_id',
        'broker_ref_id',
        'system_data',
        'broker_data',
        'discrepancy_details',
        'status',
        'resolution_notes',
    ];

    protected $casts = [
        'system_data' => 'array',
        'broker_data' => 'array',
        'discrepancy_details' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(ReconciliationRun::class, 'reconciliation_run_id');
    }

    public function resolve(string $notes): void
    {
        $this->update([
            'status' => 'resolved',
            'resolution_notes' => $notes,
        ]);
    }
}
