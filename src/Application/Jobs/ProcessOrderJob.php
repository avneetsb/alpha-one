<?php

namespace TradingPlatform\Application\Jobs;

use TradingPlatform\Domain\Order\Order;
use TradingPlatform\Domain\Order\Services\SmartOrderRouter;
use TradingPlatform\Infrastructure\Logger\LoggerService;
use TradingPlatform\Infrastructure\Queue\QueueManager;

/**
 * Job responsible for processing an order asynchronously.
 *
 * Handles creation of the Order model, routing via SmartOrderRouter,
 * persisting the order, and logging the outcome.
 */
class ProcessOrderJob
{
    private array $orderData;
    private $logger;
    private SmartOrderRouter $router;

    /**
 * ProcessOrderJob constructor.
 *
 * @param array $orderData Raw order data required to create the Order model.
 */
public function __construct(array $orderData)
    {
        $this->orderData = $orderData;
        $this->logger = LoggerService::getLogger();
        // In a real app, router would be injected by the worker
        $this->router = new SmartOrderRouter(require __DIR__ . '/../../../config/broker.php');
    }

    /**
 * Execute the job logic.
 *
 * Creates the Order entity, routes it to the appropriate broker, saves it,
 * and logs success or failure. Exceptions are re‑thrown to trigger retry/DLQ.
 */
public function handle(): void
    {
        $this->logger->info("Processing order job", ['order_id' => $this->orderData['client_order_id'] ?? 'unknown']);

        try {
            // 1. Create Order Model
            $order = new Order($this->orderData);
            
            // 2. Route Order
            $brokerId = $this->router->route($order);
            $order->broker_id = $brokerId;
            $order->status = 'PENDING';
            $order->save();

            // 3. Execute Order (via Adapter)

            
            $this->logger->info("Order processed successfully", ['order_id' => $order->id, 'broker' => $brokerId]);

        } catch (\Exception $e) {
            $this->logger->error("Order processing failed", [
                'error' => $e->getMessage(),
                'data' => $this->orderData
            ]);
            
            // Re-throw to trigger retry/DLQ logic in worker
            throw $e;
        }
    }
}
