<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Reconciliation Runs Table
 *
 * Tracks reconciliation sessions against broker systems for orders/positions/holdings,
 * including scope, counts, statuses, and metadata.
 *
 * @accepted_values
 * - scope: 'orders', 'positions', 'holdings', 'all'
 * - status: 'running', 'completed', 'failed', 'completed_with_errors'
 */
return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('reconciliation_runs', function (Blueprint $table) {
            $table->id();
            $table->string('broker_id', 50);
            $table->enum('scope', ['orders', 'positions', 'holdings', 'all']);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['running', 'completed', 'failed', 'completed_with_errors']);
            $table->integer('items_processed')->default(0);
            $table->integer('mismatches_found')->default(0);
            $table->integer('mismatches_resolved')->default(0);
            $table->json('metadata')->nullable(); // Filter criteria, etc.
            $table->timestamps();
            
            $table->index(['broker_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('reconciliation_runs');
    }
};
