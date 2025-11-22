<?php

namespace TradingPlatform\Domain\Margin\Models;

use Illuminate\Database\Eloquent\Model;

class MarginUtilization extends Model
{
    protected $table = 'margin_utilization';

    protected $fillable = [
        'broker_id',
        'account_id',
        'available_margin',
        'used_margin',
        'total_margin',
        'margin_utilization_percentage',
        'margin_breakdown',
        'snapshot_timestamp',
    ];

    protected $casts = [
        'available_margin' => 'decimal:2',
        'used_margin' => 'decimal:2',
        'total_margin' => 'decimal:2',
        'margin_utilization_percentage' => 'decimal:2',
        'margin_breakdown' => 'array',
        'snapshot_timestamp' => 'datetime',
    ];

    public function isHighUtilization(): bool
    {
        return $this->margin_utilization_percentage >= 75;
    }

    public function isCriticalUtilization(): bool
    {
        return $this->margin_utilization_percentage >= 90;
    }
}
