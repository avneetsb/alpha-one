<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Optimization Results Table
 *
 * Stores per-individual evaluation results across generations, including
 * hyperparameters, fitness, backtest linkage, and genetic operators metadata.
 *
 * @example Top fitness in a run:
 * // DB::table('optimization_results')->where('optimization_run_id',$runId)->orderByDesc('fitness_score')->limit(5)->get();
 *
 * @accepted_values
 * - is_elite: true/false
 * - mutation_applied: true/false
 * - crossover_applied: true/false
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Capsule::schema()->create('optimization_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('optimization_run_id')->constrained()->onDelete('cascade');
            $table->foreignId('strategy_config_id')->constrained('strategy_configurations')->onDelete('cascade');
            $table->integer('generation');
            $table->integer('individual_index');
            $table->string('dna', 500);
            $table->json('hyperparameters');
            $table->decimal('fitness_score', 15, 6);
            $table->json('fitness_components'); // Individual objective scores
            $table->foreignId('backtest_result_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('rank_in_generation')->nullable();
            $table->boolean('is_elite')->default(false);
            $table->json('parent_ids')->nullable(); // Array of parent result IDs
            $table->boolean('mutation_applied')->default(false);
            $table->boolean('crossover_applied')->default(false);
            $table->timestamp('evaluated_at');
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('optimization_run_id');
            $table->index('strategy_config_id');
            $table->index('generation');
            $table->index(['fitness_score' => 'desc']);
            $table->index('is_elite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('optimization_results');
    }
};
