<?php

namespace TradingPlatform\Domain\Strategy;

use TradingPlatform\Domain\MarketData\Candle;
use TradingPlatform\Domain\MarketData\Tick;

/**
 * Class AbstractStrategy
 *
 * Base class for all trading strategies providing common infrastructure for
 * signal generation, hyperparameter optimization, and strategy lifecycle management.
 *
 * **Strategy Development Pattern:**
 * 1. Extend AbstractStrategy
 * 2. Implement analyze() method with trading logic
 * 3. Define hyperparameters() for optimization
 * 4. Optionally override onTick() for real-time trading
 *
 * **Key Features:**
 * - Hyperparameter optimization support (genetic algorithms)
 * - DNA encoding for parameter persistence
 * - Configuration management
 * - Signal generation framework
 * - Backtesting compatibility
 *
 * **Lifecycle Methods:**
 * - analyze(): Core strategy logic (required)
 * - onTick(): Real-time tick processing (optional)
 * - hyperparameters(): Define optimizable parameters (optional)
 * - dna(): Encode/decode optimized parameters (optional)
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Creating a simple RSI strategy
 * ```php
 * class RSIStrategy extends AbstractStrategy
 * {
 *     public function analyze(array $candles): ?string
 *     {
 *         $rsi = $this->calculateRSI($candles, $this->hp['period'] ?? 14);
 *
 *         if ($rsi < $this->hp['oversold'] ?? 30) {
 *             return Signal::BUY;
 *         } elseif ($rsi > $this->hp['overbought'] ?? 70) {
 *             return Signal::SELL;
 *         }
 *
 *         return null;
 *     }
 *
 *     public function hyperparameters(): array
 *     {
 *         return [
 *             'period' => ['min' => 7, 'max' => 21, 'step' => 1],
 *             'oversold' => ['min' => 20, 'max' => 40, 'step' => 5],
 *             'overbought' => ['min' => 60, 'max' => 80, 'step' => 5],
 *         ];
 *     }
 * }
 * ```
 *
 * @see Signal For signal structure
 * @see BacktestEngine For strategy testing
 */
abstract class AbstractStrategy
{
    /**
     * @var string The name of the strategy.
     */
    protected string $name;

    /**
     * @var array Configuration parameters.
     */
    protected array $config;

    /**
     * @var array Hyperparameters.
     */
    protected array $hp = [];

    /**
     * @var string|null DNA string for optimization.
     */
    protected ?string $dna = null;

    /**
     * AbstractStrategy constructor.
     *
     * @param  string  $name  Strategy name.
     * @param  array  $config  Configuration array.
     */
    public function __construct(string $name, array $config = [])
    {
        $this->name = $name;
        $this->config = $config;

        // Initialize hyperparameters with defaults or from DNA
        $this->initializeHyperparameters();
    }

    /**
     * Get the strategy name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Define hyperparameters for optimization.
     * Override in child strategies.
     */
    public function hyperparameters(): array
    {
        return [];
    }

    /**
     * Get DNA string for optimized parameters.
     * Override in child strategies.
     */
    public function dna(): ?string
    {
        return null;
    }

    /**
     * Initialize hyperparameters from DNA or defaults.
     */
    private function initializeHyperparameters(): void
    {
        $dna = $this->dna();
        $hyperparams = $this->hyperparameters();

        if ($dna && ! empty($hyperparams)) {
            // Decode DNA
            $genes = explode('_', $dna);
            foreach ($hyperparams as $index => $param) {
                $gene = $genes[$index] ?? '';
                $type = substr($gene, 0, 1);
                $value = substr($gene, 1);

                if ($type === 'c') {
                    $this->hp[$param['name']] = $param['options'][(int) $value];
                } elseif ($type === 'i') {
                    $this->hp[$param['name']] = (int) $value;
                } elseif ($type === 'f') {
                    $this->hp[$param['name']] = (float) $value;
                } else {
                    $this->hp[$param['name']] = $param['default'];
                }
            }
        } else {
            // Use defaults
            foreach ($hyperparams as $param) {
                $this->hp[$param['name']] = $param['default'];
            }
        }
    }

    /**
     * Execute strategy logic on candle data.
     * Returns a Signal or null.
     *
     * @param  array  $candle  Candle data array.
     */
    public function execute(array $candle): ?Signal
    {
        // New method for backtesting compatibility
        return $this->onCandle(Candle::fromArray($candle));
    }

    /**
     * Process a new tick and potentially return a Signal.
     *
     * @param  Tick  $tick  The new tick.
     */
    abstract public function onTick(Tick $tick): ?Signal;

    /**
     * Process a new candle and potentially return a Signal.
     *
     * @param  Candle  $candle  The new candle.
     */
    abstract public function onCandle(Candle $candle): ?Signal;
}
