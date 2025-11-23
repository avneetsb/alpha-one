<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Margin Calls Migration
 *
 * Creates the `margin_calls` table to track and manage margin call events.
 * A margin call occurs when the account value falls below the maintenance margin
 * requirement. This table helps in:
 * 1. Recording the event for audit purposes.
 * 2. Managing the lifecycle of the call (Open -> Acknowledged -> Resolved).
 * 3. Triggering automated risk responses (e.g., liquidating positions).
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
     * Creates the `margin_calls` table with columns for:
     * - Identification (broker_id, account_id)
     * - Severity classification (low, medium, high, critical)
     * - Financial details (shortfall amount, utilization %)
     * - Lifecycle management (status, timestamps, resolution notes)
     */
    public function up(): void
    {
        Capsule::schema()->create('margin_calls', function (Blueprint $table) {
            $table->id();

            // Account context
            $table->string('broker_id', 50);
            $table->string('account_id', 100);

            // Risk assessment
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->decimal('margin_shortfall', 15, 2); // Amount needed to restore margin
            $table->decimal('margin_utilization_percentage', 5, 2); // Current usage %

            // Broker message/reason
            $table->text('message');

            // Lifecycle status
            $table->enum('status', ['open', 'acknowledged', 'resolved', 'escalated'])->default('open');

            // Timestamps for SLA tracking
            $table->timestamp('triggered_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Audit trail
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            // Indexes for operational dashboards
            $table->index(['broker_id', 'account_id', 'status']);
            $table->index('triggered_at');
            $table->index('severity');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the `margin_calls` table.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('margin_calls');
    }
};
