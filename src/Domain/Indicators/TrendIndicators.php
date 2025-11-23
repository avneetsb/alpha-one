<?php

namespace TradingPlatform\Domain\Indicators;

/**
 * TREND INDICATORS
 * All moving averages, channels, and trend-following indicators
 */

/**
 * Simple Moving Average (SMA)
 *
 * The unweighted mean of the previous n data.
 *
 * **Formula:**
 * SMA = (P1 + P2 + ... + Pn) / n
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $sma = new SMA();
 * $result = $sma->calculate($candles, ['period' => 20]);
 * ```
 */
class SMA extends Indicator
{
    /**
     * Calculate SMA.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 20)
     * @return array Array of SMA values.
     */
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

/**
 * Exponential Moving Average (EMA)
 *
 * A type of moving average that places a greater weight and significance on the
 * most recent data points.
 *
 * **Formula:**
 * EMA = Price(t) * k + EMA(y) * (1 - k)
 * where k = 2 / (N + 1)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $ema = new EMA();
 * $result = $ema->calculate($candles, ['period' => 20]);
 * ```
 */
class EMA extends Indicator
{
    /**
     * Calculate EMA.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 20)
     * @return array Array of EMA values.
     */
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

/**
 * Double Exponential Moving Average (DEMA)
 *
 * Reduces the lag of a traditional EMA.
 *
 * **Formula:**
 * DEMA = 2 * EMA(n) - EMA(EMA(n))
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $dema = new DEMA();
 * $result = $dema->calculate($candles, ['period' => 30]);
 * ```
 */
class DEMA extends Indicator
{
    /**
     * Calculate DEMA.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 30)
     * @return array Array of DEMA values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 30;

        $ema = new EMA;
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

/**
 * Triple Exponential Moving Average (TEMA)
 *
 * Reduces the lag of a traditional EMA even further than DEMA.
 *
 * **Formula:**
 * TEMA = (3 * EMA1) - (3 * EMA2) + EMA3
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $tema = new TEMA();
 * $result = $tema->calculate($candles, ['period' => 30]);
 * ```
 */
class TEMA extends Indicator
{
    /**
     * Calculate TEMA.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 30)
     * @return array Array of TEMA values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 30;

        $ema = new EMA;
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

/**
 * Hull Moving Average (HMA)
 *
 * Developed by Alan Hull to reduce lag while maintaining smoothness.
 *
 * **Formula:**
 * HMA = WMA(2 * WMA(n/2) - WMA(n), sqrt(n))
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $hma = new HMA();
 * $result = $hma->calculate($candles, ['period' => 9]);
 * ```
 */
class HMA extends Indicator
{
    /**
     * Calculate HMA.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 9)
     * @return array Array of HMA values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 9;
        $prices = $this->extractClosePrices($data);

        $halfPeriod = (int) ($period / 2);
        $sqrtPeriod = (int) sqrt($period);

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

    /**
     * Calculate WMA helper.
     *
     * @param  array  $prices  Price data.
     * @param  int  $period  Period.
     * @return array WMA values.
     */
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

/**
 * Weighted Moving Average (WMA)
 *
 * A moving average that puts more weight on recent data and less on past data.
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $wma = new WMA();
 * $result = $wma->calculate($candles, ['period' => 20]);
 * ```
 */
class WMA extends Indicator
{
    /**
     * Calculate WMA.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 20)
     * @return array Array of WMA values.
     */
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

/**
 * Triangular Moving Average (TMA)
 *
 * A smoothed version of the SMA. It is essentially an SMA of an SMA.
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $tma = new TMA();
 * $result = $tma->calculate($candles, ['period' => 20]);
 * ```
 */
class TMA extends Indicator
{
    /**
     * Calculate TMA.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 20)
     * @return array Array of TMA values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;

        $sma = new SMA;
        $firstSMA = $sma->calculate($data, ['period' => ceil($period / 2)]);

        $firstSMAData = [];
        foreach ($firstSMA as $value) {
            $firstSMAData[] = ['close' => $value];
        }

        return $sma->calculate($firstSMAData, ['period' => ceil($period / 2)]);
    }
}

/**
 * Kaufman Adaptive Moving Average (KAMA)
 *
 * A moving average designed to account for market noise or volatility.
 *
 * **Formula:**
 * ER = Change / Volatility
 * SC = [ER * (fastSC - slowSC) + slowSC]^2
 * KAMA = KAMA(prev) + SC * (Price - KAMA(prev))
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $kama = new KAMA();
 * $result = $kama->calculate($candles, ['period' => 30, 'fast' => 2, 'slow' => 30]);
 * ```
 */
class KAMA extends Indicator
{
    /**
     * Calculate KAMA.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Efficiency Ratio period (default: 30)
     *                         - fast: Fast SC period (default: 2)
     *                         - slow: Slow SC period (default: 30)
     * @return array Array of KAMA values.
     */
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

/**
 * Zero-Lag Exponential Moving Average (ZLEMA)
 *
 * An EMA variation that attempts to eliminate lag by de-lagging the data
 * before smoothing.
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $zlema = new ZLEMA();
 * $result = $zlema->calculate($candles, ['period' => 20]);
 * ```
 */
class ZLEMA extends Indicator
{
    /**
     * Calculate ZLEMA.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 20)
     * @return array Array of ZLEMA values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $prices = $this->extractClosePrices($data);

        $lag = (int) (($period - 1) / 2);
        $emaData = [];

        for ($i = $lag; $i < count($prices); $i++) {
            $emaData[] = ['close' => 2 * $prices[$i] - $prices[$i - $lag]];
        }

        $ema = new EMA;

        return $ema->calculate($emaData, ['period' => $period]);
    }
}

/**
 * Bollinger Bands
 *
 * A technical analysis tool defined by a set of trendlines plotted two standard
 * deviations (positively and negatively) away from a simple moving average (SMA)
 * of a security's price.
 *
 * **Components:**
 * - Middle Band: SMA
 * - Upper Band: Middle Band + (StdDev * Multiplier)
 * - Lower Band: Middle Band - (StdDev * Multiplier)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $bb = new BollingerBands();
 * $result = $bb->calculate($candles, ['period' => 20, 'std_dev' => 2]);
 * ```
 */
class BollingerBands extends Indicator
{
    /**
     * Calculate Bollinger Bands.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: SMA period (default: 20)
     *                         - std_dev: Standard Deviation multiplier (default: 2)
     * @return array Associative array containing 'upper', 'middle', and 'lower' arrays.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $stdDevMultiplier = $params['std_dev'] ?? 2;

        $prices = $this->extractClosePrices($data);
        $sma = new SMA;
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

/**
 * Donchian Channels
 *
 * A channel indicator formed by taking the highest high and the lowest low
 * of the last n periods.
 *
 * **Components:**
 * - Upper Band: Highest High
 * - Lower Band: Lowest Low
 * - Middle Band: (Upper + Lower) / 2
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $dc = new DonchianChannels();
 * $result = $dc->calculate($candles, ['period' => 20]);
 * ```
 */
class DonchianChannels extends Indicator
{
    /**
     * Calculate Donchian Channels.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 20)
     * @return array Associative array containing 'upper', 'middle', and 'lower' arrays.
     */
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

/**
 * Keltner Channel
 *
 * A volatility-based envelope set above and below an exponential moving average.
 *
 * **Components:**
 * - Middle Line: EMA
 * - Upper Band: Middle Line + (Multiplier * ATR)
 * - Lower Band: Middle Line - (Multiplier * ATR)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $kc = new KeltnerChannel();
 * $result = $kc->calculate($candles, ['period' => 20, 'multiplier' => 2.0]);
 * ```
 */
class KeltnerChannel extends Indicator
{
    /**
     * Calculate Keltner Channel.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: EMA and ATR period (default: 20)
     *                         - multiplier: ATR multiplier (default: 2.0)
     * @return array Associative array containing 'upper', 'middle', and 'lower' arrays.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $multiplier = $params['multiplier'] ?? 2.0;

        $emaCalc = new EMA;
        $middle = $emaCalc->calculate($data, ['period' => $period]);

        $atrCalc = new ATR;
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

/**
 * SuperTrend
 *
 * A trend-following indicator similar to a moving average. It is calculated
 * using the ATR.
 *
 * **Interpretation:**
 * - Price > SuperTrend: Bullish
 * - Price < SuperTrend: Bearish
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $st = new SuperTrend();
 * $result = $st->calculate($candles, ['period' => 10, 'multiplier' => 3.0]);
 * ```
 */
class SuperTrend extends Indicator
{
    /**
     * Calculate SuperTrend.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: ATR period (default: 10)
     *                         - multiplier: ATR multiplier (default: 3.0)
     * @return array Associative array containing 'supertrend' and 'direction' arrays.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 10;
        $multiplier = $params['multiplier'] ?? 3.0;

        $atrCalc = new ATR;
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

/**
 * Williams Alligator
 *
 * A trend-following indicator developed by Bill Williams.
 *
 * **Components:**
 * - Jaw: 13-period SMMA, shifted 8 bars forward
 * - Teeth: 8-period SMMA, shifted 5 bars forward
 * - Lips: 5-period SMMA, shifted 3 bars forward
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $alligator = new Alligator();
 * $result = $alligator->calculate($candles);
 * ```
 */
class Alligator extends Indicator
{
    /**
     * Calculate Alligator.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters (unused).
     * @return array Associative array containing 'jaw', 'teeth', and 'lips' arrays.
     */
    public function calculate(array $data, array $params = []): array
    {
        $prices = $this->extractClosePrices($data);

        $jaw = $this->smma($prices, 13);
        $teeth = $this->smma($prices, 8);
        $lips = $this->smma($prices, 5);

        return ['jaw' => $jaw, 'teeth' => $teeth, 'lips' => $lips];
    }

