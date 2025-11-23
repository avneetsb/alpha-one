<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Optimization Runs Migration
 *
 * Creates the `optimization_runs` table to track the execution of strategy
 * optimization sessions (e.g., Genetic Algorithms, Grid Search).
 *
 * This table acts as the "header" for a batch of simulations, storing:
 * 1. The configuration of the optimizer itself (population size, mutation rate).
 * 2. The objective function (what we are trying to maximize/minimize).
 * 3. The overall progress and status of the run.
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
     * Creates the `optimization_runs` table with columns for:
     * - Identification (strategy_id, name)
     * - Status tracking (pending, running, completed)
     * - Configuration (algorithm, optimization_config, fitness_objectives)
     * - Data context (data_source, period)
     * - Progress metrics (generations, evaluations)
     * - Results (best_fitness, best_config_id)
     */
    public function up(): void
    {
        Capsule::schema()->create('optimization_runs', function (Blueprint $table) {
            $table->id();

            // Strategy being optimized
            $table->foreignId('strategy_id')->constrained()->onDelete('cascade');

            // Human-readable name for the run
            $table->string('name');

            // Execution status
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled'])->default('pending');

            // Optimization method
            $table->string('algorithm', 50)->default('genetic'); // genetic, grid_search, random_search

            // Optimizer settings (e.g., {"population_size": 100, "mutation_rate": 0.05})
            $table->json('optimization_config');

            // Goals (e.g., {"maximize": "sharpe_ratio", "minimize": "drawdown"})
            $table->json('fitness_objectives');

            // Backtest environment settings
            $table->json('backtest_config'); // period, slippage, commission, etc.

            // Data used for optimization
            $table->string('data_source'); // Symbol/instrument
            $table->date('data_period_start');
            $table->date('data_period_end');

            // Progress tracking
            $table->integer('total_generations');
            $table->integer('current_generation')->default(0);
            $table->integer('total_evaluations')->default(0);

            // Outcome
            $table->decimal('best_fitness', 15, 6)->nullable();
            $table->foreignId('best_config_id')->nullable()->constrained('strategy_configurations')->onDelete('set null');

            // Timestamps and diagnostics
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('strategy_id');
            $table->index('status');
            $table->index(['data_period_start', 'data_period_end']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the `optimization_runs` table.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('optimization_runs');
    }
};
