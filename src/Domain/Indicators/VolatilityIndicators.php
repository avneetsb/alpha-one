<?php

namespace TradingPlatform\Domain\Indicators;

/**
 * VOLATILITY INDICATORS
 * All volatility and range-based indicators
 */

/**
 * Average True Range (ATR)
 *
 * A technical analysis indicator that measures market volatility by decomposing
 * the entire range of an asset price for that period.
 *
 * **Formula:**
 * TR = Max(High - Low, |High - Close(prev)|, |Low - Close(prev)|)
 * ATR = ((ATR(prev) * (n - 1)) + TR) / n
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $atr = new ATR();
 * $result = $atr->calculate($candles, ['period' => 14]);
 * ```
 */
class ATR extends Indicator
{
    /**
     * Calculate ATR.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 14)
     * @return array Array of ATR values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 14;

        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);

        $tr = [];
        $tr[0] = $highs[0] - $lows[0];

        for ($i = 1; $i < count($data); $i++) {
            $tr1 = $highs[$i] - $lows[$i];
            $tr2 = abs($highs[$i] - $closes[$i - 1]);
            $tr3 = abs($lows[$i] - $closes[$i - 1]);
            $tr[$i] = max($tr1, $tr2, $tr3);
        }

        $atr = [];
        $atr[$period - 1] = array_sum(array_slice($tr, 0, $period)) / $period;

        for ($i = $period; $i < count($data); $i++) {
            $atr[$i] = (($atr[$i - 1] * ($period - 1)) + $tr[$i]) / $period;
        }

        return $atr;
    }
}

/**
 * True Range (TR)
 *
 * The greatest of the following:
 * - Current High less the current Low
 * - The absolute value of the current High less the previous Close
 * - The absolute value of the current Low less the previous Close
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $tr = new TrueRange();
 * $result = $tr->calculate($candles);
 * ```
 */
class TrueRange extends Indicator
{
    /**
     * Calculate True Range.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters (unused).
     * @return array Array of TR values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);

        $result = [];
        $result[0] = $highs[0] - $lows[0];

        for ($i = 1; $i < count($data); $i++) {
            $tr1 = $highs[$i] - $lows[$i];
            $tr2 = abs($highs[$i] - $closes[$i - 1]);
            $tr3 = abs($lows[$i] - $closes[$i - 1]);

            $result[$i] = max($tr1, $tr2, $tr3);
        }

        return $result;
    }
}

/**
 * Normalized Average True Range (NATR)
 *
 * A measure of volatility that is normalized by the closing price, making it
 * useful for comparing volatility across different assets.
 *
 * **Formula:**
 * NATR = (ATR / Close) * 100
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $natr = new NATR();
 * $result = $natr->calculate($candles, ['period' => 14]);
 * ```
 */
class NATR extends Indicator
{
    /**
     * Calculate NATR.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: ATR period (default: 14)
     * @return array Array of NATR values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 14;

        $atrCalc = new ATR;
        $atr = $atrCalc->calculate($data, ['period' => $period]);

        $closes = $this->extractClosePrices($data);

        $result = [];
        foreach ($atr as $i => $atrValue) {
            if ($closes[$i] != 0) {
                $result[$i] = ($atrValue / $closes[$i]) * 100;
            }
        }

        return $result;
    }
}

/**
 * Mass Index
 *
 * Uses the high-low range to identify trend reversals based on range expansions.
 *
 * **Formula:**
 * MI = Sum(EMA(High-Low, 9) / EMA(EMA(High-Low, 9), 9), 25)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $mi = new MassIndex();
 * $result = $mi->calculate($candles, ['period' => 25, 'ema_period' => 9]);
 * ```
 */
class MassIndex extends Indicator
{
    /**
     * Calculate Mass Index.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Summation period (default: 25)
     *                         - ema_period: EMA period (default: 9)
     * @return array Array of Mass Index values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 25;
        $emaPeriod = $params['ema_period'] ?? 9;

        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);

        $ranges = [];
        for ($i = 0; $i < count($data); $i++) {
            $ranges[] = ['close' => $highs[$i] - $lows[$i]];
        }

        $ema = new EMA;
        $singleEMA = $ema->calculate($ranges, ['period' => $emaPeriod]);

        $singleEMAData = [];
        foreach ($singleEMA as $value) {
            $singleEMAData[] = ['close' => $value];
        }
        $doubleEMA = $ema->calculate($singleEMAData, ['period' => $emaPeriod]);

        $emaRatio = [];
        foreach ($singleEMA as $i => $value) {
            if (isset($doubleEMA[$i]) && $doubleEMA[$i] != 0) {
                $emaRatio[$i] = $value / $doubleEMA[$i];
            }
        }

        $result = [];
        $emaRatioValues = array_values($emaRatio);
        for ($i = $period - 1; $i < count($emaRatioValues); $i++) {
            $result[$i] = array_sum(array_slice($emaRatioValues, $i - $period + 1, $period));
        }

        return $result;
    }
}

/**
 * Chaikin Volatility
 *
 * Measures the volatility of an asset by calculating the spread between the
 * high and low prices.
 *
 * **Formula:**
 * HL = High - Low
 * EMA_HL = EMA(HL, period)
 * CV = ((EMA_HL - EMA_HL(prev)) / EMA_HL(prev)) * 100
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $cv = new ChaikinVolatility();
 * $result = $cv->calculate($candles, ['period' => 10, 'roc_period' => 10]);
 * ```
 */
class ChaikinVolatility extends Indicator
{
    /**
     * Calculate Chaikin Volatility.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: EMA period (default: 10)
     *                         - roc_period: ROC period (default: 10)
     * @return array Array of Chaikin Volatility values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 10;
        $rocPeriod = $params['roc_period'] ?? 10;

        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);

        $ranges = [];
        for ($i = 0; $i < count($data); $i++) {
            $ranges[] = ['close' => $highs[$i] - $lows[$i]];
        }

        $ema = new EMA;
        $emaRange = $ema->calculate($ranges, ['period' => $period]);

        $result = [];
        $emaValues = array_values($emaRange);

        for ($i = $rocPeriod; $i < count($emaValues); $i++) {
            if ($emaValues[$i - $rocPeriod] != 0) {
                $result[$i] = (($emaValues[$i] - $emaValues[$i - $rocPeriod]) / $emaValues[$i - $rocPeriod]) * 100;
            }
        }

        return $result;
    }
}

/**
 * Standard Deviation
 *
 * A statistical measure of market volatility, measuring how widely prices are
 * dispersed from the average price.
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $stdDev = new StandardDeviation();
 * $result = $stdDev->calculate($candles, ['period' => 20]);
 * ```
 */
class StandardDeviation extends Indicator
{
    /**
     * Calculate Standard Deviation.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 20)
     * @return array Array of Standard Deviation values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $prices = $this->extractClosePrices($data);

        $result = [];

        for ($i = $period - 1; $i < count($prices); $i++) {
            $subset = array_slice($prices, $i - $period + 1, $period);
            $mean = array_sum($subset) / $period;

            $variance = 0;
            foreach ($subset as $price) {
                $variance += pow($price - $mean, 2);
            }
            $variance /= $period;

            $result[$i] = sqrt($variance);
        }

        return $result;
    }
}

/**
 * Ulcer Index
 *
 * Measures downside risk. The index increases as the price moves farther away
 * from a recent high.
 *
 * **Formula:**
 * PD = [(Close - Highest High) / Highest High] * 100
 * UI = Sqrt(Sum(PD^2) / n)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $ui = new UlcerIndex();
 * $result = $ui->calculate($candles, ['period' => 14]);
 * ```
 */
class UlcerIndex extends Indicator
{
    /**
     * Calculate Ulcer Index.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 14)
     * @return array Array of Ulcer Index values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 14;
        $prices = $this->extractClosePrices($data);

        $result = [];

        for ($i = $period - 1; $i < count($prices); $i++) {
            $subset = array_slice($prices, $i - $period + 1, $period);
            $highestPrice = max($subset);

            $percentageDrawdowns = [];
            foreach ($subset as $price) {
                $drawdown = (($price - $highestPrice) / $highestPrice) * 100;
                $percentageDrawdowns[] = $drawdown ** 2;
            }

            $squaredAverage = array_sum($percentageDrawdowns) / $period;
            $result[$i] = sqrt($squaredAverage);
        }

        return $result;
    }
}

/**
 * Historical Volatility
 *
 * The realized volatility of a financial instrument over a given time period.
 *
 * **Formula:**
 * Log Return = ln(Price / Price(prev))
 * HV = Stdev(Log Returns) * Sqrt(Annualization Factor) * 100
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $hv = new HistoricalVolatility();
 * $result = $hv->calculate($candles, ['period' => 20, 'annualize' => 252]);
 * ```
 */
class HistoricalVolatility extends Indicator
{
    /**
     * Calculate Historical Volatility.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 20)
     *                         - annualize: Annualization factor (default: 252)
     * @return array Array of Historical Volatility values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;
        $annualizationFactor = $params['annualize'] ?? 252; // Trading days per year

        $prices = $this->extractClosePrices($data);

        // Calculate log returns
        $logReturns = [];
        for ($i = 1; $i < count($prices); $i++) {
            if ($prices[$i - 1] > 0) {
                $logReturns[$i] = log($prices[$i] / $prices[$i - 1]);
            }
        }

        $result = [];

        for ($i = $period; $i < count($logReturns); $i++) {
            $subset = array_slice($logReturns, $i - $period, $period);
            $mean = array_sum($subset) / $period;

            $variance = 0;
            foreach ($subset as $return) {
                $variance += pow($return - $mean, 2);
            }
            $variance /= $period;

            // Annualized volatility as percentage
            $result[$i] = sqrt($variance * $annualizationFactor) * 100;
        }

        return $result;
    }
}

/**
 * Average Directional Movement Index Rating (ADXR)
 *
 * A smoothed version of the ADX indicator.
 *
 * **Formula:**
 * ADXR = (ADX + ADX(n periods ago)) / 2
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $adxr = new ADXR();
 * $result = $adxr->calculate($candles, ['period' => 14, 'interval' => 14]);
 * ```
 */
class ADXR extends Indicator
{
    /**
     * Calculate ADXR.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: ADX period (default: 14)
     *                         - interval: Smoothing interval (default: 14)
     * @return array Array of ADXR values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 14;
        $interval = $params['interval'] ?? 14;

        // Calculate ADX first
        $adx = new ADX;
        $adxValues = $adx->calculate($data, ['period' => $period]);

        $result = [];
        $adxArray = array_values($adxValues);

        // ADXR is average of current ADX and ADX from 'interval' periods ago
        for ($i = $interval; $i < count($adxArray); $i++) {
            $result[$i] = ($adxArray[$i] + $adxArray[$i - $interval]) / 2;
        }

        return $result;
    }
}
