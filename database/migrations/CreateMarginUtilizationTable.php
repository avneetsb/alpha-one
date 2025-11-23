<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Margin Utilization Migration
 *
 * Creates the `margin_utilization` table to store periodic snapshots of account
 * margin usage. This data is vital for:
 * 1. Real-time risk monitoring (alerting when utilization > 80%).
 * 2. Historical analysis of capital efficiency.
 * 3. Post-mortem analysis of margin calls.
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
     * Creates the `margin_utilization` table with columns for:
     * - Account context (broker_id, account_id)
     * - Margin metrics (available, used, total, utilization %)
     * - Detailed breakdown (JSON blob for component-wise margin)
     * - Snapshot timestamp
     */
    public function up(): void
    {
        Capsule::schema()->create('margin_utilization', function (Blueprint $table) {
            $table->id();

            // Account context
            $table->string('broker_id', 50);
            $table->string('account_id', 100);

            // Core metrics
            $table->decimal('available_margin', 15, 2); // Free cash + collateral
            $table->decimal('used_margin', 15, 2); // Blocked margin
            $table->decimal('total_margin', 15, 2); // Total account value
            $table->decimal('margin_utilization_percentage', 5, 2); // (Used / Total) * 100

            // Detailed breakdown (e.g., {"span": 50000, "exposure": 20000})
            $table->json('margin_breakdown')->nullable();

            // When the snapshot was taken
            $table->timestamp('snapshot_timestamp');
            $table->timestamps();

            // Indexes for time-series analysis
            $table->index(['broker_id', 'account_id']);
            $table->index('snapshot_timestamp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the `margin_utilization` table.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('margin_utilization');
    }
};
