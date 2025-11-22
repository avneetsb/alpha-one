<?php

namespace TradingPlatform\Domain\Optimization\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function strategy()
    {
        return $this->belongsTo(\TradingPlatform\Domain\Strategy\Models\Strategy::class);
    }

    public function bestConfig()
    {
        return $this->belongsTo(\TradingPlatform\Domain\Strategy\Models\StrategyConfiguration::class, 'best_config_id');
    }
}
