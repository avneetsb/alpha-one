<?php

namespace TradingPlatform\Domain\Order\Services;

use TradingPlatform\Domain\Order\Order;

/**
 * Smart Order Router (SOR)
 *
 * Intelligent routing engine that directs orders to the optimal execution venue/broker
 * based on instrument type, liquidity, and cost considerations. Also provides
 * advanced order execution algorithms like Iceberg and Bracket orders.
 *
 * **Key Capabilities:**
 * - **Dynamic Routing**: Selects broker based on instrument (e.g., Stocks -> Dhan, Crypto -> Binance)
 * - **Iceberg Execution**: Splits large orders into smaller visible tranches to minimize market impact
 * - **Bracket Orders**: Automatically creates Target and Stop-Loss orders for risk management
 * - **Cost Optimization**: Routes to brokers with lowest fees for specific asset classes
 *
 * @version 1.0.0
 *
 * @example Basic Routing
 * ```php
 * $router = new SmartOrderRouter(['default_broker' => 'dhan']);
 * $brokerId = $router->route($order);
 * echo "Routing order to: $brokerId";
 * ```
 * @example Iceberg Order
 * ```php
 * // Split 1000 qty order into 100 qty visible chunks
 * $childOrders = $router->splitIcebergOrder($largeOrder, 100);
 * foreach ($childOrders as $child) {
 *     $broker->placeOrder($child);
 * }
 * ```
 *
 * @see BrokerAdapterInterface For broker implementations
 * @see Order For order model structure
 */
class SmartOrderRouter
{
    /**
     * Routing configuration rules.
     */
    private array $config;

    /**
     * Logger instance.
     *
     * @var \Psr\Log\LoggerInterface|null
     */
    private $logger;

    /**
     * SmartOrderRouter constructor.
     *
     * @param  array  $config  Configuration array defining routing rules.
     *                         Structure: ['default_broker' => 'id', 'routing_rules' => ['type' => 'broker_id']]
     * @param  \Psr\Log\LoggerInterface|null  $logger  Logger for tracking routing decisions.
     */
    public function __construct(array $config = [], ?\Psr\Log\LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger ?? \TradingPlatform\Infrastructure\Logger\LoggerService::getLogger();
    }

    /**
     * Determine the optimal broker for an order.
     *
     * Applies a hierarchy of rules to select the execution venue:
     * 1. **Explicit Preference**: If order specifies a broker, use it.
     * 2. **Instrument Rules**: Check config for instrument-specific routing (e.g., 'OPTION' -> 'dhan').
     * 3. **Default Fallback**: Use the system-wide default broker.
     *
     * @param  Order  $order  The order object to be routed.
     * @return string The identifier of the selected broker (e.g., 'dhan', 'zerodha').
     *
     * @example Routing based on instrument type
     * ```php
     * $order = new Order(['instrument' => (object)['type' => 'FUTURES']]);
     * $broker = $router->route($order);
     * // Returns 'dhan' if configured for futures
     * ```
     * @example Explicit broker preference
     * ```php
     * $order = new Order(['broker_id' => 'zerodha']);
     * $broker = $router->route($order);
     * // Returns 'zerodha'
     * ```
     */
    public function route(Order $order): string
    {
        // 1. Check for specific broker preference in order
        if (isset($order->broker_id)) {
            return $order->broker_id;
        }

        // 2. Check for instrument-specific routing rule
        if (isset($order->instrument) && isset($order->instrument->type)) {
            $type = $order->instrument->type;
            if (isset($this->config['routing_rules'][$type])) {
                return $this->config['routing_rules'][$type];
            }
        }

        // 3. Fallback to default broker
        $broker = $this->config['default_broker'] ?? 'dhan';

        if ($this->logger) {
            $instrumentType = (isset($order->instrument) && isset($order->instrument->type)) ? $order->instrument->type : 'unknown';
            $this->logger->info('Routed order', [
                'instrument_type' => $instrumentType,
                'selected_broker' => $broker,
            ]);
        }

        return $broker;
    }

