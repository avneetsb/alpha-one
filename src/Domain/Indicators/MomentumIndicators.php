<?php

namespace TradingPlatform\Domain\Indicators;

/**
 * MOMENTUM INDICATORS
 * All oscillators and momentum-based indicators
 */

/** Relative Strength Index */
class RSI extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 14;
        $prices = $this->extractClosePrices($data);
        
        $gains = [];
        $losses = [];
        
        for ($i = 1; $i < count($prices); $i++) {
            $change = $prices[$i] - $prices[$i - 1];
            $gains[$i] = $change > 0 ? $change : 0;
            $losses[$i] = $change < 0 ? abs($change) : 0;
        }
        
        $result = [];
        $avgGain = array_sum(array_slice($gains, 1, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 1, $period)) / $period;
        
        for ($i = $period; $i < count($prices); $i++) {
            $avgGain = (($avgGain * ($period - 1)) + $gains[$i]) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $losses[$i]) / $period;
            
            if ($avgLoss == 0) {
                $result[$i] = 100;
            } else {
                $rs = $avgGain / $avgLoss;
                $result[$i] = 100 - (100 / (1 + $rs));
            }
        }
        
        return $result;
    }
}

/** MACD */
class MACD extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $fastPeriod = $params['fast'] ?? 12;
        $slowPeriod = $params['slow'] ?? 26;
        $signalPeriod = $params['signal'] ?? 9;
        
        $ema = new EMA();
        $fastEMA = $ema->calculate($data, ['period' => $fastPeriod]);
        $slowEMA = $ema->calculate($data, ['period' => $slowPeriod]);
        
        $macdLine = [];
        foreach ($fastEMA as $i => $fast) {
            if (isset($slowEMA[$i])) {
                $macdLine[$i] = $fast - $slowEMA[$i];
            }
        }
        
        $macdData = [];
        foreach ($macdLine as $value) {
            $macdData[] = ['close' => $value];
        }
        
        $signalLine = $ema->calculate($macdData, ['period' => $signalPeriod]);
        
        $histogram = [];
        foreach ($macdLine as $i => $macd) {
            if (isset($signalLine[$i])) {
                $histogram[$i] = $macd - $signalLine[$i];
            }
        }
        
        return ['macd' => $macdLine, 'signal' => $signalLine, 'histogram' => $histogram];
    }
}

/** Stochastic Oscillator */
class Stochastic extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $kPeriod = $params['k_period'] ?? 14;
        $dPeriod = $params['d_period'] ?? 3;
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);
        
        $percentK = [];
        
        for ($i = $kPeriod - 1; $i < count($data); $i++) {
            $highestHigh = max(array_slice($highs, $i - $kPeriod + 1, $kPeriod));
            $lowestLow = min(array_slice($lows, $i - $kPeriod + 1, $kPeriod));
            
            if ($highestHigh != $lowestLow) {
                $percentK[$i] = (($closes[$i] - $lowestLow) / ($highestHigh - $lowestLow)) * 100;
            } else {
                $percentK[$i] = 50;
            }
        }
        
        $percentD = [];
        $kValues = array_values($percentK);
        for ($i = $dPeriod - 1; $i < count($kValues); $i++) {
            $percentD[] = array_sum(array_slice($kValues, $i - $dPeriod + 1, $dPeriod)) / $dPeriod;
        }
        
        return ['k' => $percentK, 'd' => $percentD];
    }
}

