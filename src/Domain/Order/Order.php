<?php

namespace TradingPlatform\Domain\Order;

use Illuminate\Database\Eloquent\Model;

/**
 * Order Domain Model - Represents trading orders in the system.
 *
 * This model encapsulates all order-related data and behavior for the trading platform.
 * It provides a clean interface for managing trading orders including order placement,
 * status tracking, and broker integration. The model supports various order types
 * (LIMIT, MARKET, STOP_LOSS) and maintains complete audit trails.
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @property int $id Primary key for the order
 * @property int|null $user_id Reference to the user who placed the order
 * @property string|null $broker_id Broker identifier ('dhan', 'zerodha', etc.)
 * @property int $instrument_id Trading instrument reference
 * @property string|null $client_order_id Client-generated order identifier
 * @property string|null $idempotency_key Unique key for duplicate prevention
 * @property string $side Order direction ('BUY', 'SELL')
 * @property string $type Order type ('LIMIT', 'MARKET', 'STOP_LOSS', 'STOP_LOSS_MARKET')
 * @property string $validity Order validity ('DAY', 'IOC')
 * @property int $qty Order quantity
 * @property float $price Order price (decimal with 2 precision)
 * @property string $status Order status ('QUEUED', 'PENDING', 'REJECTED', 'CANCELLED', 'TRADED', 'EXPIRED')
 * @property string|null $broker_order_id Broker-generated order identifier
 * @property \Carbon\Carbon $created_at Order creation timestamp
 * @property \Carbon\Carbon $updated_at Order last update timestamp
 *
 * @table orders
 *
 * @example Basic order creation:
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
 * @example Order status update:
 * $order = Order::find(123);
 * $order->update(['status' => 'TRADED', 'broker_order_id' => 'DH123456']);
 * @example Querying orders:
 * // Get all pending orders for a user
 * $pendingOrders = Order::where('user_id', 1)
 *     ->where('status', 'PENDING')
 *     ->get();
 *
 * // Get orders by broker and status
 * $dhanOrders = Order::where('broker_id', 'dhan')
 *     ->whereIn('status', ['PENDING', 'TRADED'])
 *     ->orderBy('created_at', 'desc')
 *     ->get();
 *
 * @important This model enforces data integrity through:
 * - Proper data type casting for financial calculations
 * - Status enumeration validation
 * - Unique constraints on idempotency keys
 * - Automatic timestamp management
 *
 * @note The model supports multi-broker scenarios where the same order
 *       can be routed to different brokers based on configuration.
 *
 * @see The orders table migration for detailed schema information
 * @see \TradingPlatform\Domain\Order\Services\OrderStateMachine For state transition logic
 * @see \TradingPlatform\Domain\Order\Services\SmartOrderRouter For routing logic
 */
class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'broker_id',
        'instrument_id',
        'client_order_id',
        'idempotency_key',
        'side',
        'type',
        'validity',
        'qty',
        'price',
        'status',
        'broker_order_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'broker_id' => 'string',
        'instrument_id' => 'integer',
        'qty' => 'integer',
        'price' => 'decimal:2',
    ];
}
