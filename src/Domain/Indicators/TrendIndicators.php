<?php

namespace TradingPlatform\Domain\Indicators;

/**
 * TREND INDICATORS
 * All moving averages, channels, and trend-following indicators
 */

/** Simple Moving Average */
class SMA extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $prices = $this->extractClosePrices($data);
        
        $result = [];
        
        for ($i = $period - 1; $i < count($prices); $i++) {
            $sum = 0;
            for ($j = 0; $j < $period; $j++) {
                $sum += $prices[$i - $j];
            }
            $result[$i] = $sum / $period;
        }
        
        return $result;
    }
}

/** Exponential Moving Average */
class EMA extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $prices = $this->extractClosePrices($data);
        
        $multiplier = 2 / ($period + 1);
        $result = [];
        
        // First EMA is SMA
        $sum = 0;
        for ($i = 0; $i < $period; $i++) {
            $sum += $prices[$i];
        }
        $result[$period - 1] = $sum / $period;
        
        // Calculate rest using EMA formula
        for ($i = $period; $i < count($prices); $i++) {
            $result[$i] = ($prices[$i] - $result[$i - 1]) * $multiplier + $result[$i - 1];
        }
        
        return $result;
    }
}

/** Double Exponential Moving Average */
class DEMA extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 30;
        
        $ema = new EMA();
        $ema1 = $ema->calculate($data, ['period' => $period]);
        
        $ema1Data = [];
        foreach ($ema1 as $i => $value) {
            $ema1Data[] = ['close' => $value];
        }
        
        $ema2 = $ema->calculate($ema1Data, ['period' => $period]);

        $result = [];
        foreach ($ema1 as $i => $value) {
            if (isset($ema2[$i])) {
                $result[$i] = (2 * $value) - $ema2[$i];
            }
        }

        return $result;
    }
}

/** Triple Exponential Moving Average */
class TEMA extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 30;
        
        $ema = new EMA();
        $ema1 = $ema->calculate($data, ['period' => $period]);
        
        $ema1Data = [];
        foreach ($ema1 as $value) {
            $ema1Data[] = ['close' => $value];
        }
        $ema2 = $ema->calculate($ema1Data, ['period' => $period]);
        
        $ema2Data = [];
        foreach ($ema2 as $value) {
            $ema2Data[] = ['close' => $value];
        }
        $ema3 = $ema->calculate($ema2Data, ['period' => $period]);

        $result = [];
        foreach ($ema1 as $i => $val1) {
            if (isset($ema2[$i], $ema3[$i])) {
                $result[$i] = (3 * $val1) - (3 * $ema2[$i]) + $ema3[$i];
            }
        }

        return $result;
    }
}

/** Hull Moving Average */
class HMA extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 9;
        $prices = $this->extractClosePrices($data);

        $halfPeriod = (int)($period / 2);
        $sqrtPeriod = (int)sqrt($period);

        $wmaHalf = $this->calculateWMA($prices, $halfPeriod);
        $wmaFull = $this->calculateWMA($prices, $period);

        $diff = [];
        foreach ($wmaHalf as $i => $val) {
            if (isset($wmaFull[$i])) {
                $diff[] = (2 * $val) - $wmaFull[$i];
            }
        }

        return $this->calculateWMA($diff, $sqrtPeriod);
    }

    private function calculateWMA(array $prices, int $period): array
    {
        $result = [];
        $count = count($prices);

        for ($i = $period - 1; $i < $count; $i++) {
            $weightedSum = 0;
            $weightSum = 0;

            for ($j = 0; $j < $period; $j++) {
                $weight = $period - $j;
                $weightedSum += $prices[$i - $j] * $weight;
                $weightSum += $weight;
            }

            $result[$i] = $weightedSum / $weightSum;
        }

        return $result;
    }
}

/** Weighted Moving Average */
class WMA extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $prices = $this->extractClosePrices($data);
        
        $result = [];
        
        for ($i = $period - 1; $i < count($prices); $i++) {
            $weightedSum = 0;
            $weightSum = 0;
            
            for ($j = 0; $j < $period; $j++) {
                $weight = $period - $j;
                $weightedSum += $prices[$i - $j] * $weight;
                $weightSum += $weight;
            }
            
            $result[$i] = $weightedSum / $weightSum;
        }
        
        return $result;
    }
}

