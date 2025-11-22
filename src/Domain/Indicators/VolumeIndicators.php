<?php

namespace TradingPlatform\Domain\Indicators;

/**
 * VOLUME INDICATORS
 * All volume-based and price-volume indicators
 */

/** Money Flow Index */
class MFI extends Indicator
{
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

/** On Balance Volume */
class OBV extends Indicator
{
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

/** Volume Weighted Average Price */
class VWAP extends Indicator
{
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

/** Volume Weighted Moving Average */
class VWMA extends Indicator
{
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

/** Accumulation/Distribution */
class AccumulationDistribution extends Indicator
{
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

/** Chaikin Money Flow */
class ChaikinMoneyFlow extends Indicator
{
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

/** Force Index */
class ForceIndex extends Indicator
{
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
        
        $ema = new EMA();
        return $ema->calculate($forceData, ['period' => $period]);
    }
}

/** Klinger Volume Oscillator */
class KlingerVolumeOscillator extends Indicator
{
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
        
        $ema = new EMA();
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

/** Volume Price Trend (VPT) */
class VolumePriceTrend extends Indicator
{
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

/** Ease of Movement (EMV) */
class EaseOfMovement extends Indicator
{
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
        
        $sma = new SMA();
        return $sma->calculate($emvData, ['period' => $period]);
    }
}

/** Positive Volume Index (PVI) */
class PositiveVolumeIndex extends Indicator
{
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

/** Negative Volume Index (NVI) */
class NegativeVolumeIndex extends Indicator
{
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

/** Volume Oscillator */
class VolumeOscillator extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $fastPeriod = $params['fast'] ?? 5;
        $slowPeriod = $params['slow'] ?? 10;
        
        $volumes = $this->extractVolumes($data);
        $volumeData = [];
        foreach ($volumes as $vol) {
            $volumeData[] = ['close' => $vol];
        }
        
        $ema = new EMA();
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