    /**
     * Calculate SMMA helper.
     *
     * @param  array  $prices  Price data.
     * @param  int  $period  Period.
     * @return array SMMA values.
     */
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

/**
 * Ichimoku Cloud (Ichimoku Kinko Hyo)
 *
 * A versatile indicator that defines support and resistance, identifies trend direction,
 * gauges momentum, and provides trading signals.
 *
 * **Components:**
 * - Tenkan-sen (Conversion Line)
 * - Kijun-sen (Base Line)
 * - Senkou Span A (Leading Span A)
 * - Senkou Span B (Leading Span B)
 * - Chikou Span (Lagging Span)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $ichimoku = new IchimokuCloud();
 * $result = $ichimoku->calculate($candles, ['conversion' => 9, 'base' => 26, 'lagging' => 52, 'displacement' => 26]);
 * ```
 */
class IchimokuCloud extends Indicator
{
    /**
     * Calculate Ichimoku Cloud.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - conversion: Tenkan-sen period (default: 9)
     *                         - base: Kijun-sen period (default: 26)
     *                         - lagging: Senkou Span B period (default: 52)
     *                         - displacement: Displacement period (default: 26)
     * @return array Associative array containing all Ichimoku components.
     */
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
            'chikou' => $chikou,
        ];
    }
}

/**
 * Parabolic SAR (Stop and Reverse)
 *
 * A trend-following indicator that highlights potential reversals.
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $sar = new ParabolicSAR();
 * $result = $sar->calculate($candles, ['af' => 0.02, 'max_af' => 0.2]);
 * ```
 */
class ParabolicSAR extends Indicator
{
    /**
     * Calculate Parabolic SAR.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - af: Acceleration Factor step (default: 0.02)
     *                         - max_af: Maximum Acceleration Factor (default: 0.2)
     * @return array Array of SAR values.
     */
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

/**
 * Heikin Ashi
 *
 * A type of candlestick chart that shares many characteristics with standard
 * candlestick charts, but differs because of the values used to create each bar.
 *
 * **Formula:**
 * - Close = (Open + High + Low + Close) / 4
 * - Open = (Open(prev) + Close(prev)) / 2
 * - High = Max(High, Open, Close)
 * - Low = Min(Low, Open, Close)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $ha = new HeikinAshi();
 * $result = $ha->calculate($candles);
 * ```
 */
class HeikinAshi extends Indicator
{
    /**
     * Calculate Heikin Ashi candles.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters (unused).
     * @return array Associative array containing 'open', 'high', 'low', and 'close' arrays.
     */
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
            'close' => $haClose,
        ];
    }
}

/**
 * Linear Regression Channel
 *
 * A three-line technical indicator used to analyze the upper and lower limits
 * of an existing trend.
 *
 * **Components:**
 * - Middle Line: Linear Regression Line
 * - Upper Channel Line: Middle Line + (StdDevs * StdDev)
 * - Lower Channel Line: Middle Line - (StdDevs * StdDev)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $lr = new LinearRegression();
 * $result = $lr->calculate($candles, ['period' => 100, 'std_devs' => 2.0]);
 * ```
 */
class LinearRegression extends Indicator
{
    /**
     * Calculate Linear Regression Channel.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 100)
     *                         - std_devs: Standard Deviation multiplier (default: 2.0)
     * @return array Associative array containing 'upper', 'middle', 'lower', and 'slope' arrays.
     */
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

