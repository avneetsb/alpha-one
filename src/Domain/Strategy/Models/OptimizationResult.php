<?php

namespace TradingPlatform\Domain\Strategy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
