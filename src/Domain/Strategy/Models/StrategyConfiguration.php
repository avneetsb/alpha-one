<?php

namespace TradingPlatform\Domain\Strategy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StrategyConfiguration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'strategy_id',
        'name',
        'hyperparameters',
        'dna',
        'source',
        'optimization_run_id',
        'parent_config_id',
        'is_favorite',
        'notes',
    ];

    protected $casts = [
        'hyperparameters' => 'array',
        'is_favorite' => 'boolean',
    ];

    /**
     * Get the strategy this configuration belongs to
     */
    public function strategy(): BelongsTo
    {
        return $this->belongsTo(Strategy::class);
    }

    /**
     * Get the optimization run that created this configuration
     */
    public function optimizationRun(): BelongsTo
    {
        return $this->belongsTo(OptimizationRun::class);
    }

    /**
     * Get the parent configuration (if this is a variation)
     */
    public function parentConfig(): BelongsTo
    {
        return $this->belongsTo(StrategyConfiguration::class, 'parent_config_id');
    }

    /**
     * Get child configurations (variations)
     */
    public function childConfigs(): HasMany
    {
        return $this->hasMany(StrategyConfiguration::class, 'parent_config_id');
    }

    /**
     * Get backtest results for this configuration
     */
    public function backtestResults(): HasMany
    {
        return $this->hasMany(BacktestResult::class);
    }

    /**
     * Get optimization results for this configuration
     */
    public function optimizationResults(): HasMany
    {
        return $this->hasMany(OptimizationResult::class);
    }

    /**
     * Scope: Only favorites
     */
    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * Scope: Filter by source
     */
    public function scopeSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope: From optimization
     */
    public function scopeFromOptimization($query)
    {
        return $query->where('source', 'optimization');
    }

    /**
     * Get hyperparameter value
     */
    public function getHyperparameter(string $key, $default = null)
    {
        return $this->hyperparameters[$key] ?? $default;
    }

    /**
     * Set hyperparameter value
     */
    public function setHyperparameter(string $key, $value): void
    {
        $params = $this->hyperparameters;
        $params[$key] = $value;
        $this->hyperparameters = $params;
    }

    /**
     * Get best backtest result
     */
    public function getBestBacktestResult()
    {
        return $this->backtestResults()
            ->orderBy('sharpe_ratio', 'desc')
            ->first();
    }
}
