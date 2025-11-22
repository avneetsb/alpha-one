<?php

namespace TradingPlatform\Domain\Margin\Models;

use Illuminate\Database\Eloquent\Model;

class MarginCall extends Model
{
    protected $fillable = [
        'broker_id',
        'account_id',
        'severity',
        'margin_shortfall',
        'margin_utilization_percentage',
        'message',
        'status',
        'triggered_at',
        'acknowledged_at',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'margin_shortfall' => 'decimal:2',
        'margin_utilization_percentage' => 'decimal:2',
        'triggered_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function acknowledge(?string $notes = null): void
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    public function resolve(string $notes): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    public function escalate(): void
    {
        $this->update(['status' => 'escalated']);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }
}
