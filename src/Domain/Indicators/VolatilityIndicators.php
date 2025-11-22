<?php

namespace TradingPlatform\Domain\Indicators;

/**
 * VOLATILITY INDICATORS
 * All volatility and range-based indicators
 */

/** Average True Range */
class ATR extends Indicator
{
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

/** True Range */
class TrueRange extends Indicator
{
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

/** Normalized ATR */
class NATR extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 14;
        
        $atrCalc = new ATR();
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

/** Mass Index */
class MassIndex extends Indicator
{
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
        
        $ema = new EMA();
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

/** Chaikin Volatility */
class ChaikinVolatility extends Indicator
{
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
        
        $ema = new EMA();
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

/** Standard Deviation */
class StandardDeviation extends Indicator
{
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

/** Ulcer Index (Downside Volatility) */
class UlcerIndex extends Indicator
{
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

/** Historical Volatility */
class HistoricalVolatility extends Indicator
{
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

/** ADXR (ADX Rating) */  
class ADXR extends Indicator
{
    public function calculate(array $data, array $params = []): array
    {
        $period = $params['period'] ?? 14;
        $interval = $params['interval'] ?? 14;
        
        // Calculate ADX first
        $adx = new ADX();
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
