<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Fee Reconciliation Table
 *
 * Aggregates and compares calculated fees against broker statements per day
 * to identify discrepancies and track reconciliation status.
 *
 * @example Daily mismatch report:
 * // DB::table('fee_reconciliation')->where('status','mismatch')->orderBy('date','desc')->get();
 *
 * @accepted_values
 * - status: 'matched', 'mismatch', 'pending'
 */
return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('fee_reconciliation', function (Blueprint $table) {
            $table->id();
            $table->string('broker_id', 50);
            $table->date('date');
            $table->decimal('calculated_fees_total', 15, 2);
            $table->decimal('broker_statement_fees_total', 15, 2);
            $table->decimal('discrepancy', 15, 2);
            $table->enum('status', ['matched', 'mismatch', 'pending'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['broker_id', 'date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('fee_reconciliation');
    }
};