/** Triangular Moving Average */
class TMA extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        
        $sma = new SMA();
        $firstSMA = $sma->calculate($data, ['period' => ceil($period / 2)]);
        
        $firstSMAData = [];
        foreach ($firstSMA as $value) {
            $firstSMAData[] = ['close' => $value];
        }
        
        return $sma->calculate($firstSMAData, ['period' => ceil($period / 2)]);
    }
}

/** Kaufman Adaptive Moving Average */
class KAMA extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 30;
        $fastPeriod = $params['fast'] ?? 2;
        $slowPeriod = $params['slow'] ?? 30;
        
        $prices = $this->extractClosePrices($data);
        
        $fastSC = 2 / ($fastPeriod + 1);
        $slowSC = 2 / ($slowPeriod + 1);
        
        $result = [];
        
        for ($i = $period; $i < count($prices); $i++) {
            $change = abs($prices[$i] - $prices[$i - $period]);
            $volatility = 0;
            for ($j = 0; $j < $period; $j++) {
                $volatility += abs($prices[$i - $j] - $prices[$i - $j - 1]);
            }
            
            $er = $volatility > 0 ? $change / $volatility : 0;
            $sc = pow(($er * ($fastSC - $slowSC) + $slowSC), 2);
            
            if ($i == $period) {
                $result[$i] = $prices[$i];
            } else {
                $result[$i] = $result[$i - 1] + $sc * ($prices[$i] - $result[$i - 1]);
            }
        }
        
        return $result;
    }
}

/** Zero-Lag Exponential Moving Average */
class ZLEMA extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $prices = $this->extractClosePrices($data);
        
        $lag = (int)(($period - 1) / 2);
        $emaData = [];
        
        for ($i = $lag; $i < count($prices); $i++) {
            $emaData[] = ['close' => 2 * $prices[$i] - $prices[$i - $lag]];
        }
        
        $ema = new EMA();
        return $ema->calculate($emaData, ['period' => $period]);
    }
}

/** Bollinger Bands */
class BollingerBands extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $stdDevMultiplier = $params['std_dev'] ?? 2;
        
        $prices = $this->extractClosePrices($data);
        $sma = new SMA();
        $middleBand = $sma->calculate($data, ['period' => $period]);
        
        $upperBand = [];
        $lowerBand = [];
        
        foreach ($middleBand as $i => $middle) {
            $subset = array_slice($prices, $i - $period + 1, $period);
            $variance = 0;
            foreach ($subset as $price) {
                $variance += pow($price - $middle, 2);
            }
            $stdDev = sqrt($variance / $period);
            
            $upperBand[$i] = $middle + ($stdDev * $stdDevMultiplier);
            $lowerBand[$i] = $middle - ($stdDev * $stdDevMultiplier);
        }
        
        return ['upper' => $upperBand, 'middle' => $middleBand, 'lower' => $lowerBand];
    }
}

/** Donchian Channels */
class DonchianChannels extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);

        $upper = [];
        $lower = [];
        $middle = [];

        for ($i = $period - 1; $i < count($data); $i++) {
            $upper[$i] = max(array_slice($highs, $i - $period + 1, $period));
            $lower[$i] = min(array_slice($lows, $i - $period + 1, $period));
            $middle[$i] = ($upper[$i] + $lower[$i]) / 2;
        }

        return ['upper' => $upper, 'middle' => $middle, 'lower' => $lower];
    }
}

/** Keltner Channel */
class KeltnerChannel extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $multiplier = $params['multiplier'] ?? 2.0;

        $emaCalc = new EMA();
        $middle = $emaCalc->calculate($data, ['period' => $period]);

        $atrCalc = new ATR();
        $atr = $atrCalc->calculate($data, ['period' => $period]);

        $upper = [];
        $lower = [];

        foreach ($middle as $i => $emaValue) {
            if (isset($atr[$i])) {
                $upper[$i] = $emaValue + ($multiplier * $atr[$i]);
                $lower[$i] = $emaValue - ($multiplier * $atr[$i]);
            }
        }

        return ['upper' => $upper, 'middle' => $middle, 'lower' => $lower];
    }
}