/** Average Directional Index */
class ADX extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 14;
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);
        
        $plusDM = [];
        $minusDM = [];
        $tr = [];
        
        for ($i = 1; $i < count($data); $i++) {
            $highDiff = $highs[$i] - $highs[$i - 1];
            $lowDiff = $lows[$i - 1] - $lows[$i];
            
            $plusDM[$i] = ($highDiff > $lowDiff && $highDiff > 0) ? $highDiff : 0;
            $minusDM[$i] = ($lowDiff > $highDiff && $lowDiff > 0) ? $lowDiff : 0;
            
            $tr1 = $highs[$i] - $lows[$i];
            $tr2 = abs($highs[$i] - $closes[$i - 1]);
            $tr3 = abs($lows[$i] - $closes[$i - 1]);
            $tr[$i] = max($tr1, $tr2, $tr3);
        }
        
        $smoothPlusDM = [];
        $smoothMinusDM = [];
        $smoothTR = [];
        $result = [];
        
        for ($i = $period; $i < count($data); $i++) {
            if ($i == $period) {
                $smoothPlusDM[$i] = array_sum(array_slice($plusDM, 1, $period));
                $smoothMinusDM[$i] = array_sum(array_slice($minusDM, 1, $period));
                $smoothTR[$i] = array_sum(array_slice($tr, 1, $period));
            } else {
                $smoothPlusDM[$i] = $smoothPlusDM[$i - 1] - ($smoothPlusDM[$i - 1] / $period) + $plusDM[$i];
                $smoothMinusDM[$i] = $smoothMinusDM[$i - 1] - ($smoothMinusDM[$i - 1] / $period) + $minusDM[$i];
                $smoothTR[$i] = $smoothTR[$i - 1] - ($smoothTR[$i - 1] / $period) + $tr[$i];
            }
            
            $plusDI = $smoothTR[$i] > 0 ? 100 * $smoothPlusDM[$i] / $smoothTR[$i] : 0;
            $minusDI = $smoothTR[$i] > 0 ? 100 * $smoothMinusDM[$i] / $smoothTR[$i] : 0;
            
            $dx = ($plusDI + $minusDI) > 0 ? 100 * abs($plusDI - $minusDI) / ($plusDI + $minusDI) : 0;
            
            if ($i == $period * 2 - 1) {
                $result[$i] = array_sum(array_slice([$dx], -$period)) / $period;
            } elseif ($i > $period * 2 - 1) {
                $result[$i] = (($result[$i - 1] * ($period - 1)) + $dx) / $period;
            }
        }
        
        return $result;
    }
}

/** Commodity Channel Index */
class CCI extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);

        $typicalPrices = [];
        foreach ($data as $i => $candle) {
            $typicalPrices[$i] = ($highs[$i] + $lows[$i] + $closes[$i]) / 3;
        }

        $result = [];

        for ($i = $period - 1; $i < count($typicalPrices); $i++) {
            $subset = array_slice($typicalPrices, $i - $period + 1, $period);
            $sma = array_sum($subset) / $period;
            
            $meanDeviation = 0;
            foreach ($subset as $value) {
                $meanDeviation += abs($value - $sma);
            }
            $meanDeviation /= $period;

            $result[$i] = $meanDeviation != 0 ? ($typicalPrices[$i] - $sma) / (0.015 * $meanDeviation) : 0;
        }

        return $result;
    }
}

/** Williams %R */
class WilliamsR extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 14;
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);

        $result = [];

        for ($i = $period - 1; $i < count($data); $i++) {
            $highestHigh = max(array_slice($highs, $i - $period + 1, $period));
            $lowestLow = min(array_slice($lows, $i - $period + 1, $period));

            if ($highestHigh != $lowestLow) {
                $result[$i] = (($highestHigh - $closes[$i]) / ($highestHigh - $lowestLow)) * -100;
            } else {
                $result[$i] = 0;
            }
        }

        return $result;
    }
}

/** True Strength Index */
class TSI extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $longPeriod = $params['long'] ?? 25;
        $shortPeriod = $params['short'] ?? 13;
        
        $closes = $this->extractClosePrices($data);

        $momentum = [];
        for ($i = 1; $i < count($closes); $i++) {
            $momentum[$i] = $closes[$i] - $closes[$i - 1];
        }

        $firstSmooth = $this->emaOfArray($momentum, $longPeriod);
        $doubleSmooth = $this->emaOfArray($firstSmooth, $shortPeriod);

        $absMomentum = array_map('abs', $momentum);
        $firstSmoothAbs = $this->emaOfArray($absMomentum, $longPeriod);
        $doubleSmoothAbs = $this->emaOfArray($firstSmoothAbs, $shortPeriod);

        $result = [];
        foreach ($doubleSmooth as $i => $value) {
            if (isset($doubleSmoothAbs[$i]) && $doubleSmoothAbs[$i] != 0) {
                $result[$i] = 100 * ($value / $doubleSmoothAbs[$i]);
            }
        }

        return $result;
    }

    private function emaOfArray(array $values, int $period): array
    {
        $result = [];
        $multiplier = 2 / ($period + 1);
        
        $sum = 0;
        $count = 0;
        foreach ($values as $i => $value) {
            if ($count < $period) {
                $sum += $value;
                $count++;
                if ($count == $period) {
                    $result[$i] = $sum / $period;
                }
            } else {
                $result[$i] = ($value - $result[$i - 1]) * $multiplier + $result[$i - 1];
            }
        }

        return $result;
    }
}

