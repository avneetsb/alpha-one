<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Margin Utilization Table
 *
 * Snapshots of account margin usage and availability for monitoring and risk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('margin_utilization', function (Blueprint $table) {
            $table->id();
            $table->string('broker_id', 50);
            $table->string('account_id', 100);
            $table->decimal('available_margin', 15, 2);
            $table->decimal('used_margin', 15, 2);
            $table->decimal('total_margin', 15, 2);
            $table->decimal('margin_utilization_percentage', 5, 2);
            $table->json('margin_breakdown')->nullable();
            $table->timestamp('snapshot_timestamp');
            $table->timestamps();
            
            $table->index(['broker_id', 'account_id']);
            $table->index('snapshot_timestamp');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('margin_utilization');
    }
};
