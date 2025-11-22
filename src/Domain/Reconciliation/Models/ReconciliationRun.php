<?php

namespace TradingPlatform\Domain\Reconciliation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReconciliationRun extends Model
{
    protected $fillable = [
        'broker_id',
        'scope',
        'started_at',
        'completed_at',
        'status',
        'items_processed',
        'mismatches_found',
        'mismatches_resolved',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ReconciliationItem::class);
    }

    public function mismatches(): HasMany
    {
        return $this->items()->where('status', 'mismatch');
    }
}
