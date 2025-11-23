<?php

namespace TradingPlatform\Domain\Strategy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class OptimizationResult
 *
 * Represents a single individual (parameter set) evaluated during optimization.
 * Tracks DNA encoding, fitness scores, genetic operations, and backtest results.
 *
 * **Individual Lifecycle:**
 * 1. Generate DNA (hyperparameter encoding)
 * 2. Decode to hyperparameters
 * 3. Run backtest to evaluate fitness
 * 4. Store fitness score and components
 * 5. Mark as elite if top performer
 * 6. Use for breeding next generation
 *
 * **Genetic Operations:**
 * - **Crossover**: Combine two parent DNAs
 * - **Mutation**: Random parameter changes
 * - **Elite**: Top performers preserved unchanged
 *
 * **Fitness Components:**
 * - sharpe_ratio: Risk-adjusted return
 * - total_return: Absolute return
 * - max_drawdown: Risk metric
 * - win_rate: Trade success rate
 * - profit_factor: Profitability metric
 *
 * **DNA Encoding:**
 * Compact string representation of hyperparameters for efficient
 * storage and genetic operations. Example: "14_30_70" for RSI(14, 30, 70)
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Creating optimization result
 * ```php
 * $result = OptimizationResult::create([
 *     'optimization_run_id' => 1,
 *     'generation' => 10,
 *     'individual_index' => 5,
 *     'dna' => '11_25_75',
 *     'hyperparameters' => ['period' => 11, 'oversold' => 25, 'overbought' => 75],
 *     'fitness_score' => 1.85,
 *     'fitness_components' => [
 *         'sharpe_ratio' => 1.75,
 *         'max_drawdown' => -8.5,
 *         'total_return' => 25.0,
 *     ],
 *     'is_elite' => true,
 *     'rank_in_generation' => 1,
 * ]);
 * ```
 */
class OptimizationResult extends Model
{
    public $timestamps = false; // Only created_at

    protected $fillable = [
        'optimization_run_id',
        'strategy_config_id',
        'generation',
        'individual_index',
        'dna',
        'hyperparameters',
        'fitness_score',
        'fitness_components',
        'backtest_result_id',
        'rank_in_generation',
        'is_elite',
        'parent_ids',
        'mutation_applied',
        'crossover_applied',
        'evaluated_at',
    ];

    protected $casts = [
        'hyperparameters' => 'array',
        'fitness_components' => 'array',
        'parent_ids' => 'array',
        'fitness_score' => 'float',
        'is_elite' => 'boolean',
        'mutation_applied' => 'boolean',
        'crossover_applied' => 'boolean',
        'evaluated_at' => 'datetime',
    ];

    /**
     * Get the optimization run
     */
    public function optimizationRun(): BelongsTo
    {
        return $this->belongsTo(OptimizationRun::class);
    }

    /**
     * Get the strategy configuration
     */
    public function strategyConfig(): BelongsTo
    {
        return $this->belongsTo(StrategyConfiguration::class, 'strategy_config_id');
    }

    /**
     * Get the backtest result
     */
    public function backtestResult(): BelongsTo
    {
        return $this->belongsTo(BacktestResult::class);
    }

    /**
     * Scope: Elite individuals
     */
    public function scopeElite($query)
    {
        return $query->where('is_elite', true);
    }

    /**
     * Scope: Specific generation
     */
    public function scopeGeneration($query, int $generation)
    {
        return $query->where('generation', $generation);
    }

    /**
     * Scope: Top performers
     */
    public function scopeTopPerformers($query, int $limit = 10)
    {
        return $query->orderBy('fitness_score', 'desc')->limit($limit);
    }

    /**
     * Get fitness component value
     */
    public function getFitnessComponent(string $component): ?float
    {
        return $this->fitness_components[$component] ?? null;
    }

    /**
     * Check if mutation was applied
     */
    public function wasMutated(): bool
    {
        return $this->mutation_applied;
    }

    /**
     * Check if crossover was applied
     */
    public function wasCrossed(): bool
    {
        return $this->crossover_applied;
    }
}
