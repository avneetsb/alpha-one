<?php

namespace TradingPlatform\Domain\Indicators\Types;

use TradingPlatform\Domain\Indicators\AbstractIndicator;
use TradingPlatform\Domain\MarketData\Models\Candle;

/**
 * Relative Strength Index (RSI)
 *
 * Momentum oscillator that measures the speed and change of price movements.
 * Returns values between 0 and 100; traditionally, RSI above 70 indicates
 * overbought and below 30 indicates oversold conditions.
 *
 * @package TradingPlatform\Domain\Indicators\Types
 * @version 1.0.0
 *
 * @config period int Required; number of periods (e.g., 14)
 *
 * @example Usage:
 * $rsi = new RSI(['period' => 14]);
 * $value = $rsi->calculate($currentCandle, $previousCandles);
 */
class RSI extends AbstractIndicator
{
    public function validateConfig(array $config): bool
    {
        return isset($config['period']) && $config['period'] > 0;
    }

    public function calculate(Candle $candle, array $previousCandles)
    {
        $period = $this->config['period'];
        
        // Need at least period + 1 candles to calculate RSI
        if (count($previousCandles) < $period) {
            return null;
        }

        // RSI calculation steps:
        // 1. Calculate gains and losses
        // 2. Compute average gain and average loss
        // 3. RS = Avg Gain / Avg Loss
        // 4. RSI = 100 - (100 / (1 + RS))
        
        // Simplified implementation for brevity
        $gains = 0;
        $losses = 0;
        
        $relevantCandles = array_slice($previousCandles, -$period);
        
        foreach ($relevantCandles as $i => $c) {
            if ($i === 0) continue;
            $prev = $relevantCandles[$i-1];
            $change = $c->close - $prev->close;
            
            if ($change > 0) $gains += $change;
            else $losses += abs($change);
        }
        
        $avgGain = $gains / $period;
        $avgLoss = $losses / $period;
        
        if ($avgLoss == 0) return 100;
        
        $rs = $avgGain / $avgLoss;
        return 100 - (100 / (1 + $rs));
    }
}