/** SuperTrend */
class SuperTrend extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 10;
        $multiplier = $params['multiplier'] ?? 3.0;

        $atrCalc = new ATR();
        $atr = $atrCalc->calculate($data, ['period' => $period]);

        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);

        $basicUpperBand = [];
        $basicLowerBand = [];
        $finalUpperBand = [];
        $finalLowerBand = [];
        $supertrend = [];
        $direction = [];

        foreach ($data as $i => $candle) {
            $hl2 = ($highs[$i] + $lows[$i]) / 2;

            if (isset($atr[$i])) {
                $basicUpperBand[$i] = $hl2 + ($multiplier * $atr[$i]);
                $basicLowerBand[$i] = $hl2 - ($multiplier * $atr[$i]);

                if ($i > 0) {
                    $finalUpperBand[$i] = ($basicUpperBand[$i] < $finalUpperBand[$i - 1] || $closes[$i - 1] > $finalUpperBand[$i - 1])
                        ? $basicUpperBand[$i]
                        : $finalUpperBand[$i - 1];

                    $finalLowerBand[$i] = ($basicLowerBand[$i] > $finalLowerBand[$i - 1] || $closes[$i - 1] < $finalLowerBand[$i - 1])
                        ? $basicLowerBand[$i]
                        : $finalLowerBand[$i - 1];
                } else {
                    $finalUpperBand[$i] = $basicUpperBand[$i];
                    $finalLowerBand[$i] = $basicLowerBand[$i];
                }

                if ($i > 0) {
                    if ($supertrend[$i - 1] == $finalUpperBand[$i - 1] && $closes[$i] <= $finalUpperBand[$i]) {
                        $supertrend[$i] = $finalUpperBand[$i];
                        $direction[$i] = -1;
                    } elseif ($supertrend[$i - 1] == $finalUpperBand[$i - 1] && $closes[$i] > $finalUpperBand[$i]) {
                        $supertrend[$i] = $finalLowerBand[$i];
                        $direction[$i] = 1;
                    } elseif ($supertrend[$i - 1] == $finalLowerBand[$i - 1] && $closes[$i] >= $finalLowerBand[$i]) {
                        $supertrend[$i] = $finalLowerBand[$i];
                        $direction[$i] = 1;
                    } elseif ($supertrend[$i - 1] == $finalLowerBand[$i - 1] && $closes[$i] < $finalLowerBand[$i]) {
                        $supertrend[$i] = $finalUpperBand[$i];
                        $direction[$i] = -1;
                    }
                } else {
                    $supertrend[$i] = $closes[$i] <= $hl2 ? $finalUpperBand[$i] : $finalLowerBand[$i];
                    $direction[$i] = $closes[$i] <= $hl2 ? -1 : 1;
                }
            }
        }

        return ['supertrend' => $supertrend, 'direction' => $direction];
    }
}

/** Alligator */
class Alligator extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $prices = $this->extractClosePrices($data);
        
        $jaw = $this->smma($prices, 13);
        $teeth = $this->smma($prices, 8);
        $lips = $this->smma($prices, 5);
        
        return ['jaw' => $jaw, 'teeth' => $teeth, 'lips' => $lips];
    }
    
    private function smma(array $prices, int $period): array
    {
        $result = [];
        $smma = array_sum(array_slice($prices, 0, $period)) / $period;
        $result[$period - 1] = $smma;
        
        for ($i = $period; $i < count($prices); $i++) {
            $smma = ($smma * ($period - 1) + $prices[$i]) / $period;
            $result[$i] = $smma;
        }
        
        return $result;
    }
}

