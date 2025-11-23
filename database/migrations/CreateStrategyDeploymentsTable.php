<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration: Create strategy_deployments table
 *
 * Tracks deployed strategies and their execution history.
 */
class CreateStrategyDeploymentsTable
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Capsule::schema()->create('strategy_deployments', function ($table) {
            $table->id();
            $table->string('deployment_id')->unique();
            $table->string('strategy_name');
            $table->enum('mode', ['paper', 'sandbox', 'live']);
            $table->enum('status', ['deployed', 'running', 'stopped', 'archived'])->default('deployed');
            $table->json('config')->nullable();
            $table->json('performance_data')->nullable();
            $table->timestamp('deployed_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamps();

            $table->index('deployment_id');
            $table->index('strategy_name');
            $table->index('mode');
            $table->index('status');
        });

        echo "Created strategy_deployments table\n";
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('strategy_deployments');
        echo "Dropped strategy_deployments table\n";
    }
}
