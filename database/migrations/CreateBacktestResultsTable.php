<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Backtest Results Migration
 *
 * Creates the `backtest_results` table, which serves as the persistent storage
 * for strategy simulation outcomes. This table captures a comprehensive snapshot
 * of a strategy's performance over a specific historical period.
 *
 * Key features:
 * - Links to specific strategy configurations for reproducibility.
 * - Stores high-level metrics (Sharpe, Drawdown, Win Rate).
 * - Persists detailed equity curves and monthly return heatmaps as JSON.
 * - Supports linkage to optimization runs for genetic algorithm analysis.
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the `backtest_results` table with columns for:
     * - Configuration linkage (strategy_config_id)
     * - Execution context (symbol, timeframe, period)
     * - Financial metrics (capital, returns, profit factors)
     * - Risk metrics (Sharpe, Sortino, Max Drawdown)
     * - Trade statistics (win rate, avg win/loss)
     * - Cost analysis (commissions, slippage)
     * - Time-series data (equity curve)
     */
    public function up(): void
    {
        Capsule::schema()->create('backtest_results', function (Blueprint $table) {
            $table->id();

            // Link to the specific parameter set used for this backtest
            $table->foreignId('strategy_config_id')->constrained('strategy_configurations')->onDelete('cascade');

            // Optional link to an optimization run if this was part of a batch process
            $table->foreignId('optimization_result_id')->nullable()->constrained()->onDelete('set null');

            // Trading context
            $table->string('symbol', 50);
            $table->string('timeframe', 20);
            $table->date('period_start');
            $table->date('period_end');

            // Capital metrics
            $table->decimal('initial_capital', 15, 2);
            $table->decimal('final_capital', 15, 2);
            $table->decimal('total_return', 10, 4); // Percentage return (e.g., 15.50 for 15.5%)
            $table->decimal('total_profit', 15, 2); // Absolute profit value

            // Trade statistics
            $table->integer('total_trades');
            $table->integer('winning_trades');
            $table->integer('losing_trades');
            $table->decimal('win_rate', 5, 2); // Percentage (0-100)

            // Performance ratios
            $table->decimal('profit_factor', 10, 4)->nullable(); // Gross Profit / Gross Loss
            $table->decimal('sharpe_ratio', 10, 4)->nullable(); // Risk-adjusted return
            $table->decimal('sortino_ratio', 10, 4)->nullable(); // Downside risk-adjusted return

            // Drawdown analysis
            $table->decimal('max_drawdown', 10, 4); // Maximum percentage drop from peak
            $table->integer('max_drawdown_duration_days')->nullable(); // Longest recovery period

            // Trade averages
            $table->decimal('avg_win', 15, 2)->nullable();
            $table->decimal('avg_loss', 15, 2)->nullable();
            $table->decimal('largest_win', 15, 2)->nullable();
            $table->decimal('largest_loss', 15, 2)->nullable();
            $table->integer('avg_trade_duration_minutes')->nullable();

            // Transaction costs
            $table->decimal('commission_paid', 15, 2)->default(0);
            $table->decimal('slippage_cost', 15, 2)->default(0);

            // Serialized data for charting
            $table->json('equity_curve'); // Array of [timestamp, equity] points
            $table->json('monthly_returns')->nullable(); // Array of monthly performance

            $table->timestamps();

            // Indexes for common queries
            $table->index('strategy_config_id');
            $table->index('symbol');
            $table->index(['period_start', 'period_end']);
            $table->index(['sharpe_ratio' => 'desc']); // For finding best strategies
            $table->index(['total_return' => 'desc']); // For finding most profitable strategies
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the `backtest_results` table.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('backtest_results');
    }
};
