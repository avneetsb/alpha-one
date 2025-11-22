<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Stop Loss Orders Table
 *
 * Stores active stop orders linked to positions with support for different
 * stop types (hard, trailing, ATR-based, etc.) and parameters.
 *
 * @accepted_values
 * - stop_type: 'hard', 'trailing', 'time_based', 'atr_based', 'volatility_adjusted'
 * - status: 'active', 'triggered', 'cancelled', 'expired'
 */
return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('stop_loss_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('stop_type', ['hard', 'trailing', 'time_based', 'atr_based', 'volatility_adjusted']);
            $table->decimal('stop_price', 15, 2);
            $table->decimal('trigger_price', 15, 2)->nullable();
            $table->decimal('trail_amount', 15, 2)->nullable(); // For trailing stops
            $table->decimal('trail_percentage', 5, 2)->nullable();
            $table->json('parameters')->nullable(); // ATR period, volatility params, etc.
            $table->enum('status', ['active', 'triggered', 'cancelled', 'expired'])->default('active');
            $table->timestamp('triggered_at')->nullable();
            $table->timestamps();
            
            $table->index(['position_id', 'status']);
            $table->index('stop_type');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('stop_loss_orders');
    }
};