            $variance = array_sum(array_map(fn ($r) => $r ** 2, $residuals)) / $n;
            $stdDev = sqrt($variance);

            $upper[$i] = $middle[$i] + ($stdDev * $stdDevs);
            $lower[$i] = $middle[$i] - ($stdDev * $stdDevs);
        }

        return ['upper' => $upper, 'middle' => $middle, 'lower' => $lower, 'slope' => $slope];
    }
}

/**
 * Vortex Indicator
 *
 * An oscillator used to identify the start of a new trend and confirm an existing trend,
 * its direction, and strength.
 *
 * **Components:**
 * - VI+ (Positive Vortex Movement)
 * - VI- (Negative Vortex Movement)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $vortex = new VortexIndicator();
 * $result = $vortex->calculate($candles, ['period' => 14]);
 * ```
 */
class VortexIndicator extends Indicator
{
    /**
     * Calculate Vortex Indicator.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 14)
     * @return array Associative array containing 'vi_plus' and 'vi_minus' arrays.
     */
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
                if ($idx < 1) {
                    continue;
                }

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

/**
 * Elder Ray Index (Bull Power & Bear Power)
 *
 * Developed by Dr. Alexander Elder, this indicator measures the amount of buying
 * and selling pressure in the market.
 *
 * **Components:**
 * - Bull Power = High - EMA
 * - Bear Power = Low - EMA
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $elderRay = new ElderRay();
 * $result = $elderRay->calculate($candles, ['period' => 13]);
 * ```
 */
class ElderRay extends Indicator
{
    /**
     * Calculate Elder Ray Index.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: EMA period (default: 13)
     * @return array Associative array containing 'bull_power', 'bear_power', and 'ema' arrays.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 13;

        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);

        $ema = new EMA;
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

/**
 * Smoothed Moving Average (SMMA)
 *
 * A moving average that gives equal weight to all data points but removes short-term
 * fluctuations more effectively than an SMA.
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $smma = new SMMA();
 * $result = $smma->calculate($candles, ['period' => 14]);
 * ```
 */
class SMMA extends Indicator
{
    /**
     * Calculate SMMA.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 14)
     * @return array Array of SMMA values.
     */
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

/**
 * Wilder's Smoothed Moving Average (RMA)
 *
 * A variation of the EMA used by Welles Wilder in indicators like RSI.
 *
 * **Formula:**
 * RMA = (RMA(prev) * (n - 1) + Price) / n
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $rma = new RMA();
 * $result = $rma->calculate($candles, ['period' => 14]);
 * ```
 */
class RMA extends Indicator
{
    /**
     * Calculate RMA.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 14)
     * @return array Array of RMA values.
     */
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

/**
 * Tillson T3 Moving Average
 *
 * A smooth moving average that reduces lag and noise.
 *
 * **Formula:**
 * GD = EMA(EMA(1 + v) - EMA(v))
 * T3 = GD(GD(GD(Price)))
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $t3 = new T3();
 * $result = $t3->calculate($candles, ['period' => 5, 'vfactor' => 0.7]);
 * ```
 */
class T3 extends Indicator
{
    /**
     * Calculate T3.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 5)
     *                         - vfactor: Volume Factor (default: 0.7)
     * @return array Array of T3 values.
     */
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

