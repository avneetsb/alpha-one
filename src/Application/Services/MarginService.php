<?php

namespace TradingPlatform\Application\Services;

use TradingPlatform\Domain\Exchange\Models\Instrument;
use TradingPlatform\Domain\Portfolio\Position;

/**
 * Margin Management Service  
 * 
 * Handles margin calculations, utilization tracking, and margin call alerts
 */
class MarginService
{
    /**
     * Calculate SPAN margin for futures/options
     */
    public function calculateSpanMargin(Instrument $instrument, float $quantity, float $price): float
    {
        // Simplified SPAN calculation
        // In production, this would use exchange-provided SPAN files
        $lotSize = $instrument->lot_size ?? 1;
        $contractValue = $quantity * $price * $lotSize;
        
        // Approximate 10-15% of contract value for futures
        $spanRate = 0.12;
        
        return $contractValue * $spanRate;
    }

    /**
     * Calculate exposure margin
     */
    public function calculateExposureMargin(Instrument $instrument, float $quantity, float $price): float
    {
        $lotSize = $instrument->lot_size ?? 1;
        $contractValue = $quantity * $price * $lotSize;
        
        // Approximate 3-5% exposure margin
        $exposureRate = 0.04;
        
        return $contractValue * $exposureRate;
    }

    /**
     * Calculate total margin required
     */
    public function calculateTotalMargin(Instrument $instrument, float $quantity, float $price, string $segment = 'futures'): array
    {
        $spanMargin = $this->calculateSpanMargin($instrument, $quantity, $price);
        $exposureMargin = $this->calculateExposureMargin($instrument, $quantity, $price);
        
        return [
            'span_margin' => round($spanMargin, 2),
            'exposure_margin' => round($exposureMargin, 2),
            'total_margin' => round($spanMargin + $exposureMargin, 2),
        ];
    }

    /**
     * Pre-trade margin validation
     */
    public function validateMarginForOrder(
        float $availableMargin,
        Instrument $instrument,
        float $quantity,
        float $price
    ): array {
        $required = $this->calculateTotalMargin($instrument, $quantity, $price);
        $isValid = $availableMargin >= $required['total_margin'];
        
        return [
            'is_valid' => $isValid,
            'required_margin' => $required['total_margin'],
            'available_margin' => $availableMargin,
            'shortfall' => $isValid ? 0 : ($required['total_margin'] - $availableMargin),
        ];
    }

    /**
     * Calculate margin utilization percentage
     */
    public function calculateUtilizationPercentage(float $usedMargin, float $totalMargin): float
    {
        if ($totalMargin == 0) {
            return 0.0;
        }
        
        return round(($usedMargin / $totalMargin) * 100, 2);
    }

    /**
     * Check for margin call conditions
     */
    public function checkMarginCall(float $usedMargin, float $totalMargin): ?array
    {
        $utilization = $this->calculateUtilizationPercentage($usedMargin, $totalMargin);
        
        if ($utilization >= 95) {
            return [
                'severity' => 'critical',
                'message' => "Critical margin utilization: {$utilization}%. Immediate action required.",
                'utilization' => $utilization,
            ];
        } elseif ($utilization >= 85) {
            return [
                'severity' => 'high',
                'message' => "High margin utilization: {$utilization}%. Monitor closely.",
                'utilization' => $utilization,
            ];
        } elseif ($utilization >= 75) {
            return [
                'severity' => 'medium',
                'message' => "Medium margin utilization: {$utilization}%.",
                'utilization' => $utilization,
            ];
        }
        
        return null; // No margin call
    }

    /**
     * Perform margin stress testing under various scenarios
     */
    public function stressTestMargin(array $positions, array $scenarios): array
    {
        $results = [];

        foreach ($scenarios as $name => $params) {
            // $params might be ['price_change_pct' => -10, 'volatility_change_pct' => 20]
            $stressedMargin = 0.0;
            
            foreach ($positions as $pos) {
                // Apply stress factors to price/volatility and recalculate
                // If pos is a Position model, we assume it has a calculated margin_required property injected
                // or we use a fallback
                $baseMargin = $pos->margin_required ?? 0.0;
                $impact = 1.0 + (abs($params['price_change_pct'] ?? 0) / 100) + (($params['volatility_change_pct'] ?? 0) / 100);
                $stressedMargin += $baseMargin * $impact;
            }

            $results[$name] = [
                'stressed_margin' => $stressedMargin,
                'increase_pct' => (($stressedMargin - array_sum(array_column($positions, 'margin_required'))) / array_sum(array_column($positions, 'margin_required'))) * 100
            ];
        }

        return $results;
    }
}