/** Aroon Oscillator */
class AroonOscillator extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 25;
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        
        $aroonUp = [];
        $aroonDown = [];
        $aroonOsc = [];
        
        for ($i = $period - 1; $i < count($data); $i++) {
            $highestIndex = $i;
            $lowestIndex = $i;
            
            for ($j = 0; $j < $period; $j++) {
                if ($highs[$i - $j] >= $highs[$highestIndex]) {
                    $highestIndex = $i - $j;
                }
                if ($lows[$i - $j] <= $lows[$lowestIndex]) {
                    $lowestIndex = $i - $j;
                }
            }
            
            $aroonUp[$i] = (($period - ($i - $highestIndex)) / $period) * 100;
            $aroonDown[$i] = (($period - ($i - $lowestIndex)) / $period) * 100;
            $aroonOsc[$i] = $aroonUp[$i] - $aroonDown[$i];
        }
        
        return ['up' => $aroonUp, 'down' => $aroonDown, 'oscillator' => $aroonOsc];
    }
}

/** Ultimate Oscillator */
class UltimateOscillator extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period1 = $params['period1'] ?? 7;
        $period2 = $params['period2'] ?? 14;
        $period3 = $params['period3'] ?? 28;
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);
        
        $result = [];
        
        for ($i = max($period1, $period2, $period3); $i < count($data); $i++) {
            $bp1 = $bp2 = $bp3 = 0;
            $tr1 = $tr2 = $tr3 = 0;
            
            for ($j = 0; $j < max($period1, $period2, $period3); $j++) {
                $idx = $i - $j;
                if ($idx < 1) continue;
                
                $bp = $closes[$idx] - min($lows[$idx], $closes[$idx - 1]);
                $tr = max($highs[$idx], $closes[$idx - 1]) - min($lows[$idx], $closes[$idx - 1]);
                
                if ($j < $period1) {
                    $bp1 += $bp;
                    $tr1 += $tr;
                }
                if ($j < $period2) {
                    $bp2 += $bp;
                    $tr2 += $tr;
                }
                if ($j < $period3) {
                    $bp3 += $bp;
                    $tr3 += $tr;
                }
            }
            
            $avg1 = $tr1 > 0 ? $bp1 / $tr1 : 0;
            $avg2 = $tr2 > 0 ? $bp2 / $tr2 : 0;
            $avg3 = $tr3 > 0 ? $bp3 / $tr3 : 0;
            
            $result[$i] = 100 * ((4 * $avg1) + (2 * $avg2) + $avg3) / 7;
        }
        
        return $result;
    }
}

/** Awesome Oscillator */
class AwesomeOscillator extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        
        $medianPrices = [];
        foreach ($data as $i => $candle) {
            $medianPrices[] = ['close' => ($highs[$i] + $lows[$i]) / 2];
        }
        
        $sma = new SMA();
        $sma5 = $sma->calculate($medianPrices, ['period' => 5]);
        $sma34 = $sma->calculate($medianPrices, ['period' => 34]);
        
        $result = [];
        foreach ($sma5 as $i => $value) {
            if (isset($sma34[$i])) {
                $result[$i] = $value - $sma34[$i];
            }
        }
        
        return $result;
    }
}

/** Chande Momentum Oscillator */
class CMO extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 14;
        $prices = $this->extractClosePrices($data);
        
        $result = [];
        
        for ($i = $period; $i < count($prices); $i++) {
            $upSum = 0;
            $downSum = 0;
            
            for ($j = 1; $j <= $period; $j++) {
                $change = $prices[$i - $j + 1] - $prices[$i - $j];
                if ($change > 0) {
                    $upSum += $change;
                } else {
                    $downSum += abs($change);
                }
            }
            
            if (($upSum + $downSum) > 0) {
                $result[$i] = 100 * (($upSum - $downSum) / ($upSum + $downSum));
            } else {
                $result[$i] = 0;
            }
        }
        
        return $result;
    }
}

/** Detrended Price Oscillator */
class DPO extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $prices = $this->extractClosePrices($data);
        
        $sma = new SMA();
        $smaValues = $sma->calculate($data, ['period' => $period]);
        
        $displacement = (int)($period / 2) + 1;
        $result = [];
        
        for ($i = $period - 1 + $displacement; $i < count($prices); $i++) {
            if (isset($smaValues[$i - $displacement])) {
                $result[$i] = $prices[$i] - $smaValues[$i - $displacement];
            }
        }
        
        return $result;
    }
}