    /**
     * Calculate EMA helper.
     *
     * @param  array  $values  Input values.
     * @param  int  $period  Period.
     * @return array EMA values.
     */
    private function calculateEMA(array $values, int $period): array
    {
        $result = [];
        $multiplier = 2 / ($period + 1);

        $validValues = array_filter($values, fn ($v) => $v !== null);
        if (empty($validValues)) {
            return [];
        }

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

/**
 * Guppy Multiple Moving Average (GMMA)
 *
 * A technical indicator that identifies changing trends by combining two groups
 * of moving averages with different time periods.
 *
 * **Components:**
 * - Short-term Group: 3, 5, 8, 10, 12, 15 EMA
 * - Long-term Group: 30, 35, 40, 45, 50, 60 EMA
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $gmma = new GMMA();
 * $result = $gmma->calculate($candles);
 * ```
 */
class GMMA extends Indicator
{
    /**
     * Calculate GMMA.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - short: Array of short periods (default: [3, 5, 8, 10, 12, 15])
     *                         - long: Array of long periods (default: [30, 35, 40, 45, 50, 60])
     * @return array Associative array containing 'short' and 'long' arrays of EMAs.
     */
    public function calculate(array $data, array $params = []): array
    {
        // Short-term group (6 EMAs)
        $shortPeriods = $params['short'] ?? [3, 5, 8, 10, 12, 15];
        // Long-term group (6 EMAs)
        $longPeriods = $params['long'] ?? [30, 35, 40, 45, 50, 60];

        $ema = new EMA;

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

/**
 * Least Squares Moving Average (LSMA)
 *
 * Also known as the End Point Moving Average, this indicator calculates the
 * regression line for the preceding time periods and projects it forward.
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $lsma = new LSMA();
 * $result = $lsma->calculate($candles, ['period' => 25]);
 * ```
 */
class LSMA extends Indicator
{
    /**
     * Calculate LSMA.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 25)
     * @return array Array of LSMA values.
     */
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

/**
 * MESA Adaptive Moving Average (MAMA)
 *
 * An adaptive moving average that adjusts to market volatility.
 *
 * **Components:**
 * - MAMA: MESA Adaptive Moving Average
 * - FAMA: Following Adaptive Moving Average
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $mama = new MAMA();
 * $result = $mama->calculate($candles, ['fast_limit' => 0.5, 'slow_limit' => 0.05]);
 * ```
 */
class MAMA extends Indicator
{
    /**
     * Calculate MAMA.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - fast_limit: Fast Limit (default: 0.5)
     *                         - slow_limit: Slow Limit (default: 0.05)
     * @return array Associative array containing 'mama' and 'fama' arrays.
     */
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
            $smooth = ($prices[$i] + 2 * ($prices[$i - 1] ?? $prices[$i])) / 3;

            // Calculate phase and period (simplified)
            $detrender = $smooth;
            $phase[$i] = atan($detrender);

            // Adaptive alpha based on phase
            $deltaPhase = abs($phase[$i] - ($phase[$i - 1] ?? 0));
            $alpha = $fastLimit / max($deltaPhase + $slowLimit, 1);
            $alpha = max(min($alpha, $fastLimit), $slowLimit);

            $mama[$i] = $alpha * $prices[$i] + (1 - $alpha) * ($mama[$i - 1] ?? $prices[$i]);
            $fama[$i] = 0.5 * $alpha * $mama[$i] + (1 - 0.5 * $alpha) * ($fama[$i - 1] ?? $prices[$i]);
        }

        return ['mama' => $mama, 'fama' => $fama];
    }
}

/**
 * Bollinger %B
 *
 * Measures where the price is in relation to the Bollinger Bands.
 *
 * **Formula:**
 * %B = (Price - Lower Band) / (Upper Band - Lower Band)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $percentB = new BollingerPercentB();
 * $result = $percentB->calculate($candles, ['period' => 20, 'std_dev' => 2]);
 * ```
 */
class BollingerPercentB extends Indicator
{
    /**
     * Calculate Bollinger %B.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: SMA period (default: 20)
     *                         - std_dev: Standard Deviation multiplier (default: 2)
     * @return array Array of %B values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $stdDev = $params['std_dev'] ?? 2;

        $bb = new BollingerBands;
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

/**
 * Bollinger Band Width
 *
 * Measures the width of the Bollinger Bands.
 *
 * **Formula:**
 * BandWidth = (Upper Band - Lower Band) / Middle Band
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $bbw = new BollingerBandWidth();
 * $result = $bbw->calculate($candles, ['period' => 20, 'std_dev' => 2]);
 * ```
 */
class BollingerBandWidth extends Indicator
{
    /**
     * Calculate Bollinger Band Width.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: SMA period (default: 20)
     *                         - std_dev: Standard Deviation multiplier (default: 2)
     * @return array Array of Band Width values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $stdDev = $params['std_dev'] ?? 2;

        $bb = new BollingerBands;
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

/**
 * Double Bollinger Bands
 *
 * Uses two sets of Bollinger Bands to identify trend strength.
 *
 * **Components:**
 * - Inner Bands: 1 Standard Deviation
 * - Outer Bands: 2 Standard Deviations
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $dbb = new DoubleBollingerBands();
 * $result = $dbb->calculate($candles, ['period' => 20, 'inner_std' => 1, 'outer_std' => 2]);
 * ```
 */
class DoubleBollingerBands extends Indicator
{
    /**
     * Calculate Double Bollinger Bands.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: SMA period (default: 20)
     *                         - inner_std: Inner StdDev (default: 1)
     *                         - outer_std: Outer StdDev (default: 2)
     * @return array Associative array containing 'middle', 'inner_upper', 'inner_lower', 'outer_upper', and 'outer_lower' arrays.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $innerStdDev = $params['inner_std'] ?? 1;
        $outerStdDev = $params['outer_std'] ?? 2;

        $prices = $this->extractClosePrices($data);
        $sma = new SMA;
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
