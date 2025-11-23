<?php

namespace TradingPlatform\Application\Jobs;

use TradingPlatform\Domain\Order\Order;
use TradingPlatform\Domain\Order\Services\SmartOrderRouter;
use TradingPlatform\Infrastructure\Logger\LoggerService;

/**
 * Job: Process Order
 *
 * Handles the asynchronous processing of a new order.
 * - Creates the Order entity from raw data.
 * - Routes the order to the best broker using SmartOrderRouter.
 * - Persists the order state.
 * - Logs the outcome.
 */
class ProcessOrderJob
{
    private array $orderData;

    private $logger;

    private SmartOrderRouter $router;

    /**
     * Create a new job instance.
     *
     * @param  array  $orderData  Raw order data (client_order_id, symbol, quantity, etc.).
     * @param  SmartOrderRouter|null  $router  Optional router instance (dependency injection).
     * @param  \Psr\Log\LoggerInterface|null  $logger  Optional logger instance.
     *
     * @example Dispatch order processing
     * ```php
     * dispatch(new ProcessOrderJob([
     *     'client_order_id' => 'ord_123',
     *     'symbol' => 'AAPL',
     *     'quantity' => 10,
     *     'side' => 'buy'
     * ]));
     * ```
     */
    public function __construct(array $orderData, ?SmartOrderRouter $router = null, ?\Psr\Log\LoggerInterface $logger = null)
    {
        $this->orderData = $orderData;
        $this->logger = $logger ?? LoggerService::getLogger();
        // In a real app, router would be injected by the worker
        $this->router = $router ?? new SmartOrderRouter(require __DIR__.'/../../../config/broker.php');
    }

    /**
     * Execute the job logic.
     *
     * 1. Hydrates the Order model.
     * 2. Determines the optimal broker.
     * 3. Sets status to PENDING and saves.
     * 4. Logs success or failure.
     *
     * @throws \Exception Re-throws exceptions to trigger queue retry mechanisms.
     */
    public function handle(): void
    {
        $this->logger->info('Processing order job', ['order_id' => $this->orderData['client_order_id'] ?? 'unknown']);

        try {
            // 1. Create Order Model
            $order = new Order($this->orderData);

            // 2. Route Order
            $brokerId = $this->router->route($order);
            $order->broker_id = $brokerId;
            $order->status = 'PENDING';
            $order->save();

            // 3. Execute Order (via Adapter)

            $this->logger->info('Order processed successfully', ['order_id' => $order->id, 'broker' => $brokerId]);

        } catch (\Exception $e) {
            $this->logger->error('Order processing failed', [
                'error' => $e->getMessage(),
                'data' => $this->orderData,
            ]);

            // Re-throw to trigger retry/DLQ logic in worker
            throw $e;
        }
    }
}
