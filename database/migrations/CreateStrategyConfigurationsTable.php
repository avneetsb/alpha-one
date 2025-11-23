<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Strategy Configurations Migration
 *
 * Creates the `strategy_configurations` table to store specific parameter sets
 * for strategies. This allows the same strategy logic (e.g., "RSI") to be
 * instantiated multiple times with different settings (e.g., "Aggressive RSI", "Conservative RSI").
 *
 * Key Features:
 * - Lineage Tracking: Tracks parent-child relationships for genetic algorithms.
 * - DNA Storage: Stores encoded parameter strings for optimization.
 * - Source Attribution: Distinguishes between manual, optimized, and default configs.
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
     * Creates the `strategy_configurations` table with columns for:
     * - Linkage (strategy_id, optimization_run_id, parent_config_id)
     * - Identity (name, notes)
     * - Configuration (hyperparameters, dna)
     * - Metadata (source, is_favorite)
     */
    public function up(): void
    {
        Capsule::schema()->create('strategy_configurations', function (Blueprint $table) {
            $table->id();

            // Parent strategy
            $table->foreignId('strategy_id')->constrained()->onDelete('cascade');

            // User-friendly name (e.g., "Golden Cross Setup")
            $table->string('name');

            // Actual parameter values (e.g., {"fast_period": 50, "slow_period": 200})
            $table->json('hyperparameters');

            // DNA string for genetic operations
            $table->string('dna', 500)->nullable();

            // Origin of this configuration
            $table->enum('source', ['manual', 'optimization', 'default'])->default('manual');

            // Linkage for optimization results
            $table->foreignId('optimization_run_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('parent_config_id')->nullable()->constrained('strategy_configurations')->onDelete('set null');

            // User preference
            $table->boolean('is_favorite')->default(false);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Allow recovery of deleted configs

            // Indexes
            $table->index('strategy_id');
            $table->index('source');
            $table->index('optimization_run_id');
            $table->index('is_favorite');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the `strategy_configurations` table.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('strategy_configurations');
    }
};
