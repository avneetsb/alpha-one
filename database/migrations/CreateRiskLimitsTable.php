<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Risk Limits Migration
 *
 * Creates the `risk_limits` table to define and track risk constraints.
 * These limits are checked before every order placement to ensure compliance
 * with the risk management policy.
 *
 * Supported Limit Types:
 * - Position Size: Max quantity per instrument.
 * - Notional: Max value of open positions.
 * - Drawdown: Max allowable loss for the day.
 * - VaR: Value at Risk limit.
 * - Concentration: Max exposure to a single sector/instrument.
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
     * Creates the `risk_limits` table with columns for:
     * - Scope (portfolio_id, strategy_id, instrument_id)
     * - Limit Definition (limit_type, limit_value)
     * - Current State (current_value, utilization_percentage)
     * - Status (is_active)
     */
    public function up(): void
    {
        Capsule::schema()->create('risk_limits', function (Blueprint $table) {
            $table->id();

            // Scope of the limit
            $table->string('portfolio_id', 100);
            $table->string('strategy_id', 100)->nullable(); // Null means portfolio-wide limit
            $table->foreignId('instrument_id')->nullable()->constrained()->onDelete('cascade'); // Null means global limit

            // Limit definition
            $table->enum('limit_type', ['position_size', 'notional', 'drawdown', 'var', 'concentration']);
            $table->decimal('limit_value', 15, 2);

            // Real-time tracking
            $table->decimal('current_value', 15, 2)->default(0);
            $table->decimal('utilization_percentage', 5, 2)->default(0);

            // Control flag
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes for fast pre-trade checks
            $table->index(['portfolio_id', 'limit_type']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the `risk_limits` table.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('risk_limits');
    }
};