/** Know Sure Thing */
class KST extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $roc1 = $params['roc1'] ?? 10;
        $roc2 = $params['roc2'] ?? 15;
        $roc3 = $params['roc3'] ?? 20;
        $roc4 = $params['roc4'] ?? 30;
        $sma1 = $params['sma1'] ?? 10;
        $sma2 = $params['sma2'] ?? 10;
        $sma3 = $params['sma3'] ?? 10;
        $sma4 = $params['sma4'] ?? 15;
        $signalPeriod = $params['signal'] ?? 9;
        
        $prices = $this->extractClosePrices($data);
        
        $rocValues = [];
        for ($n = 1; $n <= 4; $n++) {
            $period = ${'roc' . $n};
            $rocValues[$n] = [];
            for ($i = $period; $i < count($prices); $i++) {
                $rocValues[$n][$i] = (($prices[$i] - $prices[$i - $period]) / $prices[$i - $period]) * 100;
            }
        }
        
        $sma = new SMA();
        $smoothedROC = [];
        for ($n = 1; $n <= 4; $n++) {
            $rocData = [];
            foreach ($rocValues[$n] as $value) {
                $rocData[] = ['close' => $value];
            }
            $smoothedROC[$n] = $sma->calculate($rocData, ['period' => ${'sma' . $n}]);
        }
        
        $kst = [];
        $maxStart = max(array_keys($smoothedROC[1]));
        for ($i = $maxStart; $i < count($data); $i++) {
            if (isset($smoothedROC[1][$i], $smoothedROC[2][$i], $smoothedROC[3][$i], $smoothedROC[4][$i])) {
                $kst[$i] = $smoothedROC[1][$i] * 1 
                         + $smoothedROC[2][$i] * 2 
                         + $smoothedROC[3][$i] * 3 
                         + $smoothedROC[4][$i] * 4;
            }
        }
        
        $kstData = [];
        foreach ($kst as $value) {
            $kstData[] = ['close' => $value];
        }
        $signal = $sma->calculate($kstData, ['period' => $signalPeriod]);
        
        return ['kst' => $kst, 'signal' => $signal];
    }
}

/** Fisher Transform */
class FisherTransform extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 10;
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        
        $value = [];
        $fisher = [];
        $trigger = [];
        
        for ($i = $period - 1; $i < count($data); $i++) {
            $highestHigh = max(array_slice($highs, $i - $period + 1, $period));
            $lowestLow = min(array_slice($lows, $i - $period + 1, $period));
            
            $hl2 = ($highs[$i] + $lows[$i]) / 2;
            
            if ($highestHigh != $lowestLow) {
                $rawValue = ($hl2 - $lowestLow) / ($highestHigh - $lowestLow) * 2 - 1;
            } else {
                $rawValue = 0;
            }
            
            if ($i == $period - 1) {
                $value[$i] = $rawValue;
            } else {
                $value[$i] = 0.33 * $rawValue + 0.67 * $value[$i - 1];
            }
            
            $value[$i] = max(min($value[$i], 0.999), -0.999);
            
            if ($i == $period - 1) {
                $fisher[$i] = 0.5 * log((1 + $value[$i]) / (1 - $value[$i]));
            } else {
                $fisher[$i] = 0.5 * log((1 + $value[$i]) / (1 - $value[$i])) + 0.5 * $fisher[$i - 1];
            }
            
            if ($i > $period - 1) {
                $trigger[$i] = $fisher[$i - 1];
            }
        }
        
        return ['fisher' => $fisher, 'trigger' => $trigger];
    }
}

/** Rate of Change */
class ROC extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 12;
        $prices = $this->extractClosePrices($data);
        
        $result = [];
        
        for ($i = $period; $i < count($prices); $i++) {
            if ($prices[$i - $period] != 0) {
                $result[$i] = (($prices[$i] - $prices[$i - $period]) / $prices[$i - $period]) * 100;
            }
        }
        
        return $result;
    }
}

/** Momentum */
class Momentum extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 10;
        $prices = $this->extractClosePrices($data);
        
        $result = [];
        
        for ($i = $period; $i < count($prices); $i++) {
            $result[$i] = $prices[$i] - $prices[$i - $period];
        }
        
        return $result;
    }
}

