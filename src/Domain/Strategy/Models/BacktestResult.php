<?php

namespace TradingPlatform\Domain\Strategy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class BacktestResult
 *
 * Stores comprehensive backtest performance metrics for strategy configurations.
 * Tracks profitability, risk-adjusted returns, trade statistics, and equity curves
 * to enable data-driven strategy selection and optimization.
 *
 * **Performance Metrics:**
 * - Returns: Total return, total profit, final capital
 * - Risk: Sharpe ratio, Sortino ratio, max drawdown
 * - Trade Stats: Win rate, profit factor, avg win/loss
 * - Costs: Commission paid, slippage cost
 * - Curves: Equity curve, monthly returns
 *
 * **Use Cases:**
 * - Strategy validation before live deployment
 * - Hyperparameter optimization (finding best parameters)
 * - Walk-forward analysis (out-of-sample testing)
 * - Strategy comparison and ranking
 * - Performance reporting and visualization
 *
 * **Key Metrics Explained:**
 * - **Sharpe Ratio**: Risk-adjusted return (>1.0 good, >2.0 excellent)
 * - **Sortino Ratio**: Like Sharpe but only penalizes downside volatility
 * - **Max Drawdown**: Largest peak-to-trough decline (%)
 * - **Profit Factor**: Gross profit / Gross loss (>1.5 good)
 * - **Win Rate**: % of winning trades (>50% for trend, >40% for mean-reversion)
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Storing backtest results
 * ```php
 * $result = BacktestResult::create([
 *     'strategy_config_id' => 1,
 *     'symbol' => 'RELIANCE',
 *     'timeframe' => '15m',
 *     'period_start' => '2023-01-01',
 *     'period_end' => '2023-12-31',
 *     'initial_capital' => 100000,
 *     'final_capital' => 125000,
 *     'total_return' => 25.0,  // 25%
 *     'total_trades' => 150,
 *     'winning_trades' => 85,
 *     'losing_trades' => 65,
 *     'win_rate' => 56.67,
 *     'profit_factor' => 1.85,
 *     'sharpe_ratio' => 1.75,
 *     'max_drawdown' => -8.5,
 *     'equity_curve' => [...],  // Array of equity values
 * ]);
 * ```
 * @example Finding best strategies
 * ```php
 * $topStrategies = BacktestResult::profitable()
 *     ->where('sharpe_ratio', '>', 1.5)
 *     ->where('max_drawdown', '>', -15)
 *     ->bestSharpe(10)
 *     ->get();
 * ```
 */
class BacktestResult extends Model
{
    protected $fillable = [
        'strategy_config_id',
        'optimization_result_id',
        'symbol',
        'timeframe',
        'period_start',
        'period_end',
        'initial_capital',
        'final_capital',
        'total_return',
        'total_profit',
        'total_trades',
        'winning_trades',
        'losing_trades',
        'win_rate',
        'profit_factor',
        'sharpe_ratio',
        'sortino_ratio',
        'max_drawdown',
        'max_drawdown_duration_days',
        'avg_win',
        'avg_loss',
        'largest_win',
        'largest_loss',
        'avg_trade_duration_minutes',
        'commission_paid',
        'slippage_cost',
        'equity_curve',
        'monthly_returns',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'initial_capital' => 'float',
        'final_capital' => 'float',
        'total_return' => 'float',
        'total_profit' => 'float',
        'win_rate' => 'float',
        'profit_factor' => 'float',
        'sharpe_ratio' => 'float',
        'sortino_ratio' => 'float',
        'max_drawdown' => 'float',
        'avg_win' => 'float',
        'avg_loss' => 'float',
        'largest_win' => 'float',
        'largest_loss' => 'float',
        'commission_paid' => 'float',
        'slippage_cost' => 'float',
        'equity_curve' => 'array',
        'monthly_returns' => 'array',
    ];

    /**
     * Get the strategy configuration
     */
    public function strategyConfig(): BelongsTo
    {
        return $this->belongsTo(StrategyConfiguration::class, 'strategy_config_id');
    }

    /**
     * Get the optimization result
     */
    public function optimizationResult(): BelongsTo
    {
        return $this->belongsTo(OptimizationResult::class);
    }

    /**
     * Scope: By symbol
     */
    public function scopeSymbol($query, string $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    /**
     * Scope: Best Sharpe ratio
     */
    public function scopeBestSharpe($query, int $limit = 10)
    {
        return $query->orderBy('sharpe_ratio', 'desc')->limit($limit);
    }

    /**
     * Scope: Best returns
     */
    public function scopeBestReturns($query, int $limit = 10)
    {
        return $query->orderBy('total_return', 'desc')->limit($limit);
    }

    /**
     * Scope: Low drawdown
     */
    public function scopeLowDrawdown($query, float $maxDrawdown = 10.0)
    {
        return $query->where('max_drawdown', '<=', $maxDrawdown);
    }

    /**
     * Scope: Profitable
     */
    public function scopeProfitable($query)
    {
        return $query->where('total_return', '>', 0);
    }

    /**
     * Get net profit after costs
     */
    public function getNetProfit(): float
    {
        return $this->total_profit - $this->commission_paid - $this->slippage_cost;
    }

    /**
     * Get risk-adjusted return
     */
    public function getRiskAdjustedReturn(): ?float
    {
        if ($this->max_drawdown == 0) {
            return null;
        }

        return $this->total_return / abs($this->max_drawdown);
    }

    /**
     * Get expectancy
     */
    public function getExpectancy(): ?float
    {
        if ($this->total_trades == 0) {
            return null;
        }

        $avgWin = $this->avg_win ?? 0;
        $avgLoss = abs($this->avg_loss ?? 0);
        $winRate = $this->win_rate / 100;
        $lossRate = 1 - $winRate;

        return ($winRate * $avgWin) - ($lossRate * $avgLoss);
    }

    /**
     * Check if profitable
     */
    public function isProfitable(): bool
    {
        return $this->total_return > 0;
    }

    /**
     * Get equity curve points count
     */
    public function getEquityCurvePointsCount(): int
    {
        return count($this->equity_curve ?? []);
    }
}
