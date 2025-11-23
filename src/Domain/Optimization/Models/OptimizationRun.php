<?php

namespace TradingPlatform\Domain\Optimization\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OptimizationRun
 *
 * Represents a single execution of a strategy optimization process.
 *
 *
 * @property int $id
 * @property int $strategy_id
 * @property string $name
 * @property string $status
 * @property string $algorithm
 * @property array $optimization_config
 * @property array $fitness_objectives
 * @property array $backtest_config
 * @property string $data_source
 * @property \DateTime $data_period_start
 * @property \DateTime $data_period_end
 * @property int $total_generations
 * @property int $current_generation
 * @property int $total_evaluations
 * @property float $best_fitness
 * @property int $best_config_id
 * @property \DateTime $started_at
 * @property \DateTime $completed_at
 * @property string $error_message
 */
class OptimizationRun extends Model
{
    protected $table = 'optimization_runs';

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
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the strategy associated with this run.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function strategy()
    {
        return $this->belongsTo(\TradingPlatform\Domain\Strategy\Models\Strategy::class);
    }

    /**
     * Get the best configuration found in this run.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bestConfig()
    {
        return $this->belongsTo(\TradingPlatform\Domain\Strategy\Models\StrategyConfiguration::class, 'best_config_id');
    }
}
