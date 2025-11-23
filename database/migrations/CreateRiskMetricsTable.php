<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Risk Metrics Migration
 *
 * Creates the `risk_metrics` table to store calculated risk indicators.
 * Unlike `risk_limits` which defines constraints, this table records the
 * actual measured risk values over time.
 *
 * Used for:
 * 1. Historical risk reporting.
 * 2. Trend analysis (e.g., is VaR increasing?).
 * 3. Triggering alerts when thresholds are breached.
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
     * Creates the `risk_metrics` table with columns for:
     * - Scope (portfolio_id, strategy_id)
     * - Metric Type (var, cvar, sharpe, etc.)
     * - Measured Values (metric_value, threshold)
     * - Status (normal, warning, breach)
     * - Metadata (JSON blob for calculation context)
     */
    public function up(): void
    {
        Capsule::schema()->create('risk_metrics', function (Blueprint $table) {
            $table->id();

            // Scope
            $table->string('portfolio_id', 100);
            $table->string('strategy_id', 100)->nullable();

            // Metric definition
            $table->enum('metric_type', ['var', 'cvar', 'sharpe', 'sortino', 'max_drawdown', 'correlation']);

            // Values
            $table->decimal('metric_value', 15, 4);
            $table->decimal('threshold', 15, 4)->nullable(); // The limit against which this was checked

            // Evaluation
            $table->enum('status', ['normal', 'warning', 'breach'])->default('normal');

            // Context (e.g., {"confidence_level": 0.95, "time_horizon": "1d"})
            $table->json('metadata')->nullable();

            // Timestamp of calculation
            $table->timestamp('calculated_at');
            $table->timestamps();

            // Indexes for reporting
            $table->index(['portfolio_id', 'metric_type']);
            $table->index('calculated_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the `risk_metrics` table.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('risk_metrics');
    }
};