/** Ichimoku Cloud */
class IchimokuCloud extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $conversionPeriod = $params['conversion'] ?? 9;
        $basePeriod = $params['base'] ?? 26;
        $laggingPeriod = $params['lagging'] ?? 52;
        $displacement = $params['displacement'] ?? 26;
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);
        
        $tenkan = [];
        $kijun = [];
        $senkouA = [];
        $senkouB = [];
        $chikou = [];
        
        for ($i = 0; $i < count($data); $i++) {
            if ($i >= $conversionPeriod - 1) {
                $periodHigh = max(array_slice($highs, $i - $conversionPeriod + 1, $conversionPeriod));
                $periodLow = min(array_slice($lows, $i - $conversionPeriod + 1, $conversionPeriod));
                $tenkan[$i] = ($periodHigh + $periodLow) / 2;
            }
            
            if ($i >= $basePeriod - 1) {
                $periodHigh = max(array_slice($highs, $i - $basePeriod + 1, $basePeriod));
                $periodLow = min(array_slice($lows, $i - $basePeriod + 1, $basePeriod));
                $kijun[$i] = ($periodHigh + $periodLow) / 2;
            }
            
            if ($i >= $basePeriod - 1 && isset($tenkan[$i], $kijun[$i])) {
                $senkouA[$i + $displacement] = ($tenkan[$i] + $kijun[$i]) / 2;
            }
            
            if ($i >= $laggingPeriod - 1) {
                $periodHigh = max(array_slice($highs, $i - $laggingPeriod + 1, $laggingPeriod));
                $periodLow = min(array_slice($lows, $i - $laggingPeriod + 1, $laggingPeriod));
                $senkouB[$i + $displacement] = ($periodHigh + $periodLow) / 2;
            }
            
            $chikou[$i - $displacement] = $closes[$i];
        }
        
        return [
            'tenkan' => $tenkan,
            'kijun' => $kijun,
            'senkou_a' => $senkouA,
            'senkou_b' => $senkouB,
            'chikou' => $chikou
        ];
    }
}

/** Parabolic SAR */
class ParabolicSAR extends Indicator  
{
    public function calculate(array $data, array $params = []): array
    {
        $af = $params['af'] ?? 0.02;
        $maxAf = $params['max_af'] ?? 0.2;
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        
        $sar = [];
        $trend = 1;
        $ep = $highs[0];
        $currentAf = $af;
        $sar[0] = $lows[0];
        
        for ($i = 1; $i < count($data); $i++) {
            $sar[$i] = $sar[$i - 1] + $currentAf * ($ep - $sar[$i - 1]);
            
            if ($trend == 1) {
                if ($lows[$i] < $sar[$i]) {
                    $trend = -1;
                    $sar[$i] = $ep;
                    $ep = $lows[$i];
                    $currentAf = $af;
                } else {
                    if ($highs[$i] > $ep) {
                        $ep = $highs[$i];
                        $currentAf = min($currentAf + $af, $maxAf);
                    }
                }
            } else {
                if ($highs[$i] > $sar[$i]) {
                    $trend = 1;
                    $sar[$i] = $ep;
                    $ep = $highs[$i];
                    $currentAf = $af;
                } else {
                    if ($lows[$i] < $ep) {
                        $ep = $lows[$i];
                        $currentAf = min($currentAf + $af, $maxAf);
                    }
                }
            }
        }
        
        return $sar;
    }
}

/** Heikin Ashi */
class HeikinAshi extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $opens = array_column($data, 'open');
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);
        
        $haOpen = [];
        $haHigh = [];
        $haLow = [];
        $haClose = [];
        
        for ($i = 0; $i < count($data); $i++) {
            $haClose[$i] = ($opens[$i] + $highs[$i] + $lows[$i] + $closes[$i]) / 4;
            
            if ($i == 0) {
                $haOpen[$i] = ($opens[$i] + $closes[$i]) / 2;
            } else {
                $haOpen[$i] = ($haOpen[$i - 1] + $haClose[$i - 1]) / 2;
            }
            
            $haHigh[$i] = max($highs[$i], $haOpen[$i], $haClose[$i]);
            $haLow[$i] = min($lows[$i], $haOpen[$i], $haClose[$i]);
        }
        
        return [
            'open' => $haOpen,
            'high' => $haHigh,
            'low' => $haLow,
            'close' => $haClose
        ];
    }
}

