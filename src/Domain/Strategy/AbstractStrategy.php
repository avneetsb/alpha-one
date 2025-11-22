<?php

namespace TradingPlatform\Domain\Strategy;

use TradingPlatform\Domain\MarketData\Tick;
use TradingPlatform\Domain\MarketData\Candle;

abstract class AbstractStrategy
{
    protected string $name;
    protected array $config;
    protected array $hp = []; // Hyperparameters
    protected ?string $dna = null;

    public function __construct(string $name, array $config = [])
    {
        $this->name = $name;
        $this->config = $config;
        
        // Initialize hyperparameters with defaults or from DNA
        $this->initializeHyperparameters();
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Define hyperparameters for optimization
     * Override in child strategies
     */
    public function hyperparameters(): array
    {
        return [];
    }

    /**
     * Get DNA string for optimized parameters
     * Override in child strategies
     */
    public function dna(): ?string
    {
        return null;
    }

    /**
     * Initialize hyperparameters
     */
    private function initializeHyperparameters(): void
    {
        $dna = $this->dna();
        $hyperparams = $this->hyperparameters();

        if ($dna && !empty($hyperparams)) {
            // Decode DNA
            $genes = explode('_', $dna);
            foreach ($hyperparams as $index => $param) {
                $gene = $genes[$index] ?? '';
                $type = substr($gene, 0, 1);
                $value = substr($gene, 1);

                if ($type === 'c') {
                    $this->hp[$param['name']] = $param['options'][(int)$value];
                } elseif ($type === 'i') {
                    $this->hp[$param['name']] = (int)$value;
                } elseif ($type === 'f') {
                    $this->hp[$param['name']] = (float)$value;
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
     * Execute strategy logic on candle data
     * Returns a Signal or null
     */
    public function execute(array $candle): ?Signal
    {
        // New method for backtesting compatibility
        return $this->onCandle(Candle::fromArray($candle));
    }

    /**
     * Process a new tick and potentially return a Signal.
     */
    abstract public function onTick(Tick $tick): ?Signal;

    /**
     * Process a new candle and potentially return a Signal.
     */
    abstract public function onCandle(Candle $candle): ?Signal;
}
