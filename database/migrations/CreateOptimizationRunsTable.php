<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Optimization Runs Table
 *
 * Tracks optimization executions for strategies, including algorithm,
 * configuration, progress, best results, and lifecycle timestamps.
 *
 * @example List completed runs for a strategy:
 * // DB::table('optimization_runs')->where('strategy_id',$id)->where('status','completed')->orderByDesc('completed_at')->get();
 *
 * @accepted_values
 * - status: 'pending', 'running', 'completed', 'failed', 'cancelled'
 * - algorithm: examples 'genetic', 'grid_search', 'random_search'
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Capsule::schema()->create('optimization_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('algorithm', 50)->default('genetic'); // genetic, grid_search, random_search
            $table->json('optimization_config'); // population_size, generations, mutation_rate, etc.
            $table->json('fitness_objectives'); // ['total_profit', 'sharpe_ratio', 'max_drawdown']
            $table->json('backtest_config'); // period, slippage, commission, etc.
            $table->string('data_source'); // Symbol/instrument
            $table->date('data_period_start');
            $table->date('data_period_end');
            $table->integer('total_generations');
            $table->integer('current_generation')->default(0);
            $table->integer('total_evaluations')->default(0);
            $table->decimal('best_fitness', 15, 6)->nullable();
            $table->foreignId('best_config_id')->nullable()->constrained('strategy_configurations')->onDelete('set null');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index('strategy_id');
            $table->index('status');
            $table->index(['data_period_start', 'data_period_end']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('optimization_runs');
    }
};