/** Stochastic Momentum Index (SMI) */
class StochasticMomentumIndex extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $kPeriod = $params['k_period'] ?? 5;
        $dPeriod = $params['d_period'] ?? 3;
        $smoothPeriod = $params['smooth'] ?? 5;
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);
        
        $smi = [];
        $distance = [];
        
        for ($i = $kPeriod - 1; $i < count($data); $i++) {
            $highestHigh = max(array_slice($highs, $i - $kPeriod + 1, $kPeriod));
            $lowestLow = min(array_slice($lows, $i - $kPeriod + 1, $kPeriod));
            
            $median = ($highestHigh + $lowestLow) / 2;
            $distance[$i] = $closes[$i] - $median;
            $range = $highestHigh - $lowestLow;
            
            if ($range != 0) {
                $smi[$i] = ($distance[$i] / ($range / 2)) * 100;
            } else {
                $smi[$i] = 0;
            }
        }
        
        // Apply double smoothing with EMA
        $smiData = [];
        foreach ($smi as $value) {
            $smiData[] = ['close' => $value];
        }
        
        $ema = new EMA();
        $smoothed1 = $ema->calculate($smiData, ['period' => $smoothPeriod]);
        
        $smoothed1Data = [];
        foreach ($smoothed1 as $value) {
            $smoothed1Data[] = ['close' => $value];
        }
        $smoothedSMI = $ema->calculate($smoothed1Data, ['period' => $smoothPeriod]);
        
        // Signal line
        $signalData = [];
        foreach ($smoothedSMI as $value) {
            $signalData[] = ['close' => $value];
        }
        $signal = $ema->calculate($signalData, ['period' => $dPeriod]);
        
        return ['smi' => $smoothedSMI, 'signal' => $signal];
    }
}

/** Percentage Price Oscillator (PPO) */
class PPO extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $fastPeriod = $params['fast'] ?? 12;
        $slowPeriod = $params['slow'] ?? 26;
        $signalPeriod = $params['signal'] ?? 9;
        
        $ema = new EMA();
        $fastEMA = $ema->calculate($data, ['period' => $fastPeriod]);
        $slowEMA = $ema->calculate($data, ['period' => $slowPeriod]);
        
        $ppo = [];
        foreach ($fastEMA as $i => $fast) {
            if (isset($slowEMA[$i]) && $slowEMA[$i] != 0) {
                $ppo[$i] = (($fast - $slowEMA[$i]) / $slowEMA[$i]) * 100;
            }
        }
        
        // Signal line
        $ppoData = [];
        foreach ($ppo as $value) {
            $ppoData[] = ['close' => $value];
        }
        $signal = $ema->calculate($ppoData, ['period' => $signalPeriod]);
        
        // Histogram
        $histogram = [];
        foreach ($ppo as $i => $value) {
            if (isset($signal[$i])) {
                $histogram[$i] = $value - $signal[$i];
            }
        }
        
        return ['ppo' => $ppo, 'signal' => $signal, 'histogram' => $histogram];
    }
}

/** Balance of Power (BOP) */
class BalanceOfPower extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $smoothPeriod = $params['smooth'] ?? 14;
        
        $opens = array_column($data, 'open');
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);
        
        $bop = [];
        
        for ($i = 0; $i < count($data); $i++) {
            $range = $highs[$i] - $lows[$i];
            if ($range != 0) {
                $bop[$i] = ($closes[$i] - $opens[$i]) / $range;
            } else {
                $bop[$i] = 0;
            }
        }
        
        // Apply SMA smoothing
        if ($smoothPeriod > 1) {
            $bopData = [];
            foreach ($bop as $value) {
                $bopData[] = ['close' => $value];
            }
            
            $sma = new SMA();
            return $sma->calculate($bopData, ['period' => $smoothPeriod]);
        }
        
        return $bop;
    }
}

/** Stochastic RSI (StochRSI) */
class StochRSI extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $rsiPeriod = $params['rsi_period'] ?? 14;
        $stochPeriod = $params['stoch_period'] ?? 14;
        $kSmooth = $params['k_smooth'] ?? 3;
        $dSmooth = $params['d_smooth'] ?? 3;
        
        // First calculate RSI
        $rsiCalc = new RSI();
        $rsiValues = $rsiCalc->calculate($data, ['period' => $rsiPeriod]);
        
        // Apply Stochastic formula to RSI values
        $stochRSI = [];
        $rsiArray = array_values($rsiValues);
        
        for ($i = $stochPeriod - 1; $i < count($rsiArray); $i++) {
            $subset = array_slice($rsiArray, $i - $stochPeriod + 1, $stochPeriod);
            $highestRSI = max($subset);
            $lowestRSI = min($subset);
            
            if ($highestRSI != $lowestRSI) {
                $stochRSI[$i] = (($rsiArray[$i] - $lowestRSI) / ($highestRSI - $lowestRSI)) * 100;
            } else {
                $stochRSI[$i] = 50;
            }
        }
        
        // Smooth %K
        $k = [];
        $stochValues = array_values($stochRSI);
        for ($i = $kSmooth - 1; $i < count($stochValues); $i++) {
            $k[] = array_sum(array_slice($stochValues, $i - $kSmooth + 1, $kSmooth)) / $kSmooth;
        }
        
        // Smooth %D
        $d = [];
        for ($i = $dSmooth - 1; $i < count($k); $i++) {
            $d[] = array_sum(array_slice($k, $i - $dSmooth + 1, $dSmooth)) / $dSmooth;
        }
        
        return ['k' => $k, 'd' => $d];
    }
}