/** Linear Regression Channel */
class LinearRegression extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 100;
        $stdDevs = $params['std_devs'] ?? 2.0;
        
        $closes = $this->extractClosePrices($data);
        
        $slope = [];
        $intercept = [];
        $upper = [];
        $lower = [];
        $middle = [];
        
        for ($i = $period - 1; $i < count($closes); $i++) {
            $subset = array_slice($closes, $i - $period + 1, $period);
            
            // Calculate linear regression
            $n = count($subset);
            $sumX = 0;
            $sumY = 0;
            $sumXY = 0;
            $sumX2 = 0;
            
            for ($j = 0; $j < $n; $j++) {
                $sumX += $j;
                $sumY += $subset[$j];
                $sumXY += $j * $subset[$j];
                $sumX2 += $j * $j;
            }
            
            $slope[$i] = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
            $intercept[$i] = ($sumY - $slope[$i] * $sumX) / $n;
            
            // Calculate regression line value at current point
            $middle[$i] = $slope[$i] * ($n - 1) + $intercept[$i];
            
            // Calculate standard deviation
            $residuals = [];
            for ($j = 0; $j < $n; $j++) {
                $predicted = $slope[$i] * $j + $intercept[$i];
                $residuals[] = $subset[$j] - $predicted;
            }
            
            $variance = array_sum(array_map(fn($r) => $r ** 2, $residuals)) / $n;
            $stdDev = sqrt($variance);
            
            $upper[$i] = $middle[$i] + ($stdDev * $stdDevs);
            $lower[$i] = $middle[$i] - ($stdDev * $stdDevs);
        }
        
        return ['upper' => $upper, 'middle' => $middle, 'lower' => $lower, 'slope' => $slope];
    }
}

/** Vortex Indicator */
class VortexIndicator extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 14;
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);
        
        $viPlus = [];
        $viMinus = [];
        
        for ($i = $period; $i < count($data); $i++) {
            $vmPlus = 0;
            $vmMinus = 0;
            $trSum = 0;
            
            for ($j = 0; $j < $period; $j++) {
                $idx = $i - $j;
                if ($idx < 1) continue;
                
                // Vortex movements
                $vmPlus += abs($highs[$idx] - $lows[$idx - 1]);
                $vmMinus += abs($lows[$idx] - $highs[$idx - 1]);
                
                // True Range
                $tr1 = $highs[$idx] - $lows[$idx];
                $tr2 = abs($highs[$idx] - $closes[$idx - 1]);
                $tr3 = abs($lows[$idx] - $closes[$idx - 1]);
                $trSum += max($tr1, $tr2, $tr3);
            }
            
            $viPlus[$i] = $trSum > 0 ? $vmPlus / $trSum : 0;
            $viMinus[$i] = $trSum > 0 ? $vmMinus / $trSum : 0;
        }
        
        return ['vi_plus' => $viPlus, 'vi_minus' => $viMinus];
    }
}

/** Elder Ray Index (Bull Power & Bear Power) */
class ElderRay extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 13;
        
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        
        $ema = new EMA();
        $emaValues = $ema->calculate($data, ['period' => $period]);
        
        $bullPower = [];
        $bearPower = [];
        
        foreach ($emaValues as $i => $emaValue) {
            $bullPower[$i] = $highs[$i] - $emaValue;
            $bearPower[$i] = $lows[$i] - $emaValue;
        }
        
        return ['bull_power' => $bullPower, 'bear_power' => $bearPower, 'ema' => $emaValues];
    }
}

/** SMMA (Smoothed Moving Average) */
class SMMA extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 14;
        $prices = $this->extractClosePrices($data);
        
        $result = [];
        $smma = array_sum(array_slice($prices, 0, $period)) / $period;
        $result[$period - 1] = $smma;
        
        for ($i = $period; $i < count($prices); $i++) {
            $smma = ($smma * ($period - 1) + $prices[$i]) / $period;
            $result[$i] = $smma;
        }
        
        return $result;
    }
}

/** RMA (Wilder's Smoothed Moving Average) */
class RMA extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 14;
        $prices = $this->extractClosePrices($data);
        
        $alpha = 1 / $period;
        $result = [];
        
        // First RMA is SMA
        $result[$period - 1] = array_sum(array_slice($prices, 0, $period)) / $period;
        
        // Subsequent values use exponential smoothing
        for ($i = $period; $i < count($prices); $i++) {
            $result[$i] = $alpha * $prices[$i] + (1 - $alpha) * $result[$i - 1];
        }
        
        return $result;
    }
}

