<?php

namespace TradingPlatform\Domain\Order\Services;

use TradingPlatform\Domain\Order\Order;

/**
 * Smart Order Router
 *
 * Determines the appropriate broker and routing strategy for orders based
 * on configuration, instrument type, and order attributes. Supports
 * iceberg order splitting and bracket order creation utilities.
 *
 * @package TradingPlatform\Domain\Order\Services
 * @version 1.0.0
 *
 * @example Route order with default broker:
 * $broker = $router->route($order); // 'dhan' if not configured otherwise
 *
 * @example Split iceberg order:
 * $parts = $router->splitIcebergOrder($order, 100);
 *
 * @example Create bracket order:
 * [$entry, $target, $stop] = $router->createBracketOrder($entryOrder, 155.0, 145.0);
 */
class SmartOrderRouter
{
    private array $config;
    private $logger;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->logger = \TradingPlatform\Infrastructure\Logger\LoggerService::getLogger();
    }

    /**
     * Select broker for order based on preferences and rules.
     */
    public function route(Order $order): string
    {
        // 1. Check for specific broker preference in order
        if (isset($order->broker_id)) {
            return $order->broker_id;
        }

        // 2. Check for instrument-specific routing rule
        if (isset($this->config['routing_rules'][$order->instrument->type])) {
            return $this->config['routing_rules'][$order->instrument->type];
        }

        // 3. Fallback to default broker
        $broker = $this->config['default_broker'] ?? 'dhan';
        
        $this->logger->info("Routed order", [
            'instrument_type' => $order->instrument->type ?? 'unknown',
            'selected_broker' => $broker
        ]);
        
        return $broker;
    }

    /**
     * Split a large order into smaller visible tranches (iceberg).
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
     * Create a bracket order (entry + OCO target/stop).
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
            'group_id' => 'oco_' . uniqid()
        ]);

        $stopOrder = new Order([
            'parent_id' => $entryOrder->id,
            'instrument' => $entryOrder->instrument,
            'quantity' => $entryOrder->quantity, // Opposite side
            'side' => $entryOrder->side === 'BUY' ? 'SELL' : 'BUY',
            'type' => 'STOP_LOSS',
            'trigger_price' => $stopLossPrice,
            'status' => 'PENDING',
            'group_id' => $targetOrder->group_id
        ]);

        return [
            'entry' => $entryOrder,
            'target' => $targetOrder,
            'stop' => $stopOrder
        ];
    }
}