/** Connors RSI (CRSI) */
class ConnorsRSI extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $rsiPeriod = $params['rsi_period'] ?? 3;
        $streakPeriod = $params['streak_period'] ?? 2;
        $pctRankPeriod = $params['rank_period'] ?? 100;
        
        $closes = $this->extractClosePrices($data);
        
        // Component 1: Short-term RSI
        $rsiCalc = new RSI();
        $rsi = $rsiCalc->calculate($data, ['period' => $rsiPeriod]);
        
        // Component 2: Streak (up/down length)
        $streak = [];
        $currentStreak = 0;
        for ($i = 1; $i < count($closes); $i++) {
            if ($closes[$i] > $closes[$i - 1]) {
                $currentStreak = $currentStreak > 0 ? $currentStreak + 1 : 1;
            } elseif ($closes[$i] < $closes[$i - 1]) {
                $currentStreak = $currentStreak < 0 ? $currentStreak - 1 : -1;
            }
            $streak[$i] = $currentStreak;
        }
        
        // Apply RSI to streak
        $streakData = [];
        foreach ($streak as $i => $value) {
            $streakData[] = ['close' => $value];
        }
        $streakRSI = $rsiCalc->calculate($streakData, ['period' => $streakPeriod]);
        
        // Component 3: Percent Rank of ROC
        $roc = [];
        for ($i = 1; $i < count($closes); $i++) {
            $roc[$i] = (($closes[$i] - $closes[$i - 1]) / $closes[$i - 1]) * 100;
        }
        
        $pctRank = [];
        for ($i = $pctRankPeriod; $i < count($roc); $i++) {
            $subset = array_slice($roc, $i - $pctRankPeriod, $pctRankPeriod);
            $currentROC = $roc[$i];
            $count = 0;
            foreach ($subset as $val) {
                if ($val < $currentROC) $count++;
            }
            $pctRank[$i] = ($count / $pctRankPeriod) * 100;
        }
        
        // Combine all three components
        $crsi = [];
        foreach ($rsi as $i => $value) {
            if (isset($streakRSI[$i], $pctRank[$i])) {
                $crsi[$i] = ($value + $streakRSI[$i] + $pctRank[$i]) / 3;
            }
        }
        
        return $crsi;
    }
}

/** Smoothed RSI */
class SmoothedRSI extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $rsiPeriod = $params['rsi_period'] ?? 14;
        $smoothPeriod = $params['smooth_period'] ?? 5;
        $smoothType = $params['smooth_type'] ?? 'sma'; // 'sma' or 'ema'
        
        // Calculate RSI
        $rsiCalc = new RSI();
        $rsiValues = $rsiCalc->calculate($data, ['period' => $rsiPeriod]);
        
        // Convert to data format for smoothing
        $rsiData = [];
        foreach ($rsiValues as $value) {
            $rsiData[] = ['close' => $value];
        }
        
        // Apply smoothing
        if ($smoothType === 'ema') {
            $ema = new EMA();
            return $ema->calculate($rsiData, ['period' => $smoothPeriod]);
        } else {
            $sma = new SMA();
            return $sma->calculate($rsiData, ['period' => $smoothPeriod]);
        }
    }
}

