<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Reconciliation Items Migration
 *
 * Creates the `reconciliation_items` table to store individual discrepancies found
 * during a reconciliation run. Each row represents a specific mismatch (e.g., an
 * order missing in the system but present in the broker's report).
 *
 * This table is the "detail" view for the `reconciliation_runs` "header".
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
     * Creates the `reconciliation_items` table with columns for:
     * - Linkage (reconciliation_run_id)
     * - Item Identification (item_type, item_id, broker_ref_id)
     * - Data Comparison (system_data, broker_data, discrepancy_details)
     * - Resolution Status (status, resolution_notes)
     */
    public function up(): void
    {
        Capsule::schema()->create('reconciliation_items', function (Blueprint $table) {
            $table->id();

            // Parent run
            $table->foreignId('reconciliation_run_id')->constrained()->onDelete('cascade');

            // Item context
            $table->string('item_type', 50); // Order, Position, Holding
            $table->string('item_id', 100); // System ID (e.g., local order ID)
            $table->string('broker_ref_id', 100)->nullable(); // Broker ID (e.g., exchange order ID)

            // Snapshot of data at time of mismatch
            $table->json('system_data')->nullable();
            $table->json('broker_data')->nullable();

            // Specifics of what didn't match (e.g., {"price": {"system": 100, "broker": 101}})
            $table->json('discrepancy_details')->nullable();

            // Workflow status
            $table->enum('status', ['mismatch', 'resolved', 'ignored', 'manual_intervention']);
            $table->text('resolution_notes')->nullable();

            $table->timestamps();

            // Indexes for workflow management
            $table->index(['reconciliation_run_id', 'status']);
            $table->index(['item_type', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the `reconciliation_items` table.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('reconciliation_items');
    }
};
