<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Fee Reconciliation Migration
 *
 * Creates the `fee_reconciliation` table, which stores the results of comparing
 * internally calculated fees against the broker's daily contract notes or ledger.
 * This helps identify discrepancies due to:
 * 1. Incorrect fee configuration rules.
 * 2. Hidden broker charges.
 * 3. Rounding differences.
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
     * Creates the `fee_reconciliation` table with columns for:
     * - Reconciliation context (broker_id, date)
     * - Comparison values (calculated vs statement totals)
     * - Result status (matched, mismatch, pending)
     * - Audit notes
     */
    public function up(): void
    {
        Capsule::schema()->create('fee_reconciliation', function (Blueprint $table) {
            $table->id();

            // Broker being reconciled
            $table->string('broker_id', 50);

            // Date of the reconciliation (usually the trade date)
            $table->date('date');

            // Comparison metrics
            $table->decimal('calculated_fees_total', 15, 2);
            $table->decimal('broker_statement_fees_total', 15, 2);
            $table->decimal('discrepancy', 15, 2); // calculated - statement

            // Reconciliation status
            $table->enum('status', ['matched', 'mismatch', 'pending'])->default('pending');

            // Notes for manual resolution or automated explanations
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes for reporting
            $table->index(['broker_id', 'date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the `fee_reconciliation` table.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('fee_reconciliation');
    }
};
