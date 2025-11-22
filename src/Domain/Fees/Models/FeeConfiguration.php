<?php

namespace TradingPlatform\Domain\Fees\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Fee Configuration Model
 * 
 * Stores broker-specific fee rules for different asset classes and segments
 */
class FeeConfiguration extends Model
{
    protected $fillable = [
        'broker_id',
        'asset_class',
        'segment',
        'fee_rules',
        'effective_from',
        'effective_to',
        'version',
    ];

    protected $casts = [
        'fee_rules' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    /**
     * Get active fee configuration for given parameters
     */
    public static function getActiveConfiguration(
        string $brokerId,
        string $assetClass,
        string $segment,
        ?\DateTime $date = null
    ): ?self {
        $date = $date ?? new \DateTime();
        
        return static::where('broker_id', $brokerId)
            ->where('asset_class', $assetClass)
            ->where('segment', $segment)
            ->where('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $date);
            })
            ->orderBy('effective_from', 'desc')
            ->first();
    }

    /**
     * Get fee rules for specific component
     */
    public function getFeeRule(string $component): ?array
    {
        return $this->fee_rules[$component] ?? null;
    }
}
