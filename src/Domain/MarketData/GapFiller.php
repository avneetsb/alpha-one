<?php

namespace TradingPlatform\Domain\MarketData;

/**
 * Utility class for filling gaps in market data candle series.
 *
 * Provides a simple strategy to handle missing data points.
 */
class GapFiller
{
    /**
 * Fill gaps in a candle array according to the specified strategy.
 *
 * @param array  $candles   Array of candle data.
 * @param string $strategy  Gap‑filling strategy: 'forward', 'backward', or 'interpolate'.
 * @return array            Candles array with gaps handled.
 */
public function fillGaps(array $candles, string $strategy = 'forward'): array
    {
        // Strategy: 'forward', 'backward', 'interpolate'
        if (empty($candles)) {
            return $candles;
        }

        // For demo, we just return the candles as-is
        // In a real implementation, we would:
        // 1. Detect gaps by checking timestamps
        // 2. Fill gaps based on strategy
        
        return $candles;
    }


}