/** RSI EMA (RSI calculated using EMA instead of SMMA) */
class RSIEMA extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 14;
        $prices = $this->extractClosePrices($data);
        
        $gains = [];
        $losses = [];
        
        for ($i = 1; $i < count($prices); $i++) {
            $change = $prices[$i] - $prices[$i - 1];
            $gains[] = ['close' => $change > 0 ? $change : 0];
            $losses[] = ['close' => $change < 0 ? abs($change) : 0];
        }
        
        // Use EMA for gains and losses
        $ema = new EMA();
        $avgGain = $ema->calculate($gains, ['period' => $period]);
        $avgLoss = $ema->calculate($losses, ['period' => $period]);
        
        $result = [];
        foreach ($avgGain as $i => $gain) {
            if (isset($avgLoss[$i])) {
                if ($avgLoss[$i] == 0) {
                    $result[$i] = 100;
                } else {
                    $rs = $gain / $avgLoss[$i];
                   $result[$i] = 100 - (100 / (1 + $rs));
                }
            }
        }
        
        return $result;
    }
}

/** Zero Lag MACD */
class ZeroLagMACD extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $fastPeriod = $params['fast'] ?? 12;
        $slowPeriod = $params['slow'] ?? 26;
        $signalPeriod = $params['signal'] ?? 9;
        
        $prices = $this->extractClosePrices($data);
        
        // Calculate zero-lag EMAs
        $fastZL = $this->zeroLagEMA($prices, $fastPeriod);
        $slowZL = $this->zeroLagEMA($prices, $slowPeriod);
        
        // MACD Line
        $macdLine = [];
        foreach ($fastZL as $i => $fast) {
            if (isset($slowZL[$i])) {
                $macdLine[$i] = $fast - $slowZL[$i];
            }
        }
        
        // Signal Line (apply zero-lag EMA to MACD)
        $signalLine = $this->zeroLagEMA($macdLine, $signalPeriod);
        
        // Histogram
        $histogram = [];
        foreach ($macdLine as $i => $macd) {
            if (isset($signalLine[$i])) {
                $histogram[$i] = $macd - $signalLine[$i];
            }
        }
        
        return ['macd' => $macdLine, 'signal' => $signalLine, 'histogram' => $histogram];
    }
    
    private function zeroLagEMA(array $values, int $period): array
    {
        $lag = (int)(($period - 1) / 2);
        $ema1 = [];
        $ema2 = [];
        $zlema = [];
        
        $multiplier = 2 / ($period + 1);
        
        // Calculate regular EMA
        if (count($values) < 1) return [];
        
        $validKeys = array_keys($values);
        $ema1[$validKeys[0]] = $values[$validKeys[0]];
        
        for ($i = 1; $i < count($validKeys); $i++) {
            $key = $validKeys[$i];
            $prevKey = $validKeys[$i - 1];
            $ema1[$key] = ($values[$key] - $ema1[$prevKey]) * $multiplier + $ema1[$prevKey];
        }
        
        // Calculate de-lagged values
        foreach ($values as $i => $value) {
            if ($i >= $lag && isset($values[$i - $lag])) {
                $delag = 2 * $value - $values[$i - $lag];
                $ema2[$i] = $delag;
            }
        }
        
        // Apply EMA to de-lagged values
        $ema2Keys = array_keys($ema2);
        if (empty($ema2Keys)) return $ema1;
        
        $zlema[$ema2Keys[0]] = $ema2[$ema2Keys[0]];
        
        for ($i = 1; $i < count($ema2Keys); $i++) {
            $key = $ema2Keys[$i];
            $prevKey = $ema2Keys[$i - 1];
            $zlema[$key] = ($ema2[$key] - $zlema[$prevKey]) * $multiplier + $zlema[$prevKey];
        }
        
        return $zlema;
    }
}

/** Slow Stochastic */
class SlowStochastic extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $kPeriod = $params['k_period'] ?? 14;
        $kSlowing = $params['k_slowing'] ?? 3;
        $dPeriod = $params['d_period'] ?? 3;
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);
        
        // Calculate raw %K (Fast %K)
        $fastK = [];
        for ($i = $kPeriod - 1; $i < count($data); $i++) {
            $highestHigh = max(array_slice($highs, $i - $kPeriod + 1, $kPeriod));
            $lowestLow = min(array_slice($lows, $i - $kPeriod + 1, $kPeriod));
            
            if ($highestHigh != $lowestLow) {
                $fastK[$i] = (($closes[$i] - $lowestLow) / ($highestHigh - $lowestLow)) * 100;
            } else {
                $fastK[$i] = 50;
            }
        }
        
        // Slow %K is SMA of Fast %K
        $slowK = [];
        $fastKValues = array_values($fastK);
        for ($i = $kSlowing - 1; $i < count($fastKValues); $i++) {
            $slowK[] = array_sum(array_slice($fastKValues, $i - $kSlowing + 1, $kSlowing)) / $kSlowing;
        }
        
        // Slow %D is SMA of Slow %K
        $slowD = [];
        for ($i = $dPeriod - 1; $i < count($slowK); $i++) {
            $slowD[] = array_sum(array_slice($slowK, $i - $dPeriod + 1, $dPeriod)) / $dPeriod;
        }
        
        return ['k' => $slowK, 'd' => $slowD];
    }
}

