<?php

namespace TradingPlatform\Domain\Strategy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class OptimizationRun
 *
 * Tracks a complete hyperparameter optimization run using genetic algorithms.
 * Manages generations, evaluations, fitness tracking, and best configuration selection.
 *
 * **Optimization Process:**
 * 1. Initialize population with random hyperparameters
 * 2. Evaluate fitness via backtesting
 * 3. Select elite individuals (best performers)
 * 4. Apply crossover and mutation to create new generation
 * 5. Repeat for N generations
 * 6. Return best configuration found
 *
 * **Algorithms Supported:**
 * - 'genetic': Standard genetic algorithm
 * - 'differential_evolution': DE optimization
 * - 'particle_swarm': PSO optimization
 * - 'grid_search': Exhaustive grid search
 * - 'random_search': Random sampling
 *
 * **Fitness Objectives:**
 * - Single: Maximize Sharpe ratio
 * - Multi: Maximize return + minimize drawdown
 * - Custom: Weighted combination of metrics
 *
 * **Status Values:**
 * - 'pending': Not started
 * - 'running': Currently executing
 * - 'completed': Successfully finished
 * - 'failed': Error occurred
 * - 'cancelled': Manually stopped
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Starting an optimization run
 * ```php
 * $run = OptimizationRun::create([
 *     'strategy_id' => 1,
 *     'name' => 'RSI Optimization Q1 2024',
 *     'algorithm' => 'genetic',
 *     'optimization_config' => [
 *         'population_size' => 50,
 *         'elite_size' => 5,
 *         'mutation_rate' => 0.1,
 *         'crossover_rate' => 0.8,
 *     ],
 *     'fitness_objectives' => ['sharpe_ratio' => 0.7, 'max_drawdown' => 0.3],
 *     'total_generations' => 50,
 *     'data_period_start' => '2024-01-01',
 *     'data_period_end' => '2024-03-31',
 * ]);
 *
 * $run->markAsStarted();
 * ```
 */
class OptimizationRun extends Model
{
    protected $fillable = [
        'strategy_id',
        'name',
        'status',
        'algorithm',
        'optimization_config',
        'fitness_objectives',
        'backtest_config',
        'data_source',
        'data_period_start',
        'data_period_end',
        'total_generations',
        'current_generation',
        'total_evaluations',
        'best_fitness',
        'best_config_id',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'optimization_config' => 'array',
        'fitness_objectives' => 'array',
        'backtest_config' => 'array',
        'data_period_start' => 'date',
        'data_period_end' => 'date',
        'best_fitness' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the strategy being optimized
     */
    public function strategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class);
    }

    /**
     * Get the best configuration found
     */
    public function bestConfig(): BelongsTo
    {
        return $this->belongsTo(StrategyConfiguration::class, 'best_config_id');
    }

    /**
     * Get all configurations created by this run
     */
    public function configurations(): HasMany
    {
        return $this->hasMany(StrategyConfiguration::class);
    }

    /**
     * Get all optimization results
     */
    public function results(): HasMany
    {
        return $this->hasMany(OptimizationResult::class);
    }

    /**
     * Get elite results
     */
    public function eliteResults(): HasMany
    {
        return $this->results()->where('is_elite', true);
    }

    /**
     * Get results for a specific generation
     */
    public function generationResults(int $generation): HasMany
    {
        return $this->results()->where('generation', $generation);
    }

    /**
     * Scope: Only completed runs
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Only running
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope: Failed runs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Check if run is  complete
     */
    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if run is still running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(): float
    {
        if ($this->total_generations == 0) {
            return 0.0;
        }

        return ($this->current_generation / $this->total_generations) * 100;
    }

    /**
     * Get duration
     */
    public function getDurationSeconds(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();

        return $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Mark as started
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(?int $bestConfigId = null, ?float $bestFitness = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'best_config_id' => $bestConfigId,
            'best_fitness' => $bestFitness,
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Update progress
     */
    public function updateProgress(int $generation, int $evaluations): void
    {
        $this->update([
            'current_generation' => $generation,
            'total_evaluations' => $evaluations,
        ]);
    }
}
