<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Reconciliation Items Table
 *
 * Captures item-level mismatches between system records and broker data
 * during reconciliation runs, with resolution notes and statuses.
 *
 * @example List unresolved mismatches:
 * // DB::table('reconciliation_items')->where('status','mismatch')->get();
 *
 * @accepted_values
 * - item_type: 'Order', 'Position', 'Holding'
 * - status: 'mismatch', 'resolved', 'ignored', 'manual_intervention'
 */
return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_run_id')->constrained()->onDelete('cascade');
            $table->string('item_type', 50); // Order, Position, Holding
            $table->string('item_id', 100); // System ID
            $table->string('broker_ref_id', 100)->nullable(); // Broker ID
            $table->json('system_data')->nullable();
            $table->json('broker_data')->nullable();
            $table->json('discrepancy_details')->nullable();
            $table->enum('status', ['mismatch', 'resolved', 'ignored', 'manual_intervention']);
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            
            $table->index(['reconciliation_run_id', 'status']);
            $table->index(['item_type', 'item_id']);
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('reconciliation_items');
    }
};
