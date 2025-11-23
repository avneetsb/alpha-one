<?php

namespace TradingPlatform\Domain\Strategy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Strategy
 *
 * Represents a trading strategy definition in the strategy registry.
 * Stores metadata, hyperparameter schemas, and relationships to configurations
 * and optimization runs.
 *
 * **Strategy Lifecycle:**
 * 1. Create strategy definition with class name and schema
 * 2. Create configurations with specific hyperparameters
 * 3. Run optimizations to find best parameters
 * 4. Deploy optimized configuration for live trading
 *
 * **Key Components:**
 * - Class name: Fully qualified PHP class implementing AbstractStrategy
 * - Hyperparameter schema: Defines optimizable parameters and ranges
 * - Configurations: Specific parameter sets for backtesting/live trading
 * - Optimization runs: Historical optimization attempts
 *
 * **Categories:**
 * - 'momentum': RSI, MACD, Stochastic strategies
 * - 'trend': Moving average, breakout strategies
 * - 'mean_reversion': Bollinger Bands, statistical arbitrage
 * - 'volatility': ATR-based, Keltner Channel strategies
 * - 'multi_indicator': Combined indicator strategies
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Creating a strategy definition
 * ```php
 * $strategy = Strategy::create([
 *     'name' => 'RSI Momentum',
 *     'class_name' => 'TradingPlatform\\Strategies\\RSIStrategy',
 *     'description' => 'RSI-based momentum strategy with dynamic thresholds',
 *     'category' => 'momentum',
 *     'version' => '1.0.0',
 *     'hyperparameters_schema' => [
 *         'period' => ['min' => 7, 'max' => 21, 'step' => 1, 'default' => 14],
 *         'oversold' => ['min' => 20, 'max' => 40, 'step' => 5, 'default' => 30],
 *         'overbought' => ['min' => 60, 'max' => 80, 'step' => 5, 'default' => 70],
 *     ],
 *     'created_by' => 'trader@example.com',
 *     'is_active' => true,
 * ]);
 * ```
 *
 * @property int $id Primary key
 * @property string $name Strategy display name
 * @property string $class_name Fully qualified class name
 * @property string $description Strategy description
 * @property string $category Strategy category
 * @property string $version Version string (semver)
 * @property array $hyperparameters_schema Parameter definitions for optimization
 * @property string $created_by User who created the strategy
 * @property bool $is_active Whether strategy is active
 * @property \DateTime $created_at Creation timestamp
 * @property \DateTime $updated_at Last update timestamp
 */
class Strategy extends Model
{
    protected $fillable = [
        'name',
        'class_name',
        'description',
        'category',
        'version',
        'hyperparameters_schema',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'hyperparameters_schema' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get all configurations for this strategy.
     *
     * Returns all parameter configurations created for this strategy,
     * including default, optimized, and custom configurations.
     *
     * @return HasMany Eloquent relationship to StrategyConfiguration
     *
     * @example Getting all configurations
     * ```php
     * $strategy = Strategy::find(1);
     * $configs = $strategy->configurations;
     *
     * foreach ($configs as $config) {
     *     echo "{$config->name}: Sharpe {$config->performance_metrics['sharpe_ratio']}\n";
     * }
     * ```
     */
    public function configurations(): HasMany
    {
        return $this->hasMany(StrategyConfiguration::class);
    }

    /**
     * Get all optimization runs for this strategy.
     */
    public function optimizationRuns(): HasMany
    {
        return $this->hasMany(OptimizationRun::class);
    }

    /**
     * Get active configurations.
     */
    public function activeConfigurations(): HasMany
    {
        return $this->configurations()->whereNull('deleted_at');
    }

    /**
     * Get favorite configurations.
     */
    public function favoriteConfigurations(): HasMany
    {
        return $this->configurations()->where('is_favorite', true);
    }

    /**
     * Scope: Only active strategies.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get the instantiated strategy class.
     *
     * @return object
     *
     * @throws \Exception If class not found.
     */
    public function getInstance(array $hyperparameters = [])
    {
        $className = $this->class_name;
        if (! class_exists($className)) {
            throw new \Exception("Strategy class {$className} not found");
        }

        // Pass name and config (hyperparameters) to constructor
        $instance = new $className($this->name, $hyperparameters);

        return $instance;
    }
}
