<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Optimization Results Migration
 *
 * Creates the `optimization_results` table to store the granular outcomes of
 * each individual strategy evaluation within an optimization run.
 *
 * In a genetic algorithm context, this table represents the "population".
 * Each row corresponds to one "individual" (a specific set of parameters)
 * and its fitness score.
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
     * Creates the `optimization_results` table with columns for:
     * - Linkage (run_id, strategy_config_id)
     * - Genetic Info (generation, individual_index, dna)
     * - Performance (fitness_score, fitness_components)
     * - Evolution metadata (is_elite, mutation_applied, crossover_applied)
     */
    public function up(): void
    {
        Capsule::schema()->create('optimization_results', function (Blueprint $table) {
            $table->id();

            // Parent optimization run
            $table->foreignId('optimization_run_id')->constrained()->onDelete('cascade');

            // The specific parameter configuration tested
            $table->foreignId('strategy_config_id')->constrained('strategy_configurations')->onDelete('cascade');

            // Genetic Algorithm context
            $table->integer('generation'); // Generation number (0 = initial population)
            $table->integer('individual_index'); // ID within the generation
            $table->string('dna', 500); // Encoded parameter string

            // Actual parameters used (redundant but useful for quick analysis)
            $table->json('hyperparameters');

            // Fitness evaluation
            $table->decimal('fitness_score', 15, 6); // Single metric for ranking
            $table->json('fitness_components'); // Detailed breakdown (e.g., {"sharpe": 1.5, "return": 20})

            // Link to full backtest details (optional to save space)
            $table->foreignId('backtest_result_id')->nullable()->constrained()->onDelete('set null');

            // Ranking within the generation
            $table->integer('rank_in_generation')->nullable();

            // Evolution flags
            $table->boolean('is_elite')->default(false); // Survived to next generation unchanged?
            $table->json('parent_ids')->nullable(); // IDs of parents if crossover occurred
            $table->boolean('mutation_applied')->default(false);
            $table->boolean('crossover_applied')->default(false);

            // Timestamps
            $table->timestamp('evaluated_at');
            $table->timestamp('created_at')->useCurrent();

            // Indexes for analysis
            $table->index('optimization_run_id');
            $table->index('strategy_config_id');
            $table->index('generation');
            $table->index(['fitness_score' => 'desc']); // Fast retrieval of best performers
            $table->index('is_elite');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the `optimization_results` table.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('optimization_results');
    }
};
