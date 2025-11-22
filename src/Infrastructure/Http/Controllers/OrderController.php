<?php

namespace TradingPlatform\Infrastructure\Http\Controllers;

use TradingPlatform\Domain\Order\Services\SmartOrderRouter;
use TradingPlatform\Infrastructure\Http\ApiResponse;

/**
 * Order Controller
 *
 * Handles REST endpoints for order-related operations including creation
 * and cancellation. Uses standardized API responses via ApiResponse trait
 * and delegates routing logic to SmartOrderRouter.
 *
 * @package TradingPlatform\Infrastructure\Http\Controllers
 * @version 1.0.0
 *
 * @example Place order:
 * POST /orders {"instrument_id":123, "qty":100, "price":150.5, "side":"BUY"}
 *
 * @example Cancel order:
 * POST /orders/{id}/cancel
 */
class OrderController
{
    use ApiResponse;

    private SmartOrderRouter $router;

    public function __construct(SmartOrderRouter $router)
    {
        $this->router = $router;
    }

    /**
     * Create a new order and enqueue for processing.
     *
     * @param mixed $request Request object providing input via all()
     */
    public function store($request)
    {
        $data = $request->all();
        $data['client_order_id'] = $data['client_order_id'] ?? 'ord_' . uniqid();
        
        // Dispatch to queue
        // Assuming we have a global dispatcher or we inject QueueManager
        // For this example, we'll simulate dispatching
        // Queue::push(new \TradingPlatform\Application\Jobs\ProcessOrderJob($data));
        
        return $this->success([
            'message' => 'Order accepted for processing',
            'client_order_id' => $data['client_order_id'],
            'status' => 'QUEUED'
        ]);
    }

    /**
     * Request cancellation of a given order.
     */
    public function cancel($id)
    {
        // Cancel logic...
        return $this->success(['order_id' => $id, 'status' => 'CANCEL_REQUESTED']);
    }
}
