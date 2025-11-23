<?php

namespace TradingPlatform\Domain\Indicators;

/**
 * MOMENTUM INDICATORS
 * All oscillators and momentum-based indicators
 */

/**
 * Relative Strength Index (RSI)
 *
 * Measures the speed and change of price movements. RSI oscillates between zero and 100.
 * Traditionally, and according to Wilder, RSI is considered overbought when above 70
 * and oversold when below 30.
 *
 * **Formula:**
 * RSI = 100 - (100 / (1 + RS))
 * RS = Average Gain / Average Loss
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $rsi = new RSI();
 * $result = $rsi->calculate($candles, ['period' => 14]);
 * ```
 */
class RSI extends Indicator
{
    /**
     * Calculate RSI.
     *
     * @param  array  $data  Array of candles or price data.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 14)
     * @return array Array of RSI values indexed by candle index.
     */
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

/**
 * Moving Average Convergence Divergence (MACD)
 *
 * A trend-following momentum indicator that shows the relationship between
 * two moving averages of a security's price.
 *
 * **Components:**
 * - **MACD Line**: 12-period EMA - 26-period EMA
 * - **Signal Line**: 9-period EMA of the MACD Line
 * - **Histogram**: MACD Line - Signal Line
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $macd = new MACD();
 * $result = $macd->calculate($candles, ['fast' => 12, 'slow' => 26, 'signal' => 9]);
 * // Returns ['macd' => [...], 'signal' => [...], 'histogram' => [...]]
 * ```
 */
class MACD extends Indicator
{
    /**
     * Calculate MACD.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - fast: Fast EMA period (default: 12)
     *                         - slow: Slow EMA period (default: 26)
     *                         - signal: Signal EMA period (default: 9)
     * @return array Associative array containing 'macd', 'signal', and 'histogram' arrays.
     */
    public function calculate(array $data, array $params = []): array
    {
        $fastPeriod = $params['fast'] ?? 12;
        $slowPeriod = $params['slow'] ?? 26;
        $signalPeriod = $params['signal'] ?? 9;

        $ema = new EMA;
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

/**
 * Stochastic Oscillator
 *
 * A momentum indicator comparing a particular closing price of a security to a
 * range of its prices over a certain period of time.
 *
 * **Components:**
 * - **%K**: The raw stochastic value (fast)
 * - **%D**: The moving average of %K (slow)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $stoch = new Stochastic();
 * $result = $stoch->calculate($candles, ['k_period' => 14, 'd_period' => 3]);
 * ```
 */
class Stochastic extends Indicator
{
    /**
     * Calculate Stochastic Oscillator.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - k_period: Lookback period for %K (default: 14)
     *                         - d_period: Smoothing period for %D (default: 3)
     * @return array Associative array containing 'k' and 'd' arrays.
     */
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

/**
 * Average Directional Index (ADX)
 *
 * Measures the strength of a trend, regardless of direction.
 *
 * **Interpretation:**
 * - ADX > 25: Strong trend
 * - ADX < 20: Weak trend / Ranging market
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $adx = new ADX();
 * $result = $adx->calculate($candles, ['period' => 14]);
 * ```
 */
class ADX extends Indicator
{
    /**
     * Calculate ADX.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 14)
     * @return array Array of ADX values.
     */
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

/**
 * Commodity Channel Index (CCI)
 *
 * Measures the difference between the current price and the historical average price.
 * Used to identify cyclical trends and overbought/oversold conditions.
 *
 * **Interpretation:**
 * - CCI > 100: Overbought / Strong Uptrend
 * - CCI < -100: Oversold / Strong Downtrend
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $cci = new CCI();
 * $result = $cci->calculate($candles, ['period' => 20]);
 * ```
 */
class CCI extends Indicator
{
    /**
     * Calculate CCI.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 20)
     * @return array Array of CCI values.
     */
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

/**
 * Williams %R
 *
 * A momentum indicator that measures overbought and oversold levels.
 * Similar to Stochastic Oscillator but plotted on an inverted scale (0 to -100).
 *
 * **Interpretation:**
 * - %R > -20: Overbought
 * - %R < -80: Oversold
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $wr = new WilliamsR();
 * $result = $wr->calculate($candles, ['period' => 14]);
 * ```
 */
class WilliamsR extends Indicator
{
    /**
     * Calculate Williams %R.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 14)
     * @return array Array of Williams %R values.
     */
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

/**
 * True Strength Index (TSI)
 *
 * A momentum oscillator that shows both trend direction and overbought/oversold conditions.
 * Uses double smoothing of price changes to reduce noise.
 *
 * **Interpretation:**
 * - Signal Line Crossovers
 * - Centerline Crossovers (Zero Line)
 * - Divergence
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $tsi = new TSI();
 * $result = $tsi->calculate($candles, ['long' => 25, 'short' => 13]);
 * ```
 */
class TSI extends Indicator
{
    /**
     * Calculate TSI.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - long: Long smoothing period (default: 25)
     *                         - short: Short smoothing period (default: 13)
     * @return array Array of TSI values.
     */
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

    /**
     * Calculate EMA of an array of values.
     *
     * @param  array  $values  Input values.
     * @param  int  $period  EMA period.
     * @return array EMA values.
     */
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

/**
 * Aroon Oscillator
 *
 * Measures the strength of a trend and the likelihood that it will continue.
 * Calculated by subtracting Aroon Down from Aroon Up.
 *
 * **Interpretation:**
 * - Above 0: Uptrend
 * - Below 0: Downtrend
 * - Near 0: Consolidation
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $aroon = new AroonOscillator();
 * $result = $aroon->calculate($candles, ['period' => 25]);
 * // Returns ['up' => [...], 'down' => [...], 'oscillator' => [...]]
 * ```
 */
class AroonOscillator extends Indicator
{
    /**
     * Calculate Aroon Oscillator.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 25)
     * @return array Associative array containing 'up', 'down', and 'oscillator' arrays.
     */
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

/**
 * Ultimate Oscillator
 *
 * A momentum oscillator that uses three different timeframes to avoid the
 * pitfalls of other oscillators.
 *
 * **Formula:**
 * Weighted average of three timeframes (usually 7, 14, 28).
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $uo = new UltimateOscillator();
 * $result = $uo->calculate($candles, ['period1' => 7, 'period2' => 14, 'period3' => 28]);
 * ```
 */
class UltimateOscillator extends Indicator
{
    /**
     * Calculate Ultimate Oscillator.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period1: Short timeframe (default: 7)
     *                         - period2: Medium timeframe (default: 14)
     *                         - period3: Long timeframe (default: 28)
     * @return array Array of Ultimate Oscillator values.
     */
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
                if ($idx < 1) {
                    continue;
                }

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

/**
 * Awesome Oscillator (AO)
 *
 * A momentum indicator used to measure market momentum.
 * Calculated as the difference between a 34-period and 5-period Simple Moving Average
 * of the median points (High+Low)/2.
 *
 * **Interpretation:**
 * - Zero Line Crossover
 * - Saucer
 * - Twin Peaks
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $ao = new AwesomeOscillator();
 * $result = $ao->calculate($candles);
 * ```
 */
class AwesomeOscillator extends Indicator
{
    /**
     * Calculate Awesome Oscillator.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters (none required).
     * @return array Array of AO values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);

        $medianPrices = [];
        foreach ($data as $i => $candle) {
            $medianPrices[] = ['close' => ($highs[$i] + $lows[$i]) / 2];
        }

        $sma = new SMA;
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

/**
 * Chande Momentum Oscillator (CMO)
 *
 * A technical momentum indicator invented by Tushar Chande.
 * Similar to RSI but uses data from both up days and down days in the numerator.
 *
 * **Range:** -100 to +100
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $cmo = new CMO();
 * $result = $cmo->calculate($candles, ['period' => 14]);
 * ```
 */
class CMO extends Indicator
{
    /**
     * Calculate CMO.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 14)
     * @return array Array of CMO values.
     */
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

/**
 * Detrended Price Oscillator (DPO)
 *
 * An indicator designed to remove trend from price and make it easier to
 * identify cycles.
 *
 * **Formula:**
 * DPO = Close - SMA(Period / 2 + 1)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $dpo = new DPO();
 * $result = $dpo->calculate($candles, ['period' => 20]);
 * ```
 */
class DPO extends Indicator
{
    /**
     * Calculate DPO.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 20)
     * @return array Array of DPO values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $prices = $this->extractClosePrices($data);

        $sma = new SMA;
        $smaValues = $sma->calculate($data, ['period' => $period]);

        $displacement = (int) ($period / 2) + 1;
        $result = [];

        for ($i = $period - 1 + $displacement; $i < count($prices); $i++) {
            if (isset($smaValues[$i - $displacement])) {
                $result[$i] = $prices[$i] - $smaValues[$i - $displacement];
            }
        }

        return $result;
    }
}

/**
 * Know Sure Thing (KST)
 *
 * A momentum oscillator developed by Martin Pring to make rate-of-change readings
 * easier for traders to interpret.
 *
 * **Components:**
 * - Four different Rate of Change (ROC) periods
 * - Smoothed by four different SMA periods
 * - Combined into a single KST line
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $kst = new KST();
 * $result = $kst->calculate($candles);
 * ```
 */
class KST extends Indicator
{
    /**
     * Calculate KST.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - roc1, roc2, roc3, roc4: ROC periods
     *                         - sma1, sma2, sma3, sma4: SMA smoothing periods
     *                         - signal: Signal line period
     * @return array Associative array containing 'kst' and 'signal' arrays.
     */
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
            $period = ${'roc'.$n};
            $rocValues[$n] = [];
            for ($i = $period; $i < count($prices); $i++) {
                $rocValues[$n][$i] = (($prices[$i] - $prices[$i - $period]) / $prices[$i - $period]) * 100;
            }
        }

        $sma = new SMA;
        $smoothedROC = [];
        for ($n = 1; $n <= 4; $n++) {
            $rocData = [];
            foreach ($rocValues[$n] as $value) {
                $rocData[] = ['close' => $value];
            }
            $smoothedROC[$n] = $sma->calculate($rocData, ['period' => ${'sma'.$n}]);
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

/**
 * Fisher Transform
 *
 * A technical indicator created by John F. Ehlers that converts prices into a
 * Gaussian normal distribution.
 *
 * **Interpretation:**
 * - Identifies price reversals
 * - Extreme readings signal potential turning points
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $ft = new FisherTransform();
 * $result = $ft->calculate($candles, ['period' => 10]);
 * ```
 */
class FisherTransform extends Indicator
{
    /**
     * Calculate Fisher Transform.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 10)
     * @return array Associative array containing 'fisher' and 'trigger' arrays.
     */
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

/**
 * Rate of Change (ROC)
 *
 * A momentum oscillator that measures the percentage change in price between
 * the current price and the price n-periods ago.
 *
 * **Formula:**
 * ROC = ((Close - Close[n]) / Close[n]) * 100
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $roc = new ROC();
 * $result = $roc->calculate($candles, ['period' => 12]);
 * ```
 */
class ROC extends Indicator
{
    /**
     * Calculate ROC.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 12)
     * @return array Array of ROC values.
     */
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

/**
 * Momentum Indicator
 *
 * Measures the amount that a security's price has changed over a given time span.
 * Similar to ROC but expresses change as an absolute value difference rather than
 * a percentage.
 *
 * **Formula:**
 * Momentum = Close - Close[n]
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $mom = new Momentum();
 * $result = $mom->calculate($candles, ['period' => 10]);
 * ```
 */
class Momentum extends Indicator
{
    /**
     * Calculate Momentum.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 10)
     * @return array Array of Momentum values.
     */
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

/**
 * Stochastic Momentum Index (SMI)
 *
 * A refined version of the Stochastic Oscillator that uses a wider range of values
 * and is more sensitive to closing prices.
 *
 * **Range:** -100 to +100
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $smi = new StochasticMomentumIndex();
 * $result = $smi->calculate($candles, ['k_period' => 5, 'd_period' => 3, 'smooth' => 5]);
 * ```
 */
class StochasticMomentumIndex extends Indicator
{
    /**
     * Calculate SMI.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - k_period: %K period (default: 5)
     *                         - d_period: %D period (default: 3)
     *                         - smooth: Smoothing period (default: 5)
     * @return array Associative array containing 'smi' and 'signal' arrays.
     */
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

        $ema = new EMA;
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

/**
 * Percentage Price Oscillator (PPO)
 *
 * A momentum oscillator similar to MACD, but measures the percentage difference
 * between two EMAs rather than the absolute difference.
 *
 * **Components:**
 * - PPO Line
 * - Signal Line
 * - Histogram
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $ppo = new PPO();
 * $result = $ppo->calculate($candles, ['fast' => 12, 'slow' => 26, 'signal' => 9]);
 * ```
 */
class PPO extends Indicator
{
    /**
     * Calculate PPO.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - fast: Fast EMA period (default: 12)
     *                         - slow: Slow EMA period (default: 26)
     *                         - signal: Signal EMA period (default: 9)
     * @return array Associative array containing 'ppo', 'signal', and 'histogram' arrays.
     */
    public function calculate(array $data, array $params = []): array
    {
        $fastPeriod = $params['fast'] ?? 12;
        $slowPeriod = $params['slow'] ?? 26;
        $signalPeriod = $params['signal'] ?? 9;

        $ema = new EMA;
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

/**
 * Balance of Power (BOP)
 *
 * Measures the strength of buying and selling pressure.
 *
 * **Formula:**
 * BOP = (Close - Open) / (High - Low)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $bop = new BalanceOfPower();
 * $result = $bop->calculate($candles, ['smooth' => 14]);
 * ```
 */
class BalanceOfPower extends Indicator
{
    /**
     * Calculate BOP.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - smooth: Smoothing period (default: 14)
     * @return array Array of BOP values.
     */
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

            $sma = new SMA;

            return $sma->calculate($bopData, ['period' => $smoothPeriod]);
        }

        return $bop;
    }
}

/**
 * Stochastic RSI (StochRSI)
 *
 * An indicator that applies the Stochastic Oscillator formula to RSI values
 * instead of price data.
 *
 * **Interpretation:**
 * - Overbought: > 80
 * - Oversold: < 20
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $stochRsi = new StochRSI();
 * $result = $stochRsi->calculate($candles, ['rsi_period' => 14, 'stoch_period' => 14]);
 * ```
 */
class StochRSI extends Indicator
{
    /**
     * Calculate StochRSI.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - rsi_period: RSI lookback period (default: 14)
     *                         - stoch_period: Stochastic lookback period (default: 14)
     *                         - k_smooth: %K smoothing period (default: 3)
     *                         - d_smooth: %D smoothing period (default: 3)
     * @return array Associative array containing 'k' and 'd' arrays.
     */
    public function calculate(array $data, array $params = []): array
    {
        $rsiPeriod = $params['rsi_period'] ?? 14;
        $stochPeriod = $params['stoch_period'] ?? 14;
        $kSmooth = $params['k_smooth'] ?? 3;
        $dSmooth = $params['d_smooth'] ?? 3;

        // First calculate RSI
        $rsiCalc = new RSI;
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

/**
 * Connors RSI (CRSI)
 *
 * A composite indicator consisting of three components:
 * 1. RSI (usually 3-period)
 * 2. RSI of Streak (usually 2-period)
 * 3. Percent Rank of ROC (usually 100-period)
 *
 * **Range:** 0 to 100
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $crsi = new ConnorsRSI();
 * $result = $crsi->calculate($candles);
 * ```
 */
class ConnorsRSI extends Indicator
{
    /**
     * Calculate Connors RSI.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - rsi_period: RSI Period (default: 3)
     *                         - streak_period: Streak RSI Period (default: 2)
     *                         - rank_period: Percent Rank Period (default: 100)
     * @return array Array of CRSI values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $rsiPeriod = $params['rsi_period'] ?? 3;
        $streakPeriod = $params['streak_period'] ?? 2;
        $pctRankPeriod = $params['rank_period'] ?? 100;

        $closes = $this->extractClosePrices($data);

        // Component 1: Short-term RSI
        $rsiCalc = new RSI;
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
                if ($val < $currentROC) {
                    $count++;
                }
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

/**
 * Smoothed RSI
 *
 * RSI calculated with a smoothing filter (SMA or EMA) applied to the result.
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $smoothedRsi = new SmoothedRSI();
 * $result = $smoothedRsi->calculate($candles, ['rsi_period' => 14, 'smooth_period' => 5]);
 * ```
 */
class SmoothedRSI extends Indicator
{
    /**
     * Calculate Smoothed RSI.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - rsi_period: RSI Period (default: 14)
     *                         - smooth_period: Smoothing Period (default: 5)
     *                         - smooth_type: 'sma' or 'ema' (default: 'sma')
     * @return array Array of Smoothed RSI values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $rsiPeriod = $params['rsi_period'] ?? 14;
        $smoothPeriod = $params['smooth_period'] ?? 5;
        $smoothType = $params['smooth_type'] ?? 'sma'; // 'sma' or 'ema'

        // Calculate RSI
        $rsiCalc = new RSI;
        $rsiValues = $rsiCalc->calculate($data, ['period' => $rsiPeriod]);

        // Convert to data format for smoothing
        $rsiData = [];
        foreach ($rsiValues as $value) {
            $rsiData[] = ['close' => $value];
        }

        // Apply smoothing
        if ($smoothType === 'ema') {
            $ema = new EMA;

            return $ema->calculate($rsiData, ['period' => $smoothPeriod]);
        } else {
            $sma = new SMA;

            return $sma->calculate($rsiData, ['period' => $smoothPeriod]);
        }
    }
}

/**
 * RSI EMA
 *
 * RSI calculated using Exponential Moving Average (EMA) for gains and losses
 * instead of the standard Smoothed Moving Average (SMMA).
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $rsiEma = new RSIEMA();
 * $result = $rsiEma->calculate($candles, ['period' => 14]);
 * ```
 */
class RSIEMA extends Indicator
{
    /**
     * Calculate RSI EMA.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 14)
     * @return array Array of RSI EMA values.
     */
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
        $ema = new EMA;
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

/**
 * Zero Lag MACD
 *
 * A MACD variation that uses Zero Lag EMAs to reduce latency.
 *
 * **Components:**
 * - Zero Lag MACD Line
 * - Zero Lag Signal Line
 * - Histogram
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $zlMacd = new ZeroLagMACD();
 * $result = $zlMacd->calculate($candles);
 * ```
 */
class ZeroLagMACD extends Indicator
{
    /**
     * Calculate Zero Lag MACD.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - fast: Fast Period (default: 12)
     *                         - slow: Slow Period (default: 26)
     *                         - signal: Signal Period (default: 9)
     * @return array Associative array containing 'macd', 'signal', and 'histogram' arrays.
     */
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

    /**
     * Calculate Zero Lag EMA.
     *
     * @param  array  $values  Input values.
     * @param  int  $period  Period.
     * @return array Zero Lag EMA values.
     */
    private function zeroLagEMA(array $values, int $period): array
    {
        $lag = (int) (($period - 1) / 2);
        $ema1 = [];
        $ema2 = [];
        $zlema = [];

        $multiplier = 2 / ($period + 1);

        // Calculate regular EMA
        if (count($values) < 1) {
            return [];
        }

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
        if (empty($ema2Keys)) {
            return $ema1;
        }

        $zlema[$ema2Keys[0]] = $ema2[$ema2Keys[0]];

        for ($i = 1; $i < count($ema2Keys); $i++) {
            $key = $ema2Keys[$i];
            $prevKey = $ema2Keys[$i - 1];
            $zlema[$key] = ($ema2[$key] - $zlema[$prevKey]) * $multiplier + $zlema[$prevKey];
        }

        return $zlema;
    }
}

/**
 * Slow Stochastic
 *
 * A version of the Stochastic Oscillator where %K is smoothed (equivalent to %D in Fast Stochastic).
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $slowStoch = new SlowStochastic();
 * $result = $slowStoch->calculate($candles);
 * ```
 */
class SlowStochastic extends Indicator
{
    /**
     * Calculate Slow Stochastic.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - k_period: %K Period (default: 14)
     *                         - k_slowing: %K Slowing Period (default: 3)
     *                         - d_period: %D Period (default: 3)
     * @return array Associative array containing 'k' and 'd' arrays.
     */
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

/**
 * Fast Stochastic
 *
 * The original version of the Stochastic Oscillator.
 *
 * **Components:**
 * - Fast %K (Raw Stochastic)
 * - Fast %D (SMA of Fast %K)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $fastStoch = new FastStochastic();
 * $result = $fastStoch->calculate($candles);
 * ```
 */
class FastStochastic extends Indicator
{
    /**
     * Calculate Fast Stochastic.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - k_period: %K Period (default: 14)
     *                         - d_period: %D Period (default: 3)
     * @return array Associative array containing 'k' and 'd' arrays.
     */
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

/**
 * Full Stochastic
 *
 * A fully customizable Stochastic Oscillator where you can specify the smoothing
 * for both %K and %D.
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $fullStoch = new FullStochastic();
 * $result = $fullStoch->calculate($candles, ['k_period' => 14, 'k_smooth' => 3, 'd_smooth' => 3]);
 * ```
 */
class FullStochastic extends Indicator
{
    /**
     * Calculate Full Stochastic.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - k_period: %K Period (default: 14)
     *                         - k_smooth: %K Smoothing Period (default: 3)
     *                         - d_smooth: %D Smoothing Period (default: 3)
     * @return array Associative array containing 'k' and 'd' arrays.
     */
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

/**
 * Schaff Trend Cycle (STC)
 *
 * A technical indicator that combines MACD with Stochastic to identify trends
 * with less noise and faster reaction time.
 *
 * **Interpretation:**
 * - > 75: Overbought
 * - < 25: Oversold
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $stc = new SchaffTrendCycle();
 * $result = $stc->calculate($candles, ['cycle' => 10, 'fast' => 23, 'slow' => 50]);
 * ```
 */
class SchaffTrendCycle extends Indicator
{
    /**
     * Calculate STC.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - cycle: Cycle Period (default: 10)
     *                         - fast: Fast MACD Period (default: 23)
     *                         - slow: Slow MACD Period (default: 50)
     * @return array Array of STC values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $cyclePeriod = $params['cycle'] ?? 10;
        $fastPeriod = $params['fast'] ?? 23;
        $slowPeriod = $params['slow'] ?? 50;

        // Calculate MACD
        $ema = new EMA;
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

    /**
     * Apply Stochastic formula to an array of values.
     *
     * @param  array  $values  Input values.
     * @param  int  $period  Lookback period.
     * @return array Stochastic values.
     */
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
