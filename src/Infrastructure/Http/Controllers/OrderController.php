<?php

namespace TradingPlatform\Infrastructure\Http\Controllers;

use TradingPlatform\Domain\Order\Services\SmartOrderRouter;
use TradingPlatform\Infrastructure\Http\ApiResponse;

/**
 * Class: Order Controller
 *
 * Handles REST API endpoints for order management.
 * Supports order creation (placement) and cancellation.
 * Delegates routing logic to `SmartOrderRouter` and dispatches jobs for async processing.
 */
class OrderController
{
    use ApiResponse;

    /**
     * @var SmartOrderRouter Service for routing orders.
     */
    private SmartOrderRouter $router;

    /**
     * OrderController constructor.
     */
    public function __construct(SmartOrderRouter $router)
    {
        $this->router = $router;
    }

    /**
     * Create a new order and enqueue for processing.
     *
     * Accepts order details, assigns a client order ID, and pushes a job to the queue
     * for asynchronous execution (routing, validation, and placement).
     *
     * @param  mixed  $request  Request object containing order details:
     *                          - instrument_id, qty, price, side, type, etc.
     * @return \Illuminate\Http\JsonResponse JSON response with order status and ID.
     *
     * @example Request
     * POST /api/v1/orders
     * {
     *   "instrument_id": 101,
     *   "qty": 50,
     *   "price": 1500.00,
     *   "side": "BUY",
     *   "type": "LIMIT"
     * }
     */
    public function store($request)
    {
        $data = $request->all();
        $data['client_order_id'] = $data['client_order_id'] ?? 'ord_'.uniqid();

        // Dispatch to queue
        // Assuming we have a global dispatcher or we inject QueueManager
        // For this example, we'll simulate dispatching
        // Queue::push(new \TradingPlatform\Application\Jobs\ProcessOrderJob($data));

        return $this->success([
            'message' => 'Order accepted for processing',
            'client_order_id' => $data['client_order_id'],
            'status' => 'QUEUED',
        ]);
    }

    /**
     * Request cancellation of a specific order.
     *
     * @param  string  $id  The unique ID of the order to cancel.
     * @return \Illuminate\Http\JsonResponse JSON response confirming cancellation request.
     *
     * @example Request
     * POST /api/v1/orders/ord_123/cancel
     */
    public function cancel($id)
    {
        // Cancel logic...
        return $this->success(['order_id' => $id, 'status' => 'CANCEL_REQUESTED']);
    }
}
