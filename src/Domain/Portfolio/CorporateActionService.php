<?php

namespace TradingPlatform\Domain\Portfolio;

class CorporateActionService
{
    public function processDividend(Position $position, float $dividendPerShare): void
    {
        // Process dividend payment
        // In a real app, we'd:
        // 1. Calculate total dividend: quantity * dividendPerShare
        // 2. Update position's realized P&L
        // 3. Log the corporate action
        
        // Mock implementation
    }

    public function processStockSplit(Position $position, float $splitRatio): void
    {
        // Process stock split (e.g., 2:1 split means splitRatio = 2.0)
        // In a real app, we'd:
        // 1. Multiply quantity by splitRatio
        // 2. Divide buy_price by splitRatio
        // 3. Update position
        // 4. Log the corporate action
        
        $position->quantity = (int)($position->quantity * $splitRatio);
        $position->buy_price = $position->buy_price / $splitRatio;
        $position->save();
    }

    public function processMerger(Position $oldPosition, string $newInstrumentId, float $conversionRatio): void
    {
        // Process merger/acquisition
        // In a real app, we'd:
        // 1. Close old position
        // 2. Create new position with converted quantity
        // 3. Transfer cost basis
        // 4. Log the corporate action
        
        // Mock implementation
    }
}