/** Fast Stochastic */
class FastStochastic extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $kPeriod = $params['k_period'] ?? 14;
        $dPeriod = $params['d_period'] ?? 3;
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);
        
        // Fast %K (raw stochastic)
        $fastK = [];
        for ($i = $kPeriod - 1; $i < count($data); $i++) {
            $highestHigh = max(array_slice($highs, $i - $kPeriod + 1, $kPeriod));
            $lowestLow = min(array_slice($lows, $i - $kPeriod + 1, $kPeriod));
            
            if ($highestHigh != $lowestLow) {
                $fastK[$i] = (($closes[$i] - $lowestLow) / ($highestHigh - $lowestLow)) * 100;
            } else {
                $fastK[$i] = 50;
            }
        }
        
        // Fast %D is SMA of Fast %K
        $fastD = [];
        $kValues = array_values($fastK);
        for ($i = $dPeriod - 1; $i < count($kValues); $i++) {
            $fastD[] = array_sum(array_slice($kValues, $i - $dPeriod + 1, $dPeriod)) / $dPeriod;
        }
        
        return ['k' => array_values($fastK), 'd' => $fastD];
    }
}

/** Full Stochastic (Customizable Smoothing) */
class FullStochastic extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $kPeriod = $params['k_period'] ?? 14;
        $kSmooth = $params['k_smooth'] ?? 3;
        $dSmooth = $params['d_smooth'] ?? 3;
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);
        
        // Raw %K
        $rawK = [];
        for ($i = $kPeriod - 1; $i < count($data); $i++) {
            $highestHigh = max(array_slice($highs, $i - $kPeriod + 1, $kPeriod));
            $lowestLow = min(array_slice($lows, $i - $kPeriod + 1, $kPeriod));
            
            if ($highestHigh != $lowestLow) {
                $rawK[$i] = (($closes[$i] - $lowestLow) / ($highestHigh - $lowestLow)) * 100;
            } else {
                $rawK[$i] = 50;
            }
        }
        
        // Smoothed %K
        $fullK = [];
        $rawKValues = array_values($rawK);
        for ($i = $kSmooth - 1; $i < count($rawKValues); $i++) {
            $fullK[] = array_sum(array_slice($rawKValues, $i - $kSmooth + 1, $kSmooth)) / $kSmooth;
        }
        
        // Smoothed %D
        $fullD = [];
        for ($i = $dSmooth - 1; $i < count($fullK); $i++) {
            $fullD[] = array_sum(array_slice($fullK, $i - $dSmooth + 1, $dSmooth)) / $dSmooth;
        }
        
        return ['k' => $fullK, 'd' => $fullD];
    }
}

/** Schaff Trend Cycle */
class SchaffTrendCycle extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $cyclePeriod = $params['cycle'] ?? 10;
        $fastPeriod = $params['fast'] ?? 23;
        $slowPeriod = $params['slow'] ?? 50;
        
        // Calculate MACD
        $ema = new EMA();
        $fastEMA = $ema->calculate($data, ['period' => $fastPeriod]);
        $slowEMA = $ema->calculate($data, ['period' => $slowPeriod]);
        
        $macd = [];
        foreach ($fastEMA as $i => $fast) {
            if (isset($slowEMA[$i])) {
                $macd[$i] = $fast - $slowEMA[$i];
            }
        }
        
        // Apply stochastic to MACD
        $stoch1 = $this->applyStochastic(array_values($macd), $cyclePeriod);
        
        // Apply stochastic again
        $stc = $this->applyStochastic($stoch1, $cyclePeriod);
        
        return $stc;
    }
    
    private function applyStochastic(array $values, int $period): array
    {
        $result = [];
        
        for ($i = $period - 1; $i < count($values); $i++) {
            $subset = array_slice($values, $i - $period + 1, $period);
            $highest = max($subset);
            $lowest = min($subset);
            
            if ($highest != $lowest) {
                $result[] = (($values[$i] - $lowest) / ($highest - $lowest)) * 100;
            } else {
                $result[] = 50;
            }
        }
        
        return $result;
    }
}