/** T3 (Tillson T3) */
class T3 extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 5;
        $vfactor = $params['vfactor'] ?? 0.7;
        
        $prices = $this->extractClosePrices($data);
        
        // Calculate 6 EMAs
        $ema1 = $this->calculateEMA($prices, $period);
        $ema2 = $this->calculateEMA($ema1, $period);
        $ema3 = $this->calculateEMA($ema2, $period);
        $ema4 = $this->calculateEMA($ema3, $period);
        $ema5 = $this->calculateEMA($ema4, $period);
        $ema6 = $this->calculateEMA($ema5, $period);
        
        // T3 formula with volume factor
        $c1 = -$vfactor * $vfactor * $vfactor;
        $c2 = 3 * $vfactor * $vfactor + 3 * $vfactor * $vfactor * $vfactor;
        $c3 = -6 * $vfactor * $vfactor - 3 * $vfactor - 3 * $vfactor * $vfactor * $vfactor;
        $c4 = 1 + 3 * $vfactor + $vfactor * $vfactor * $vfactor + 3 * $vfactor * $vfactor;
        
        $result = [];
        foreach ($ema6 as $i => $val) {
            if (isset($ema5[$i], $ema4[$i], $ema3[$i])) {
                $result[$i] = $c1 * $ema6[$i] + $c2 * $ema5[$i] + $c3 * $ema4[$i] + $c4 * $ema3[$i];
            }
        }
        
        return $result;
    }
    
    private function calculateEMA(array $values, int $period): array
    {
        $result = [];
        $multiplier = 2 / ($period + 1);
        
        $validValues = array_filter($values, fn($v) => $v !== null);
        if (empty($validValues)) return [];
        
        $keys = array_keys($validValues);
        $result[$keys[0]] = $validValues[$keys[0]];
        
        for ($i = 1; $i < count($keys); $i++) {
            $key = $keys[$i];
            $prevKey = $keys[$i - 1];
            $result[$key] = ($validValues[$key] - $result[$prevKey]) * $multiplier + $result[$prevKey];
        }
        
        return $result;
    }
}

/** GMMA (Guppy Multiple Moving Average) */
class GMMA extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        // Short-term group (6 EMAs)
        $shortPeriods = $params['short'] ?? [3, 5, 8, 10, 12, 15];
        // Long-term group (6 EMAs)
        $longPeriods = $params['long'] ?? [30, 35, 40, 45, 50, 60];
        
        $ema = new EMA();
        
        $shortEMAs = [];
        foreach ($shortPeriods as $period) {
            $shortEMAs[$period] = $ema->calculate($data, ['period' => $period]);
        }
        
        $longEMAs = [];
        foreach ($longPeriods as $period) {
            $longEMAs[$period] = $ema->calculate($data, ['period' => $period]);
        }
        
        return ['short' => $shortEMAs, 'long' => $longEMAs];
    }
}

/** LSMA (Least Squares Moving Average) */
class LSMA extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 25;
        $prices = $this->extractClosePrices($data);
        
        $result = [];
        
        for ($i = $period - 1; $i < count($prices); $i++) {
            $subset = array_slice($prices, $i - $period + 1, $period);
            
            // Linear regression calculation
            $n = count($subset);
            $sumX = 0;
            $sumY = 0;
            $sumXY = 0;
            $sumX2 = 0;
            
            for ($j = 0; $j < $n; $j++) {
                $sumX += $j;
                $sumY += $subset[$j];
                $sumXY += $j * $subset[$j];
                $sumX2 += $j * $j;
            }
            
            $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
            $intercept = ($sumY - $slope * $sumX) / $n;
            
            // LSMA is the regression value at the current point
            $result[$i] = $slope * ($n - 1) + $intercept;
        }
        
        return $result;
    }
}

