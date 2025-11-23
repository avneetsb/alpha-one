<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Margin Requirements Migration
 *
 * Creates the `margin_requirements` table to store the margin rules for each
 * instrument. This is essential for pre-trade risk validation.
 *
 * Supports different margin types:
 * - SPAN: Standard Portfolio Analysis of Risk (Futures/Options).
 * - Exposure: Additional margin charged by brokers.
 * - Option Premium: Full premium required for buying options.
 * - Delivery: Margin required for equity delivery trades.
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
     * Creates the `margin_requirements` table with columns for:
     * - Context (broker_id, instrument_id)
     * - Margin Type (span, exposure, etc.)
     * - Values (percentage or fixed amount)
     * - Complex parameters (JSON blob for multi-leg rules)
     * - Validity period (effective_from, effective_to)
     */
    public function up(): void
    {
        Capsule::schema()->create('margin_requirements', function (Blueprint $table) {
            $table->id();

            // Context
            $table->string('broker_id', 50);
            $table->foreignId('instrument_id')->constrained()->onDelete('cascade');

            // Margin Definition
            $table->enum('margin_type', ['span', 'exposure', 'option_premium', 'delivery']);
            $table->decimal('margin_percentage', 5, 2)->nullable(); // e.g., 20.00%
            $table->decimal('span_margin_amount', 15, 2)->nullable(); // Fixed amount per lot
            $table->json('margin_parameters')->nullable(); // Additional params (e.g., volatility)

            // Validity Period
            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->timestamps();

            // Indexes for fast lookup during pre-trade checks
            $table->index(['broker_id', 'instrument_id', 'margin_type']);
            $table->index('effective_from');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the `margin_requirements` table.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('margin_requirements');
    }
};
