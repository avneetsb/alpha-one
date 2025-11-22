<?php

namespace TradingPlatform\Domain\Strategy;

use TradingPlatform\Domain\Indicators\IndicatorManager;
use TradingPlatform\Domain\MarketData\{Tick, Candle};

/**
 * Advanced Multi-Indicator Strategy (97-Indicator Library)
 * 
 * Leverages the best indicators from each category:
 * - Trend: T3 (low-lag smoothing) or MAMA (adaptive)
 * - Momentum: StochRSI (sensitive) or ConnorsRSI (mean-reversion)
 * - Volume: OBV + Volume Oscillator (confirmation)
 * - Volatility: ATR + Ulcer Index (risk management)
 * - Advanced: Schaff Trend Cycle (combined MACD+Stochastic)
 * 
 * Designed for hyperparameter optimization with 97 indicators available
 */
class MultiIndicatorStrategy extends AbstractStrategy
{
    private IndicatorManager $indicators;
    private array $candles = [];

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->indicators = new IndicatorManager();
    }

    /**
     * Define hyperparameters for optimization
     */
    public function hyperparameters(): array
    {
        return [
            // === SELECTABLE INDICATORS ===
            // Trend Indicator Selection
            ['name' => 'trend_indicator', 'type' => 'categorical', 
             'options' => ['t3', 'mama', 'gmma', 'supertrend', 'ichimoku'], 
             'default' => 't3'],
            
            // Momentum Indicator Selection
            ['name' => 'momentum_indicator', 'type' => 'categorical',
             'options' => ['stochrsi', 'connorsrsi', 'stc', 'rsi', 'macd'],
             'default' => 'stochrsi'],
            
            // Volume Indicator
            ['name' => 'volume_indicator', 'type' => 'categorical',
             'options' => ['obv', 'vosc', 'mfi', 'cmf'],
             'default' => 'obv'],
            
            // === T3 Parameters ===
            ['name' => 't3_period', 'type' => 'int', 'min' => 3, 'max' => 10, 'default' => 5],
            ['name' => 't3_vfactor', 'type' => 'float', 'min' => 0.5, 'max' => 0.9, 'step' => 0.1, 'default' => 0.7],
            
            // === MAMA Parameters ===
            ['name' => 'mama_fast_limit', 'type' => 'float', 'min' => 0.3, 'max' => 0.7, 'step' => 0.1, 'default' => 0.5],
            ['name' => 'mama_slow_limit', 'type' => 'float', 'min' => 0.01, 'max' => 0.1, 'step' => 0.01, 'default' => 0.05],
            
            // === StochRSI Parameters ===
            ['name' => 'stochrsi_rsi_period', 'type' => 'int', 'min' => 10, 'max' => 20, 'default' => 14],
            ['name' => 'stochrsi_stoch_period', 'type' => 'int', 'min' => 10, 'max' => 20, 'default' => 14],
            ['name' => 'stochrsi_oversold', 'type' => 'float', 'min' => 0.15, 'max' => 0.25, 'step' => 0.05, 'default' => 0.20],
            ['name' => 'stochrsi_overbought', 'type' => 'float', 'min' => 0.75, 'max' => 0.85, 'step' => 0.05, 'default' => 0.80],
            
            // === ConnorsRSI Parameters ===
            ['name' => 'connorsrsi_period', 'type' => 'int', 'min' => 2, 'max' => 5, 'default' => 3],
            ['name' => 'connorsrsi_oversold', 'type' => 'int', 'min' => 5, 'max' => 15, 'default' => 10],
            ['name' => 'connorsrsi_overbought', 'type' => 'int', 'min' => 85, 'max' => 95, 'default' => 90],
            
            // === Schaff Trend Cycle Parameters ===
            ['name' => 'stc_cycle', 'type' => 'int', 'min' => 8, 'max' => 12, 'default' => 10],
            ['name' => 'stc_fast', 'type' => 'int', 'min' => 20, 'max' => 26, 'default' => 23],
            ['name' => 'stc_slow', 'type' => 'int', 'min' => 45, 'max' => 55, 'default' => 50],
            
            // === Volume Oscillator Parameters ===
            ['name' => 'vosc_fast', 'type' => 'int', 'min' => 5, 'max' => 15, 'default' => 12],
            ['name' => 'vosc_slow', 'type' => 'int', 'min' => 20, 'max' => 30, 'default' => 26],
            
            // === Risk Management ===
            ['name' => 'atr_period', 'type' => 'int', 'min' => 10, 'max' => 20, 'default' => 14],
            ['name' => 'atr_stop_multiplier', 'type' => 'float', 'min' => 1.5, 'max' => 3.0, 'step' => 0.5, 'default' => 2.0],
            ['name' => 'atr_target_multiplier', 'type' => 'float', 'min' => 2.0, 'max' => 4.0, 'step' => 0.5, 'default' => 3.0],
            
            // === Signal Confirmation ===
            ['name' => 'require_volume_confirmation', 'type' => 'categorical', 
             'options' => ['yes', 'no'], 
             'default' => 'yes'],
            ['name' => 'min_confidence', 'type' => 'float', 'min' => 0.6, 'max' => 0.9, 'step' => 0.1, 'default' => 0.7],
        ];
    }

    /**
     * Override this method to use optimized DNA
     */
    public function dna(): ?string
    {
        // Return null for default parameters
        // After optimization, paste the best DNA here
        return null;
    }

    public function onTick(Tick $tick): ?Signal
    {
        // Not used in backtesting
        return null;
    }

    public function onCandle(Candle $candle): ?Signal
    {
        // Store candles
        $this->candles[] = [
            'open' => (float)$candle->open,
            'high' => (float)$candle->high,
            'low' => (float)$candle->low,
            'close' => (float)$candle->close,
            'volume' => (int)$candle->volume,
        ];

        if (count($this->candles) > $this->maxCandles) {
            array_shift($this->candles);
        }

        // Need enough data
        if (count($this->candles) < 100) {
            return null;
        }

        // Get selected indicators
        $trendIndicator = $this->hp['trend_indicator'];
        $momentumIndicator = $this->hp['momentum_indicator'];
        $volumeIndicator = $this->hp['volume_indicator'];

        // === Calculate Trend ===
        $trendSignal = $this->calculateTrendSignal($trendIndicator);
        
        // === Calculate Momentum ===
        $momentumSignal = $this->calculateMomentumSignal($momentumIndicator);
        
        // === Calculate Volume ===
        $volumeConfirmed = $this->calculateVolumeConfirmation($volumeIndicator);

        // === Calculate Risk Metrics ===
        $atr = $this->indicators->calculate('atr', $this->candles, [
            'period' => $this->hp['atr_period'],
        ]);
        
        $ulcer = $this->indicators->calculate('ulcer', $this->candles, [
            'period' => $this->hp['atr_period'],
        ]);

        $currentIndex = count($this->candles) - 1;
        $currentATR = $atr[$currentIndex] ?? 0;
        $currentUlcer = $ulcer[$currentIndex] ?? 0;
        $currentClose = $this->candles[$currentIndex]['close'];

        // Require volume confirmation if enabled
        $requireVolumeConf = $this->hp['require_volume_confirmation'] === 'yes';
        
        // === BUY SIGNAL ===
        if ($trendSignal === 'buy' && $momentumSignal === 'buy') {
            if ($requireVolumeConf && !$volumeConfirmed) {
                return null;
            }

            // Calculate confidence based on signal strength
            $confidence = $this->calculateConfidence($trendSignal, $momentumSignal, $volumeConfirmed);
            
            if ($confidence < $this->hp['min_confidence']) {
                return null;
            }

            return new Signal(
                'BUY',
                $currentClose,
                confidence: $confidence,
                stopLoss: $currentClose - ($currentATR * $this->hp['atr_stop_multiplier']),
                takeProfit: $currentClose + ($currentATR * $this->hp['atr_target_multiplier'])
            );
        }

        // === SELL SIGNAL ===
        if ($trendSignal === 'sell' && $momentumSignal === 'sell') {
            if ($requireVolumeConf && !$volumeConfirmed) {
                return null;
            }

            $confidence = $this->calculateConfidence($trendSignal, $momentumSignal, $volumeConfirmed);
            
            if ($confidence < $this->hp['min_confidence']) {
                return null;
            }

            return new Signal(
                'SELL',
                $currentClose,
                confidence: $confidence,
                stopLoss: $currentClose + ($currentATR * $this->hp['atr_stop_multiplier']),
                takeProfit: $currentClose - ($currentATR * $this->hp['atr_target_multiplier'])
            );
        }

        return null;
    }

    private function calculateTrendSignal(string $indicator): ?string
    {
        $currentIndex = count($this->candles) - 1;
        $currentClose = $this->candles[$currentIndex]['close'];

        switch ($indicator) {
            case 't3':
                $t3 = $this->indicators->calculate('t3', $this->candles, [
                    'period' => $this->hp['t3_period'],
                    'vfactor' => $this->hp['t3_vfactor'],
                ]);
                $t3Value = $t3[$currentIndex] ?? null;
                if ($t3Value) {
                    return $currentClose > $t3Value ? 'buy' : 'sell';
                }
                break;

            case 'mama':
                $mama = $this->indicators->calculate('mama', $this->candles, [
                    'fast_limit' => $this->hp['mama_fast_limit'],
                    'slow_limit' => $this->hp['mama_slow_limit'],
                ]);
                $mamaValue = $mama['mama'][$currentIndex] ?? null;
                if ($mamaValue) {
                    return $currentClose > $mamaValue ? 'buy' : 'sell';
                }
                break;

            case 'supertrend':
                $st = $this->indicators->calculate('supertrend', $this->candles, [
                    'period' => 10,
                    'multiplier' => 3.0,
                ]);
                $direction = $st['direction'][$currentIndex] ?? 0;
                return $direction === 1 ? 'buy' : ($direction === -1 ? 'sell' : null);

            default:
                return null;
        }

        return null;
    }

    private function calculateMomentumSignal(string $indicator): ?string
    {
        $currentIndex = count($this->candles) - 1;

        switch ($indicator) {
            case 'stochrsi':
                $stochRSI = $this->indicators->calculate('stochrsi', $this->candles, [
                    'rsi_period' => $this->hp['stochrsi_rsi_period'],
                    'stoch_period' => $this->hp['stochrsi_stoch_period'],
                ]);
                
                if (!empty($stochRSI['k'])) {
                    $kValue = end($stochRSI['k']) / 100; // Normalize to 0-1
                    if ($kValue < $this->hp['stochrsi_oversold']) return 'buy';
                    if ($kValue > $this->hp['stochrsi_overbought']) return 'sell';
                }
                break;

            case 'connorsrsi':
                $crsi = $this->indicators->calculate('connorsrsi', $this->candles, [
                    'rsi_period' => $this->hp['connorsrsi_period'],
                ]);
                $crsiValue = $crsi[$currentIndex] ?? null;
                if ($crsiValue !== null) {
                    if ($crsiValue < $this->hp['connorsrsi_oversold']) return 'buy';
                    if ($crsiValue > $this->hp['connorsrsi_overbought']) return 'sell';
                }
                break;

            case 'stc':
                $stc = $this->indicators->calculate('stc', $this->candles, [
                    'cycle' => $this->hp['stc_cycle'],
                    'fast' => $this->hp['stc_fast'],
                    'slow' => $this->hp['stc_slow'],
                ]);
                
                if (!empty($stc)) {
                    $stcValue = end($stc);
                    if ($stcValue < 25) return 'buy';
                    if ($stcValue > 75) return 'sell';
                }
                break;

            default:
                return null;
        }

        return null;
    }

    private function calculateVolumeConfirmation(string $indicator): bool
    {
        $currentIndex = count($this->candles) - 1;

        switch ($indicator) {
            case 'obv':
                $obv = $this->indicators->calculate('obv', $this->candles);
                if (count($obv) >= 2) {
                    $current = $obv[$currentIndex] ?? 0;
                    $previous = $obv[$currentIndex - 1] ?? 0;
                    return $current > $previous; // Volume increasing
                }
                break;

            case 'vosc':
                $vosc = $this->indicators->calculate('vosc', $this->candles, [
                    'fast_period' => $this->hp['vosc_fast'],
                    'slow_period' => $this->hp['vosc_slow'],
                ]);
                $voscValue = $vosc[$currentIndex] ?? null;
                return $voscValue !== null && $voscValue > 0;

            case 'mfi':
                $mfi = $this->indicators->calculate('mfi', $this->candles, ['period' => 14]);
                $mfiValue = $mfi[$currentIndex] ?? 50;
                return $mfiValue > 40 && $mfiValue < 60; // Neutral zone

            default:
                return true;
        }

        return true;
    }

    private function calculateConfidence(string $trend, string $momentum, bool $volumeConfirmed): float
    {
        $confidence = 0.5;

        // Both signals agree
        if ($trend === $momentum) {
            $confidence += 0.2;
        }

        // Volume confirmation
        if ($volumeConfirmed) {
            $confidence += 0.15;
        }

        // Base confidence for having signals
        $confidence += 0.15;

        return min($confidence, 0.95);
    }
}
