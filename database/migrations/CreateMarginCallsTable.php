<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Margin Calls Table
 *
 * Captures margin call events from brokers with severity, shortfall, and
 * resolution lifecycle for operational tracking.
 *
 * @example List open critical calls:
 * // DB::table('margin_calls')->where('status','open')->where('severity','critical')->get();
 *
 * @accepted_values
 * - severity: 'low', 'medium', 'high', 'critical'
 * - status: 'open', 'acknowledged', 'resolved', 'escalated'
 */
return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('margin_calls', function (Blueprint $table) {
            $table->id();
            $table->string('broker_id', 50);
            $table->string('account_id', 100);
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->decimal('margin_shortfall', 15, 2);
            $table->decimal('margin_utilization_percentage', 5, 2);
            $table->text('message');
            $table->enum('status', ['open', 'acknowledged', 'resolved', 'escalated'])->default('open');
            $table->timestamp('triggered_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            
            $table->index(['broker_id', 'account_id', 'status']);
            $table->index('triggered_at');
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('margin_calls');
    }
};