/** MAMA (MESA Adaptive Moving Average) */
class MAMA extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $fastLimit = $params['fast_limit'] ?? 0.5;
        $slowLimit = $params['slow_limit'] ?? 0.05;
        
        $prices = $this->extractClosePrices($data);
        
        $mama = [];
        $fama = [];
        $phase = [];
        $period = [];
        
        // Initialize
        $mama[0] = $prices[0];
        $fama[0] = $prices[0];
        
        for ($i = 1; $i < count($prices); $i++) {
            // Simplified MAMA calculation (Hilbert Transform approximation)
            $smooth = ($prices[$i] + 2 * ($prices[$i-1] ?? $prices[$i])) / 3;
            
            // Calculate phase and period (simplified)
            $detrender = $smooth;
            $phase[$i] = atan($detrender);
            
            // Adaptive alpha based on phase
            $deltaPhase = abs($phase[$i] - ($phase[$i-1] ?? 0));
            $alpha = $fastLimit / max($deltaPhase + $slowLimit, 1);
            $alpha = max(min($alpha, $fastLimit), $slowLimit);
            
            $mama[$i] = $alpha * $prices[$i] + (1 - $alpha) * ($mama[$i-1] ?? $prices[$i]);
            $fama[$i] = 0.5 * $alpha * $mama[$i] + (1 - 0.5 * $alpha) * ($fama[$i-1] ?? $prices[$i]);
        }
        
        return ['mama' => $mama, 'fama' => $fama];
    }
}

/** Bollinger %B */
class BollingerPercentB extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $stdDev = $params['std_dev'] ?? 2;
        
        $bb = new BollingerBands();
        $bands = $bb->calculate($data, ['period' => $period, 'std_dev' => $stdDev]);
        
        $closes = $this->extractClosePrices($data);
        $percentB = [];
        
        foreach ($bands['upper'] as $i => $upper) {
            if (isset($bands['lower'][$i], $closes[$i])) {
                $range = $upper - $bands['lower'][$i];
                if ($range != 0) {
                    $percentB[$i] = ($closes[$i] - $bands['lower'][$i]) / $range;
                } else {
                    $percentB[$i] = 0.5;
                }
            }
        }
        
        return $percentB;
    }
}

/** Bollinger Band Width */
class BollingerBandWidth extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $stdDev = $params['std_dev'] ?? 2;
        
        $bb = new BollingerBands();
        $bands = $bb->calculate($data, ['period' => $period, 'std_dev' => $stdDev]);
        
        $width = [];
        
        foreach ($bands['upper'] as $i => $upper) {
            if (isset($bands['middle'][$i], $bands['lower'][$i])) {
                $width[$i] = (($upper - $bands['lower'][$i]) / $bands['middle'][$i]) * 100;
            }
        }
        
        return $width;
    }
}

/** Double Bollinger Bands */
class DoubleBollingerBands extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $innerStdDev = $params['inner_std'] ?? 1;
        $outerStdDev = $params['outer_std'] ?? 2;
        
        $prices = $this->extractClosePrices($data);
        $sma = new SMA();
        $middle = $sma->calculate($data, ['period' => $period]);
        
        $innerUpper = [];
        $innerLower = [];
        $outerUpper = [];
        $outerLower = [];
        
        for ($i = $period - 1; $i < count($prices); $i++) {
            $subset = array_slice($prices, $i - $period + 1, $period);
            $mean = array_sum($subset) / $period;
            
            $variance = 0;
            foreach ($subset as $price) {
                $variance += pow($price - $mean, 2);
            }
            $stdDevValue = sqrt($variance / $period);
            
            // Inner bands (1 std dev)
            $innerUpper[$i] = $middle[$i] + ($stdDevValue * $innerStdDev);
            $innerLower[$i] = $middle[$i] - ($stdDevValue * $innerStdDev);
            
            // Outer bands (2 std dev)
            $outerUpper[$i] = $middle[$i] + ($stdDevValue * $outerStdDev);
            $outerLower[$i] = $middle[$i] - ($stdDevValue * $outerStdDev);
        }
        
        return [
            'middle' => $middle,
            'inner_upper' => $innerUpper,
            'inner_lower' => $innerLower,
            'outer_upper' => $outerUpper,
            'outer_lower' => $outerLower,
        ];
    }
}
