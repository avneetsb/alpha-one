<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Risk Metrics Table
 *
 * Records calculated risk metrics (VaR, CVaR, Sharpe, drawdown, etc.)
 * with thresholds and status for monitoring and alerts.
 *
 * @accepted_values
 * - metric_type: 'var', 'cvar', 'sharpe', 'sortino', 'max_drawdown', 'correlation'
 * - status: 'normal', 'warning', 'breach'
 */
return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('risk_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('portfolio_id', 100);
            $table->string('strategy_id', 100)->nullable();
            $table->enum('metric_type', ['var', 'cvar', 'sharpe', 'sortino', 'max_drawdown', 'correlation']);
            $table->decimal('metric_value', 15, 4);
            $table->decimal('threshold', 15, 4)->nullable();
            $table->enum('status', ['normal', 'warning', 'breach'])->default('normal');
            $table->json('metadata')->nullable(); // Additional context (confidence level, timeframe, etc.)
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            $table->index(['portfolio_id', 'metric_type']);
            $table->index('calculated_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('risk_metrics');
    }
};
