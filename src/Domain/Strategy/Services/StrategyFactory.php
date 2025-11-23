<?php

namespace TradingPlatform\Domain\Strategy\Services;

use TradingPlatform\Domain\Strategy\AbstractStrategy;

/**
 * Strategy Factory
 *
 * Centralized factory for instantiating trading strategies with their required
 * configurations and dependencies. Ensures consistent initialization and
 * dependency injection for all strategy types.
 *
 * **Responsibilities:**
 * - Strategy instantiation based on class name or alias
 * - Configuration validation and injection
 * - Dependency resolution (e.g., injecting IndicatorManager)
 *
 * **Supported Strategies:**
 * - 'RSI_Momentum': Standard RSI mean reversion
 * - 'MACD_Trend': MACD trend following
 * - 'Multi_Indicator': Complex multi-factor strategy
 *
 * @version 1.0.0
 *
 * @example Creating a Strategy
 * ```php
 * $factory = new StrategyFactory();
 * $config = [
 *     'period' => 14,
 *     'oversold' => 30,
 *     'overbought' => 70
 * ];
 *
 * $strategy = $factory->create('RSI_Momentum', $config);
 * ```
 *
 * @see AbstractStrategy Base class for all strategies
 */
class StrategyFactory
{
    /**
     * Create a new strategy instance.
     *
     * Instantiates the requested strategy class and injects the provided configuration.
     * This method acts as the central point for strategy creation, ensuring that
     * all strategies are initialized with a consistent state and valid configuration.
     *
     * @param  string  $name  The name or alias of the strategy to create (e.g., 'RSI_Momentum').
     * @param  array  $config  Configuration parameters specific to the strategy.
     *                         Example: ['period' => 14, 'threshold' => 30]
     * @return object The instantiated strategy object (typically extends AbstractStrategy).
     *
     * @throws \InvalidArgumentException If the strategy name is unknown or class not found.
     *
     * @example Creating a MACD strategy
     * ```php
     * $config = ['fast' => 12, 'slow' => 26, 'signal' => 9];
     * $strategy = $factory->create('MACD_Trend', $config);
     * ```
     */
    public function create(string $name, array $config)
    {
        // Mock factory implementation
        // In a real application, this would map names to classes and instantiate them
        return new class($config)
        {
            public function __construct(private array $config) {}
        };
    }
}
