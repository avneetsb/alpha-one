<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Reconciliation Runs Migration
 *
 * Creates the `reconciliation_runs` table to track the execution of reconciliation
 * processes. Reconciliation is the process of verifying that the platform's internal
 * state matches the broker's state.
 *
 * This table acts as a log of these verification sessions, recording:
 * 1. When the check was run.
 * 2. What was checked (Orders, Positions, Holdings).
 * 3. The high-level outcome (Success, Failure, Mismatches Found).
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
     * Creates the `reconciliation_runs` table with columns for:
     * - Context (broker_id, scope)
     * - Timing (started_at, completed_at)
     * - Status (running, completed, failed)
     * - Statistics (items_processed, mismatches_found, mismatches_resolved)
     * - Metadata (JSON blob for filter criteria)
     */
    public function up(): void
    {
        Capsule::schema()->create('reconciliation_runs', function (Blueprint $table) {
            $table->id();

            // Target broker
            $table->string('broker_id', 50);

            // What are we reconciling?
            $table->enum('scope', ['orders', 'positions', 'holdings', 'all']);

            // Execution timing
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();

            // Run status
            $table->enum('status', ['running', 'completed', 'failed', 'completed_with_errors']);

            // Summary statistics
            $table->integer('items_processed')->default(0);
            $table->integer('mismatches_found')->default(0);
            $table->integer('mismatches_resolved')->default(0);

            // Additional context (e.g., {"date_range": "2024-01-01"})
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes for history and monitoring
            $table->index(['broker_id', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the `reconciliation_runs` table.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('reconciliation_runs');
    }
};
