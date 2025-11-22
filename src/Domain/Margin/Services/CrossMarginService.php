<?php

namespace TradingPlatform\Domain\Margin\Services;

use TradingPlatform\Domain\Exchange\Models\Instrument;
use TradingPlatform\Domain\Portfolio\Models\Position;

class CrossMarginService
{
    /**
     * Calculate margin with cross-margining offsets
     * 
     * @param Position[] $positions
     * @return array
     */
    public function calculatePortfolioMargin(array $positions): array
    {
        $totalMargin = 0.0;
        $benefit = 0.0;

        // Group positions by underlying
        $grouped = $this->groupByUnderlying($positions);

        foreach ($grouped as $underlying => $groupPositions) {
            // Calculate standalone margin
            $standalone = 0.0;
            foreach ($groupPositions as $pos) {
                $standalone += $pos->margin_required;
            }

            // Calculate offset margin (e.g., spread benefits)
            // Simplified logic: Long Future + Short Call = Covered Call (reduced margin)
            $offsetMargin = $this->calculateOffsetMargin($groupPositions);

            $totalMargin += $offsetMargin;
            $benefit += ($standalone - $offsetMargin);
        }

        return [
            'total_margin' => $totalMargin,
            'cross_margin_benefit' => $benefit,
            'details' => $grouped
        ];
    }

    /**
     * Aggregate margins across multiple brokers
     */
    public function aggregateBrokerMargins(array $brokerMargins): array
    {
        $totalUsed = 0.0;
        $totalAvailable = 0.0;
        $details = [];

        foreach ($brokerMargins as $broker => $data) {
            $totalUsed += $data['used'];
            $totalAvailable += $data['available'];
            $details[$broker] = $data;
        }

        return [
            'total_used' => $totalUsed,
            'total_available' => $totalAvailable,
            'utilization_pct' => $totalAvailable > 0 ? ($totalUsed / $totalAvailable) * 100 : 0,
            'breakdown' => $details
        ];
    }

    private function groupByUnderlying(array $positions): array
    {
        $groups = [];
        foreach ($positions as $pos) {
            // Assuming symbol format like 'NIFTY23OCT...'
            $underlying = preg_replace('/\d+.*$/', '', $pos->symbol); 
            $groups[$underlying][] = $pos;
        }
        return $groups;
    }

    private function calculateOffsetMargin(array $positions): float
    {
        // Placeholder for complex SPAN-like spread logic
        // For now, return 80% of sum if we have offsetting positions (Long & Short)
        $hasLong = false;
        $hasShort = false;
        $sumMargin = 0.0;

        foreach ($positions as $pos) {
            if ($pos->quantity > 0) $hasLong = true;
            if ($pos->quantity < 0) $hasShort = true;
            $sumMargin += $pos->margin_required;
        }

        if ($hasLong && $hasShort) {
            return $sumMargin * 0.8; // 20% benefit
        }

        return $sumMargin;
    }
}
