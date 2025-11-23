<?php

namespace TradingPlatform\Domain\Indicators;

/**
 * VOLUME INDICATORS
 * All volume-based and price-volume indicators
 */

/**
 * Money Flow Index (MFI)
 *
 * A momentum indicator that uses price and volume to measure buying and selling pressure.
 * It is also known as volume-weighted RSI.
 *
 * **Formula:**
 * Typical Price = (High + Low + Close) / 3
 * Raw Money Flow = Typical Price * Volume
 * Money Ratio = Positive Money Flow / Negative Money Flow
 * MFI = 100 - (100 / (1 + Money Ratio))
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $mfi = new MFI();
 * $result = $mfi->calculate($candles, ['period' => 14]);
 * ```
 */
class MFI extends Indicator
{
    /**
     * Calculate Money Flow Index.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 14)
     * @return array Array of MFI values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 14;

        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);
        $volumes = $this->extractVolumes($data);

        $typicalPrices = [];
        $moneyFlow = [];

        foreach ($data as $i => $candle) {
            $typicalPrices[$i] = ($highs[$i] + $lows[$i] + $closes[$i]) / 3;
            $moneyFlow[$i] = $typicalPrices[$i] * $volumes[$i];
        }

        $result = [];

        for ($i = $period; $i < count($data); $i++) {
            $positiveFlow = 0;
            $negativeFlow = 0;

            for ($j = 1; $j <= $period; $j++) {
                if ($typicalPrices[$i - $j + 1] > $typicalPrices[$i - $j]) {
                    $positiveFlow += $moneyFlow[$i - $j + 1];
                } elseif ($typicalPrices[$i - $j + 1] < $typicalPrices[$i - $j]) {
                    $negativeFlow += $moneyFlow[$i - $j + 1];
                }
            }

            if ($negativeFlow == 0) {
                $result[$i] = 100;
            } else {
                $moneyRatio = $positiveFlow / $negativeFlow;
                $result[$i] = 100 - (100 / (1 + $moneyRatio));
            }
        }

        return $result;
    }
}

/**
 * On Balance Volume (OBV)
 *
 * A momentum indicator that uses volume flow to predict changes in stock price.
 *
 * **Formula:**
 * If Close > Close(prev): OBV = OBV(prev) + Volume
 * If Close < Close(prev): OBV = OBV(prev) - Volume
 * If Close = Close(prev): OBV = OBV(prev)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $obv = new OBV();
 * $result = $obv->calculate($candles);
 * ```
 */
class OBV extends Indicator
{
    /**
     * Calculate On Balance Volume.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters (unused).
     * @return array Array of OBV values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $closes = $this->extractClosePrices($data);
        $volumes = $this->extractVolumes($data);

        $result = [0 => $volumes[0]];

        for ($i = 1; $i < count($data); $i++) {
            if ($closes[$i] > $closes[$i - 1]) {
                $result[$i] = $result[$i - 1] + $volumes[$i];
            } elseif ($closes[$i] < $closes[$i - 1]) {
                $result[$i] = $result[$i - 1] - $volumes[$i];
            } else {
                $result[$i] = $result[$i - 1];
            }
        }

        return $result;
    }
}

/**
 * Volume Weighted Average Price (VWAP)
 *
 * The average price a security has traded at throughout the day, based on both
 * volume and price.
 *
 * **Formula:**
 * VWAP = Cumulative(Typical Price * Volume) / Cumulative(Volume)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $vwap = new VWAP();
 * $result = $vwap->calculate($candles);
 * ```
 */
class VWAP extends Indicator
{
    /**
     * Calculate VWAP.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters (unused).
     * @return array Array of VWAP values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);
        $volumes = $this->extractVolumes($data);

        $cumulativeTPV = 0;
        $cumulativeVolume = 0;
        $result = [];

        foreach ($data as $i => $candle) {
            $typicalPrice = ($highs[$i] + $lows[$i] + $closes[$i]) / 3;
            $cumulativeTPV += $typicalPrice * $volumes[$i];
            $cumulativeVolume += $volumes[$i];

            $result[$i] = $cumulativeVolume > 0 ? $cumulativeTPV / $cumulativeVolume : 0;
        }

        return $result;
    }
}

/**
 * Volume Weighted Moving Average (VWMA)
 *
 * A moving average that weights the price data by volume.
 *
 * **Formula:**
 * VWMA = Sum(Price * Volume) / Sum(Volume)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $vwma = new VWMA();
 * $result = $vwma->calculate($candles, ['period' => 20]);
 * ```
 */
class VWMA extends Indicator
{
    /**
     * Calculate VWMA.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 20)
     * @return array Array of VWMA values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;

        $closes = $this->extractClosePrices($data);
        $volumes = $this->extractVolumes($data);

        $result = [];

        for ($i = $period - 1; $i < count($data); $i++) {
            $vwSum = 0;
            $volumeSum = 0;

            for ($j = 0; $j < $period; $j++) {
                $vwSum += $closes[$i - $j] * $volumes[$i - $j];
                $volumeSum += $volumes[$i - $j];
            }

            $result[$i] = $volumeSum > 0 ? $vwSum / $volumeSum : 0;
        }

        return $result;
    }
}

/**
 * Accumulation/Distribution Line (A/D)
 *
 * A momentum indicator that assesses the supply and demand of a stock by looking
 * at where the price closed within the period's range and then multiplying that
 * by volume.
 *
 * **Formula:**
 * MFM = ((Close - Low) - (High - Close)) / (High - Low)
 * MFV = MFM * Volume
 * AD = AD(prev) + MFV
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $ad = new AccumulationDistribution();
 * $result = $ad->calculate($candles);
 * ```
 */
class AccumulationDistribution extends Indicator
{
    /**
     * Calculate Accumulation/Distribution.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters (unused).
     * @return array Array of A/D values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);
        $volumes = $this->extractVolumes($data);

        $result = [0 => 0];

        for ($i = 1; $i < count($data); $i++) {
            if ($highs[$i] != $lows[$i]) {
                $mfm = (($closes[$i] - $lows[$i]) - ($highs[$i] - $closes[$i])) / ($highs[$i] - $lows[$i]);
            } else {
                $mfm = 0;
            }

            $mfv = $mfm * $volumes[$i];
            $result[$i] = $result[$i - 1] + $mfv;
        }

        return $result;
    }
}

/**
 * Chaikin Money Flow (CMF)
 *
 * A volume-weighted average of accumulation and distribution over a specified period.
 *
 * **Formula:**
 * CMF = Sum(MFV, period) / Sum(Volume, period)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $cmf = new ChaikinMoneyFlow();
 * $result = $cmf->calculate($candles, ['period' => 20]);
 * ```
 */
class ChaikinMoneyFlow extends Indicator
{
    /**
     * Calculate Chaikin Money Flow.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: Lookback period (default: 20)
     * @return array Array of CMF values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 20;

        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);
        $volumes = $this->extractVolumes($data);

        $result = [];

        for ($i = $period - 1; $i < count($data); $i++) {
            $mfvSum = 0;
            $volumeSum = 0;

            for ($j = 0; $j < $period; $j++) {
                $idx = $i - $j;
                if ($highs[$idx] != $lows[$idx]) {
                    $mfm = (($closes[$idx] - $lows[$idx]) - ($highs[$idx] - $closes[$idx])) / ($highs[$idx] - $lows[$idx]);
                } else {
                    $mfm = 0;
                }

                $mfvSum += $mfm * $volumes[$idx];
                $volumeSum += $volumes[$idx];
            }

            $result[$i] = $volumeSum > 0 ? $mfvSum / $volumeSum : 0;
        }

        return $result;
    }
}

/**
 * Force Index
 *
 * Uses price and volume to assess the power behind a move or identify possible
 * turning points.
 *
 * **Formula:**
 * Force Index = (Close - Close(prev)) * Volume
 * Smoothed Force Index = EMA(Force Index, period)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $fi = new ForceIndex();
 * $result = $fi->calculate($candles, ['period' => 13]);
 * ```
 */
class ForceIndex extends Indicator
{
    /**
     * Calculate Force Index.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: EMA period (default: 13)
     * @return array Array of Force Index values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 13;

        $closes = $this->extractClosePrices($data);
        $volumes = $this->extractVolumes($data);

        $forceIndex = [];

        for ($i = 1; $i < count($data); $i++) {
            $forceIndex[$i] = ($closes[$i] - $closes[$i - 1]) * $volumes[$i];
        }

        $forceData = [];
        foreach ($forceIndex as $value) {
            $forceData[] = ['close' => $value];
        }

        $ema = new EMA;

        return $ema->calculate($forceData, ['period' => $period]);
    }
}

/**
 * Klinger Volume Oscillator (KVO)
 *
 * A volume-based indicator designed to determine the long-term trend of money flow
 * while remaining sensitive enough to detect short-term fluctuations.
 *
 * **Components:**
 * - KVO = EMA(VF, short) - EMA(VF, long)
 * - Signal Line = EMA(KVO, signal)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $kvo = new KlingerVolumeOscillator();
 * $result = $kvo->calculate($candles, ['short' => 34, 'long' => 55, 'signal' => 13]);
 * ```
 */
class KlingerVolumeOscillator extends Indicator
{
    /**
     * Calculate Klinger Volume Oscillator.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - short: Short EMA period (default: 34)
     *                         - long: Long EMA period (default: 55)
     *                         - signal: Signal EMA period (default: 13)
     * @return array Associative array containing 'kvo' and 'signal' arrays.
     */
    public function calculate(array $data, array $params = []): array
    {
        $shortPeriod = $params['short'] ?? 34;
        $longPeriod = $params['long'] ?? 55;
        $signalPeriod = $params['signal'] ?? 13;

        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $closes = $this->extractClosePrices($data);
        $volumes = $this->extractVolumes($data);

        $vf = [];
        $trend = [];

        for ($i = 1; $i < count($data); $i++) {
            $hlc = ($highs[$i] + $lows[$i] + $closes[$i]) / 3;
            $prevHLC = ($highs[$i - 1] + $lows[$i - 1] + $closes[$i - 1]) / 3;

            $trend[$i] = $hlc > $prevHLC ? 1 : -1;
            $vf[$i] = $volumes[$i] * abs(2 * (($highs[$i] + $lows[$i] + $closes[$i]) / 3 - $hlc)) / ($highs[$i] - $lows[$i]) * $trend[$i];
        }

        $vfData = [];
        foreach ($vf as $value) {
            $vfData[] = ['close' => $value];
        }

        $ema = new EMA;
        $shortEMA = $ema->calculate($vfData, ['period' => $shortPeriod]);
        $longEMA = $ema->calculate($vfData, ['period' => $longPeriod]);

        $kvo = [];
        foreach ($shortEMA as $i => $value) {
            if (isset($longEMA[$i])) {
                $kvo[$i] = $value - $longEMA[$i];
            }
        }

        $kvoData = [];
        foreach ($kvo as $value) {
            $kvoData[] = ['close' => $value];
        }
        $signal = $ema->calculate($kvoData, ['period' => $signalPeriod]);

        return ['kvo' => $kvo, 'signal' => $signal];
    }
}

/**
 * Volume Price Trend (VPT)
 *
 * A technical indicator that uses price and volume to determine the strength of
 * a price trend.
 *
 * **Formula:**
 * VPT = VPT(prev) + (Volume * ((Close - Close(prev)) / Close(prev)))
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $vpt = new VolumePriceTrend();
 * $result = $vpt->calculate($candles);
 * ```
 */
class VolumePriceTrend extends Indicator
{
    /**
     * Calculate Volume Price Trend.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters (unused).
     * @return array Array of VPT values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $closes = $this->extractClosePrices($data);
        $volumes = $this->extractVolumes($data);

        $result = [0 => 0];

        for ($i = 1; $i < count($data); $i++) {
            $priceChange = ($closes[$i] - $closes[$i - 1]) / $closes[$i - 1];
            $result[$i] = $result[$i - 1] + ($volumes[$i] * $priceChange);
        }

        return $result;
    }
}

/**
 * Ease of Movement (EMV)
 *
 * A volume-based oscillator that fluctuates above and below zero. It was developed
 * to measure the "ease" of price movement.
 *
 * **Formula:**
 * Distance Moved = ((High + Low) / 2) - ((High(prev) + Low(prev)) / 2)
 * Box Ratio = (Volume / 100,000,000) / (High - Low)
 * EMV = Distance Moved / Box Ratio
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $emv = new EaseOfMovement();
 * $result = $emv->calculate($candles, ['period' => 14]);
 * ```
 */
class EaseOfMovement extends Indicator
{
    /**
     * Calculate Ease of Movement.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - period: SMA smoothing period (default: 14)
     * @return array Array of EMV values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 14;

        $highs = $this->extractHighPrices($data);
        $lows = $this->extractLowPrices($data);
        $volumes = $this->extractVolumes($data);

        $distanceMoved = [];
        $boxRatio = [];
        $emv = [];

        for ($i = 1; $i < count($data); $i++) {
            $distanceMoved[$i] = (($highs[$i] + $lows[$i]) / 2) - (($highs[$i - 1] + $lows[$i - 1]) / 2);

            $boxHeight = $highs[$i] - $lows[$i];
            $volumeInMillions = $volumes[$i] / 1000000;

            $boxRatio[$i] = $volumeInMillions > 0 ? $boxHeight / $volumeInMillions : 0;

            $emv[$i] = $boxRatio[$i] != 0 ? $distanceMoved[$i] / $boxRatio[$i] : 0;
        }

        // Apply SMA to EMV
        $emvData = [];
        foreach ($emv as $value) {
            $emvData[] = ['close' => $value];
        }

        $sma = new SMA;

        return $sma->calculate($emvData, ['period' => $period]);
    }
}

/**
 * Positive Volume Index (PVI)
 *
 * A cumulative indicator that uses volume change to decide when the smart money
 * is active. PVI focuses on days where volume increased from the previous day.
 *
 * **Formula:**
 * If Volume > Volume(prev): PVI = PVI(prev) + ((Close - Close(prev)) / Close(prev)) * PVI(prev)
 * If Volume <= Volume(prev): PVI = PVI(prev)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $pvi = new PositiveVolumeIndex();
 * $result = $pvi->calculate($candles);
 * ```
 */
class PositiveVolumeIndex extends Indicator
{
    /**
     * Calculate Positive Volume Index.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters (unused).
     * @return array Array of PVI values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $closes = $this->extractClosePrices($data);
        $volumes = $this->extractVolumes($data);

        $result = [0 => 1000]; // Start with base value of 1000

        for ($i = 1; $i < count($data); $i++) {
            if ($volumes[$i] > $volumes[$i - 1]) {
                // Volume increased - update PVI
                $pctChange = ($closes[$i] - $closes[$i - 1]) / $closes[$i - 1];
                $result[$i] = $result[$i - 1] + ($pctChange * $result[$i - 1]);
            } else {
                // Volume decreased or same - PVI unchanged
                $result[$i] = $result[$i - 1];
            }
        }

        return $result;
    }
}

/**
 * Negative Volume Index (NVI)
 *
 * A cumulative indicator that uses volume change to decide when the smart money
 * is active. NVI focuses on days where volume decreased from the previous day.
 *
 * **Formula:**
 * If Volume < Volume(prev): NVI = NVI(prev) + ((Close - Close(prev)) / Close(prev)) * NVI(prev)
 * If Volume >= Volume(prev): NVI = NVI(prev)
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $nvi = new NegativeVolumeIndex();
 * $result = $nvi->calculate($candles);
 * ```
 */
class NegativeVolumeIndex extends Indicator
{
    /**
     * Calculate Negative Volume Index.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters (unused).
     * @return array Array of NVI values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $closes = $this->extractClosePrices($data);
        $volumes = $this->extractVolumes($data);

        $result = [0 => 1000]; // Start with base value of 1000

        for ($i = 1; $i < count($data); $i++) {
            if ($volumes[$i] < $volumes[$i - 1]) {
                // Volume decreased - update NVI
                $pctChange = ($closes[$i] - $closes[$i - 1]) / $closes[$i - 1];
                $result[$i] = $result[$i - 1] + ($pctChange * $result[$i - 1]);
            } else {
                // Volume increased or same - NVI unchanged
                $result[$i] = $result[$i - 1];
            }
        }

        return $result;
    }
}

/**
 * Volume Oscillator
 *
 * Measures the difference between two volume moving averages.
 *
 * **Formula:**
 * VO = ((Fast EMA - Slow EMA) / Slow EMA) * 100
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * $vo = new VolumeOscillator();
 * $result = $vo->calculate($candles, ['fast' => 5, 'slow' => 10]);
 * ```
 */
class VolumeOscillator extends Indicator
{
    /**
     * Calculate Volume Oscillator.
     *
     * @param  array  $data  Array of candles.
     * @param  array  $params  Parameters:
     *                         - fast: Fast EMA period (default: 5)
     *                         - slow: Slow EMA period (default: 10)
     * @return array Array of Volume Oscillator values.
     */
    public function calculate(array $data, array $params = []): array
    {
        $fastPeriod = $params['fast'] ?? 5;
        $slowPeriod = $params['slow'] ?? 10;

        $volumes = $this->extractVolumes($data);
        $volumeData = [];
        foreach ($volumes as $vol) {
            $volumeData[] = ['close' => $vol];
        }

        $ema = new EMA;
        $fastEMA = $ema->calculate($volumeData, ['period' => $fastPeriod]);
        $slowEMA = $ema->calculate($volumeData, ['period' => $slowPeriod]);

        $result = [];
        foreach ($fastEMA as $i => $fast) {
            if (isset($slowEMA[$i]) && $slowEMA[$i] != 0) {
                $result[$i] = (($fast - $slowEMA[$i]) / $slowEMA[$i]) * 100;
            }
        }

        return $result;
    }
}
