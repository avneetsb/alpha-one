<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Backtest Results Table
 *
 * Stores performance metrics and artifacts from strategy backtests, including
 * capital changes, returns, risk ratios, trade statistics, and equity curves.
 * Links to `strategy_configurations` and optional `optimization_results`.
 *
 * @example Query top Sharpe backtests for a symbol:
 * // DB::table('backtest_results')->where('symbol','RELIANCE')->orderByDesc('sharpe_ratio')->limit(10)->get();
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Capsule::schema()->create('backtest_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_config_id')->constrained('strategy_configurations')->onDelete('cascade');
            $table->foreignId('optimization_result_id')->nullable()->constrained()->onDelete('set null');
            $table->string('symbol', 50);
            $table->string('timeframe', 20);
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('initial_capital', 15, 2);
            $table->decimal('final_capital', 15, 2);
            $table->decimal('total_return', 10, 4);
            $table->decimal('total_profit', 15, 2);
            $table->integer('total_trades');
            $table->integer('winning_trades');
            $table->integer('losing_trades');
            $table->decimal('win_rate', 5, 2);
            $table->decimal('profit_factor', 10, 4)->nullable();
            $table->decimal('sharpe_ratio', 10, 4)->nullable();
            $table->decimal('sortino_ratio', 10, 4)->nullable();
            $table->decimal('max_drawdown', 10, 4);
            $table->integer('max_drawdown_duration_days')->nullable();
            $table->decimal('avg_win', 15, 2)->nullable();
            $table->decimal('avg_loss', 15, 2)->nullable();
            $table->decimal('largest_win', 15, 2)->nullable();
            $table->decimal('largest_loss', 15, 2)->nullable();
            $table->integer('avg_trade_duration_minutes')->nullable();
            $table->decimal('commission_paid', 15, 2)->default(0);
            $table->decimal('slippage_cost', 15, 2)->default(0);
            $table->json('equity_curve');
            $table->json('monthly_returns')->nullable();
            $table->timestamps();
            
            $table->index('strategy_config_id');
            $table->index('symbol');
            $table->index(['period_start', 'period_end']);
            $table->index(['sharpe_ratio' => 'desc']);
            $table->index(['total_return' => 'desc']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('backtest_results');
    }
};
