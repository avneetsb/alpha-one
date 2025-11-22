<?php

namespace TradingPlatform\Domain\Indicators;

/**
 * ADVANCED PRICE-LEVEL & PATTERN INDICATORS
 * Fibonacci, Pivot Points, and Pattern Recognition
 */

/** Fibonacci Retracement */
class FibonacciRetracement extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 50;
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        
        $levels = [];
        
        for ($i = $period - 1; $i < count($data); $i++) {
            $periodHigh = max(array_slice($highs, $i - $period + 1, $period));
            $periodLow = min(array_slice($lows, $i - $period + 1, $period));
            
            $range = $periodHigh - $periodLow;
            
            $levels[$i] = [
                'level_0' => $periodHigh,
                'level_236' => $periodHigh - ($range * 0.236),
                'level_382' => $periodHigh - ($range * 0.382),
                'level_50' => $periodHigh - ($range * 0.5),
                'level_618' => $periodHigh - ($range * 0.618),
                'level_786' => $periodHigh - ($range * 0.786),
                'level_100' => $periodLow,
            ];
        }
        
        return $levels;
    }
}

/** Pivot Points (Standard) */
class PivotPoints extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);
        
        $result = [];
        
        for ($i = 1; $i < count($data); $i++) {
            $pivot = ($highs[$i - 1] + $lows[$i - 1] + $closes[$i - 1]) / 3;
            
            $result[$i] = [
                'pivot' => $pivot,
                'r1' => (2 * $pivot) - $lows[$i - 1],
                'r2' => $pivot + ($highs[$i - 1] - $lows[$i - 1]),
                'r3' => $highs[$i - 1] + (2 * ($pivot - $lows[$i - 1])),
                's1' => (2 * $pivot) - $highs[$i - 1],
                's2' => $pivot - ($highs[$i - 1] - $lows[$i - 1]),
                's3' => $lows[$i - 1] - (2 * ($highs[$i - 1] - $pivot)),
            ];
        }
        
        return $result;
    }
}

/** Camarilla Pivot Points */
class CamarillaPivots extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);
        
        $result = [];
        
        for ($i = 1; $i < count($data); $i++) {
            $range = $highs[$i - 1] - $lows[$i - 1];
            
            $result[$i] = [
                'r4' => ($range * 1.1) / 2 + $closes[$i - 1],
                'r3' => ($range * 1.1) / 4 + $closes[$i - 1],
                'r2' => ($range * 1.1) / 6 + $closes[$i - 1],
                'r1' => ($range * 1.1) / 12 + $closes[$i - 1],
                'pivot' => $closes[$i - 1],
                's1' => $closes[$i - 1] - ($range * 1.1) / 12,
                's2' => $closes[$i - 1] - ($range * 1.1) / 6,
                's3' => $closes[$i - 1] - ($range * 1.1) / 4,
                's4' => $closes[$i - 1] - ($range * 1.1) / 2,
            ];
        }
        
        return $result;
    }
}

/** Support & Resistance Levels */
class SupportResistance extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $threshold = $params['threshold'] ?? 0.02; // 2% threshold
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        
        $result = [];
        
        for ($i = $period; $i < count($data); $i++) {
            $localMaxima = [];
            $localMinima = [];
            
            // Find local maxima and minima
            for ($j = $i - $period + 2; $j < $i - 1; $j++) {
                if ($highs[$j] > $highs[$j - 1] && $highs[$j] > $highs[$j + 1]) {
                    $localMaxima[] = $highs[$j];
                }
                if ($lows[$j] < $lows[$j - 1] && $lows[$j] < $lows[$j + 1]) {
                    $localMinima[] = $lows[$j];
                }
            }
            
            // Cluster similar levels
            $resistance = $this->clusterLevels($localMaxima, $threshold);
            $support = $this->clusterLevels($localMinima, $threshold);
            
            $result[$i] = [
                'resistance' => $resistance,
                'support' => $support,
            ];
        }
        
        return $result;
    }
    
    private function clusterLevels(array $levels, float $threshold): array
    {
        if (empty($levels)) return [];
        
        sort($levels);
        $clusters = [];
        $currentCluster = [$levels[0]];
        
        for ($i = 1; $i < count($levels); $i++) {
            if (abs($levels[$i] - $levels[$i - 1]) / $levels[$i - 1] < $threshold) {
                $currentCluster[] = $levels[$i];
            } else {
                $clusters[] = array_sum($currentCluster) / count($currentCluster);
                $currentCluster = [$levels[$i]];
            }
        }
        
        $clusters[] = array_sum($currentCluster) / count($currentCluster);
        
        return $clusters;
    }
}

/** ZigZag  */
class ZigZag extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $deviation = $params['deviation'] ?? 5; // 5% deviation
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        
        $result = [];
        $lastPivot = 0;
        $lastPivotPrice = $highs[0];
        $isHigh = true;
        
        for ($i = 1; $i < count($data); $i++) {
            if ($isHigh) {
                if ($highs[$i] > $lastPivotPrice) {
                    $lastPivotPrice = $highs[$i];
                    $lastPivot = $i;
                } elseif (($lastPivotPrice - $lows[$i]) / $lastPivotPrice * 100 >= $deviation) {
                    $result[$lastPivot] = $lastPivotPrice;
                    $lastPivotPrice = $lows[$i];
                    $lastPivot = $i;
                    $isHigh = false;
                }
            } else {
                if ($lows[$i] < $lastPivotPrice) {
                    $lastPivotPrice = $lows[$i];
                    $lastPivot = $i;
                } elseif (($highs[$i] - $lastPivotPrice) / $lastPivotPrice * 100 >= $deviation) {
                    $result[$lastPivot] = $lastPivotPrice;
                    $lastPivotPrice = $highs[$i];
                    $lastPivot = $i;
                    $isHigh = true;
                }
            }
        }
        
        return $result;
    }
}
