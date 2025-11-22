<?php

namespace TradingPlatform\Infrastructure\Worker;

use Illuminate\Support\Facades\Redis;
use TradingPlatform\Infrastructure\Logging\AsyncLogger;

class WorkerManager
{
    private AsyncLogger $logger;
    private string $workerId;
    private string $workerType;

    public function __construct(AsyncLogger $logger, string $workerType)
    {
        $this->logger = $logger;
        $this->workerType = $workerType;
        $this->workerId = uniqid("worker:{$workerType}:");
    }

    public function startHeartbeat(): void
    {
        // In a real loop, this would run every second
        $this->sendHeartbeat();
        
        // Check for leader
        if ($this->shouldBeLeader()) {
            $this->becomeLeader();
        }
    }

    private function sendHeartbeat(): void
    {
        $key = "worker:heartbeat:{$this->workerId}";
        Redis::setex($key, 5, time()); // 5s TTL
        
        // Add to active workers set
        Redis::sadd("workers:active:{$this->workerType}", $this->workerId);
        
        $this->logger->debug("Heartbeat sent", ['worker_id' => $this->workerId]);
    }

    private function shouldBeLeader(): bool
    {
        $leaderKey = "worker:leader:{$this->workerType}";
        
        // Try to acquire lock
        return (bool) Redis::set($leaderKey, $this->workerId, 'EX', 10, 'NX');
    }

    private function becomeLeader(): void
    {
        $this->logger->info("Became leader", ['worker_id' => $this->workerId]);
        
        // Perform leader duties (e.g., assigning tasks, checking dead workers)
        $this->checkDeadWorkers();
    }

    private function checkDeadWorkers(): void
    {
        $activeWorkers = Redis::smembers("workers:active:{$this->workerType}");
        
        foreach ($activeWorkers as $workerId) {
            if (!Redis::exists("worker:heartbeat:{$workerId}")) {
                $this->handleDeadWorker($workerId);
            }
        }
    }

    private function handleDeadWorker(string $workerId): void
    {
        $this->logger->warning("Detected dead worker", ['worker_id' => $workerId]);
        
        // Remove from active set
        Redis::srem("workers:active:{$this->workerType}", $workerId);
        
        // Re-queue assigned jobs...
    }
}
