<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

/**
 * Orders Table Migration
 *
 * Creates the `orders` table, which is the central ledger for all trading activity.
 * It stores every order placed, modified, or cancelled by the system or users.
 *
 * Key Features:
 * - Idempotency: Uses `idempotency_key` to prevent duplicate orders during network retries.
 * - Multi-Broker: Supports routing orders to different brokers via `broker_id`.
 * - Audit Trail: Tracks client-side IDs (`client_order_id`) and broker-side IDs (`broker_order_id`).
 * - Status Tracking: Comprehensive state machine (QUEUED -> PENDING -> TRADED/REJECTED).
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 */
class CreateOrdersTable
{
    /**
     * Run the migration.
     *
     * Creates the `orders` table with columns for:
     * - Identification (user_id, broker_id, instrument_id)
     * - Tracking (client_order_id, broker_order_id, idempotency_key)
     * - Order Details (side, type, validity, qty, price)
     * - Lifecycle (status, timestamps)
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->create('orders', function (Blueprint $table) {
            // Primary key for unique order identification
            $table->id();

            // User reference - nullable to support system-generated orders
            $table->unsignedBigInteger('user_id')->nullable();

            // Broker identification for multi-broker support ('dhan', 'zerodha', etc.)
            $table->string('broker_id')->nullable();

            // Trading instrument reference linking to market data
            $table->unsignedBigInteger('instrument_id');

            // Client-generated order identifier for tracking
            $table->string('client_order_id')->nullable();

            // Unique key for idempotency - prevents duplicate submissions
            $table->string('idempotency_key')->unique()->nullable();

            // Order direction enumeration
            $table->enum('side', ['BUY', 'SELL']);

            // Order type enumeration supporting various execution strategies
            $table->enum('type', ['LIMIT', 'MARKET', 'STOP_LOSS', 'STOP_LOSS_MARKET']);

            // Order validity enumeration
            $table->enum('validity', ['DAY', 'IOC']);

            // Order quantity in units
            $table->integer('qty');

            // Order price with 2 decimal precision for currency values
            $table->decimal('price', 10, 2);

            // Order status enumeration with default 'QUEUED' state
            $table->enum('status', ['QUEUED', 'PENDING', 'REJECTED', 'CANCELLED', 'TRADED', 'EXPIRED'])->default('QUEUED');

            // Broker-generated order identifier for external reference
            $table->string('broker_order_id')->nullable();

            // Automatic timestamps for audit trail
            $table->timestamps();

            // Performance indexes for common query patterns
            $table->index('status');      // Fast status-based queries
            $table->index('client_order_id'); // Fast client order lookups
        });
    }

    /**
     * Reverse the migration.
     *
     * Drops the `orders` table.
     *
     * @return void
     */
    public function down()
    {
        Capsule::schema()->dropIfExists('orders');
    }
}
