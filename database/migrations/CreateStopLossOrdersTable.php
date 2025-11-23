<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Stop Loss Orders Migration
 *
 * Creates the `stop_loss_orders` table to manage advanced stop-loss mechanisms.
 * While basic stop-losses are handled by the broker, this table supports
 * platform-managed smart stops like:
 * - Trailing Stops: Moving the stop price as the market moves in favor.
 * - ATR-based Stops: Adjusting stop distance based on volatility.
 * - Time-based Stops: Exiting positions after a certain duration.
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
     * Creates the `stop_loss_orders` table with columns for:
     * - Linkage (position_id, order_id)
     * - Stop Logic (stop_type, stop_price, trigger_price)
     * - Trailing Logic (trail_amount, trail_percentage)
     * - Configuration (parameters JSON)
     * - Lifecycle (status, triggered_at)
     */
    public function up(): void
    {
        Capsule::schema()->create('stop_loss_orders', function (Blueprint $table) {
            $table->id();

            // Parent position
            $table->foreignId('position_id')->constrained()->onDelete('cascade');

            // Associated broker order (if placed)
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');

            // Stop logic type
            $table->enum('stop_type', ['hard', 'trailing', 'time_based', 'atr_based', 'volatility_adjusted']);

            // Price levels
            $table->decimal('stop_price', 15, 2); // The current effective stop price
            $table->decimal('trigger_price', 15, 2)->nullable(); // Price at which the stop is triggered

            // Trailing parameters
            $table->decimal('trail_amount', 15, 2)->nullable(); // Fixed amount trail (e.g., 5 points)
            $table->decimal('trail_percentage', 5, 2)->nullable(); // Percentage trail (e.g., 1%)

            // Advanced configuration (e.g., {"atr_period": 14, "multiplier": 2})
            $table->json('parameters')->nullable();

            // Status
            $table->enum('status', ['active', 'triggered', 'cancelled', 'expired'])->default('active');

            // Execution time
            $table->timestamp('triggered_at')->nullable();
            $table->timestamps();

            // Indexes for monitoring loop
            $table->index(['position_id', 'status']);
            $table->index('stop_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the `stop_loss_orders` table.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('stop_loss_orders');
    }
};
