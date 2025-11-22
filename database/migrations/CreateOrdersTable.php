<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

/**
 * Migration for creating the orders table that stores trading order information.
 * 
 * This migration establishes the core orders table structure for the trading platform,
 * supporting various order types, statuses, and broker integrations. The table
 * maintains a complete audit trail of all trading activities with proper indexing
 * for performance optimization.
 * 
 * @package Database\Migrations
 * @author  Trading Platform Team
 * @version 1.0.0
 * 
 * @example
 * // Table structure created:
 * // - id: Primary key
 * // - user_id: Reference to user (nullable for system orders)
 * // - broker_id: Broker identifier ('dhan', 'zerodha', etc.)
 * // - instrument_id: Reference to trading instrument
 * // - client_order_id: Client-generated order identifier
 * // - idempotency_key: Unique key for duplicate prevention
 * // - side: Order direction ('BUY', 'SELL')
 * // - type: Order type ('LIMIT', 'MARKET', 'STOP_LOSS', 'STOP_LOSS_MARKET')
 * // - validity: Order validity ('DAY', 'IOC')
 * // - qty: Order quantity
 * // - price: Order price (10,2 decimal precision)
 * // - status: Order status with default 'QUEUED'
 * // - broker_order_id: Broker-generated order identifier
 * // - timestamps: created_at and updated_at
 * 
 * @accepted_values
 * - side: 'BUY', 'SELL'
 * - type: 'LIMIT', 'MARKET', 'STOP_LOSS', 'STOP_LOSS_MARKET'
 * - validity: 'DAY', 'IOC'
 * - status: 'QUEUED', 'PENDING', 'REJECTED', 'CANCELLED', 'TRADED', 'EXPIRED'
 * 
 * @example Insert:
 * DB::table('orders')->insert([
 *   'instrument_id' => 123,
 *   'side' => 'BUY',
 *   'type' => 'LIMIT',
 *   'validity' => 'DAY',
 *   'qty' => 100,
 *   'price' => 150.50,
 *   'status' => 'QUEUED',
 *   'idempotency_key' => 'ord-123-unique'
 * ]);
 *
 * // Usage example:
 * $order = Order::create([
 *     'user_id' => 1,
 *     'broker_id' => 'dhan',
 *     'instrument_id' => 123,
 *     'side' => 'BUY',
 *     'type' => 'LIMIT',
 *     'validity' => 'DAY',
 *     'qty' => 100,
 *     'price' => 150.50,
 *     'status' => 'PENDING'
 * ]);
 */
class CreateOrdersTable
{
    /**
     * Run the migration to create the orders table.
     * 
     * Creates a comprehensive orders table with the following features:
     * - Primary key auto-incrementing ID
     * - User reference (nullable for system-generated orders)
     * - Broker identification for multi-broker support
     * - Instrument reference linking to tradable assets
     * - Client order ID for order tracking
     * - Idempotency key to prevent duplicate order submissions
     * - Order side (BUY/SELL) enumeration
     * - Order type enumeration supporting multiple order types
     * - Order validity enumeration (DAY/IOC)
     * - Quantity and price fields with appropriate precision
     * - Status enumeration with default 'QUEUED' state
     * - Broker order ID for external reference
     * - Automatic timestamps for audit trail
     * - Strategic indexes for query performance
     * 
     * @return void
     * 
     * @throws \Exception If table creation fails
     * 
     * @example
     * // The created table supports various order scenarios:
     * // 1. Regular client order:
     * //    user_id: 123, broker_id: 'dhan', side: 'BUY', type: 'LIMIT'
     * // 2. System-generated order:
     * //    user_id: null, broker_id: 'system', side: 'SELL', type: 'MARKET'
     * // 3. Stop-loss order:
     * //    type: 'STOP_LOSS', validity: 'DAY', status: 'PENDING'
     */
    public function up()
    {
        Capsule::schema()->create('orders', function (Blueprint $table) {
            // Primary key for unique order identification
            $table->id();
            
            // User reference - nullable to support system-generated orders
            $table->unsignedBigInteger('user_id')->nullable(); // Nullable for now as we don't have users table seeded
            
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
     * Reverse the migration by dropping the orders table.
     * 
     * Removes the orders table and all associated data. This operation
     * is destructive and will permanently delete all order records.
     * 
     * @return void
     * 
     * @throws \Exception If table drop operation fails
     * 
     * @warning This will permanently delete all order data. Ensure proper
     *          backups are taken before running this migration rollback.
     */
    public function down()
    {
        Capsule::schema()->dropIfExists('orders');
    }
}
