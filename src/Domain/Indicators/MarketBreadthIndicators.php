<?php

namespace TradingPlatform\Domain\Indicators;

/**
 * MARKET BREADTH INDICATORS
 * Indicators that measure market-wide participation and health
 * Used primarily for market index analysis
 */

/** Advance-Decline Line */
class AdvanceDeclineLine extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        // This indicator needs special market data (advancing/declining issues)
        // For individual stocks, we'll use a proxy based on price changes
        
        $closes = $this->extractClosePrices($data);
        $result = [0 => 0];
        
        for ($i = 1; $i < count($data); $i++) {
            $change = $closes[$i] > $closes[$i - 1] ? 1 : ($closes[$i] < $closes[$i - 1] ? -1 : 0);
            $result[$i] = $result[$i - 1] + $change;
        }
        
        return $result;
    }
}

/** Advance-Decline Ratio (ADR) */
class AdvanceDeclineRatio extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 10;
        $closes = $this->extractClosePrices($data);
        
        $result = [];
        
        for ($i = $period; $i < count($data); $i++) {
            $advances = 0;
            $declines = 0;
            
            for ($j = 1; $j <= $period; $j++) {
                if ($closes[$i - $j + 1] > $closes[$i - $j]) {
                    $advances++;
                } elseif ($closes[$i - $j + 1] < $closes[$i - $j]) {
                    $declines++;
                }
            }
            
            $result[$i] = $declines > 0 ? $advances / $declines : $advances;
        }
        
        return $result;
    }
}

/** McClellan Oscillator */
class McClellanOscillator extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $fastPeriod = $params['fast'] ?? 19;
        $slowPeriod = $params['slow'] ?? 39;
        
        $adLine = new AdvanceDeclineLine();
        $adData = $adLine->calculate($data);
        
        $adArray = [];
        foreach ($adData as $value) {
            $adArray[] = ['close' => $value];
        }
        
        $ema = new EMA();
        $fastEMA = $ema->calculate($adArray, ['period' => $fastPeriod]);
        $slowEMA = $ema->calculate($adArray, ['period' => $slowPeriod]);
        
        $result = [];
        foreach ($fastEMA as $i => $fast) {
            if (isset($slowEMA[$i])) {
                $result[$i] = $fast - $slowEMA[$i];
            }
        }
        
        return $result;
    }
}

/** Arms Index (TRIN) */
class ArmsIndex extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        // Simplified version using volume and price
        // Real TRIN needs market-wide data
        
        $closes = $this->extractClosePrices($data);
        $volumes = $this->extractVolumes($data);
        
        $result = [];
        
        for ($i = 1; $i < count($data); $i++) {
            $priceUp = $closes[$i] > $closes[$i - 1];
            $volRatio = $priceUp ? $volumes[$i] / max($volumes[$i - 1], 1) : $volumes[$i - 1] / max($volumes[$i], 1);
            $result[$i] = $volRatio;
        }
        
        return $result;
    }
}

/** New High-New Low Index */
class NewHighNewLow extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 52; // 52-period (e.g., weeks for daily data)
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        
        $result = [];
        
        for ($i = $period; $i < count($data); $i++) {
            $periodHigh = max(array_slice($highs, $i - $period, $period));
            $periodLow = min(array_slice($lows, $i - $period, $period));
            
            $isNewHigh = $highs[$i] >= $periodHigh ? 1 : 0;
            $isNewLow = $lows[$i] <= $periodLow ? 1 : 0;
            
            $result[$i] = $isNewHigh - $isNewLow;
        }
        
        return $result;
    }
}
