<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Strategy Configurations Table
 *
 * Stores named hyperparameter sets for strategies with lineage (parent),
 * source (manual/optimization), DNA representation, and soft deletes.
 *
 * @example Favorite configs for a strategy:
 * // DB::table('strategy_configurations')->where('strategy_id',$id)->where('is_favorite',true)->get();
 *
 * @accepted_values
 * - source: 'manual', 'optimization', 'default'
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Capsule::schema()->create('strategy_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained()->onDelete('cascade');
            $table->string('name'); // User-friendly name
            $table->json('hyperparameters'); // Actual parameter values
            $table->string('dna', 500)->nullable(); // DNA string representation
            $table->enum('source', ['manual', 'optimization', 'default'])->default('manual');
            $table->foreignId('optimization_run_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('parent_config_id')->nullable()->constrained('strategy_configurations')->onDelete('set null');
            $table->boolean('is_favorite')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('strategy_id');
            $table->index('source');
            $table->index('optimization_run_id');
            $table->index('is_favorite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('strategy_configurations');
    }
};