    /**
     * Split a large order into smaller visible tranches (Iceberg Order).
     *
     * Reduces market impact by hiding the total order size. Only the 'visibleQty'
     * is shown to the market at any time. This is useful for large institutional
     * orders where revealing the full size could move the market against the trader.
     *
     * @param  Order  $parentOrder  The original large quantity order.
     * @param  int  $visibleQty  The maximum quantity to display per child order.
     * @return array List of child Order objects representing the tranches.
     *
     * @example Splitting an order
     * ```php
     * $parent = new Order(['quantity' => 500, 'price' => 100, 'side' => 'BUY']);
     * $children = $router->splitIcebergOrder($parent, 200);
     *
     * // Result:
     * // Order 1: Qty 200
     * // Order 2: Qty 200
     * // Order 3: Qty 100
     * ```
     */
    public function splitIcebergOrder(Order $parentOrder, int $visibleQty): array
    {
        $childOrders = [];
        $remainingQty = $parentOrder->quantity;

        while ($remainingQty > 0) {
            $qty = min($remainingQty, $visibleQty);

            $childOrders[] = new Order([
                'parent_id' => $parentOrder->id,
                'instrument' => $parentOrder->instrument,
                'quantity' => $qty,
                'price' => $parentOrder->price,
                'side' => $parentOrder->side,
                'type' => 'LIMIT',
                'status' => 'PENDING',
            ]);

            $remainingQty -= $qty;
        }

        return $childOrders;
    }

    /**
     * Create a Bracket Order (Entry + Target + Stop Loss).
     *
     * Generates a set of linked orders where the entry order is accompanied by
     * a Take Profit (Target) and Stop Loss order. The exit orders are typically
     * linked via an OCO (One-Cancels-Other) group ID, ensuring that if one
     * executes, the other is cancelled.
     *
     * @param  Order  $entryOrder  The primary entry order.
     * @param  float  $targetPrice  The price target for profit taking.
     * @param  float  $stopLossPrice  The stop loss trigger price.
     * @return array Associative array containing:
     *               - 'entry': The entry order
     *               - 'target': The take profit order (Limit)
     *               - 'stop': The stop loss order (Stop Loss)
     *
     * @example Creating a bracket order
     * ```php
     * $entry = new Order(['side' => 'BUY', 'price' => 100, 'quantity' => 10]);
     * $bracket = $router->createBracketOrder($entry, 110.0, 95.0);
     *
     * // Access components
     * $entryOrder = $bracket['entry'];
     * $targetOrder = $bracket['target'];
     * $stopOrder = $bracket['stop'];
     * ```
     */
    public function createBracketOrder(Order $entryOrder, float $targetPrice, float $stopLossPrice): array
    {
        // 1. Place Entry Order
        // 2. Create OCO (One-Cancels-Other) group for Target and Stop

        $targetOrder = new Order([
            'parent_id' => $entryOrder->id,
            'instrument' => $entryOrder->instrument,
            'quantity' => $entryOrder->quantity, // Opposite side
            'side' => $entryOrder->side === 'BUY' ? 'SELL' : 'BUY',
            'type' => 'LIMIT',
            'price' => $targetPrice,
            'status' => 'PENDING',
            'group_id' => 'oco_'.uniqid(),
        ]);

        $stopOrder = new Order([
            'parent_id' => $entryOrder->id,
            'instrument' => $entryOrder->instrument,
            'quantity' => $entryOrder->quantity, // Opposite side
            'side' => $entryOrder->side === 'BUY' ? 'SELL' : 'BUY',
            'type' => 'STOP_LOSS',
            'trigger_price' => $stopLossPrice,
            'status' => 'PENDING',
            'group_id' => $targetOrder->group_id,
        ]);

        return [
            'entry' => $entryOrder,
            'target' => $targetOrder,
            'stop' => $stopOrder,
        ];
    }
}
