<?php

namespace TradingPlatform\Application\Services;

use TradingPlatform\Domain\Exchange\Models\Position;
use TradingPlatform\Domain\Fees\Models\FeeCalculation;
use Illuminate\Support\Facades\Redis; // Assuming Redis facade

class DashboardService
{
    public function getMetrics(): array
    {
        return [
            'system' => $this->getSystemMetrics(),
            'trading' => $this->getTradingMetrics(),
            'workers' => $this->getWorkerMetrics(),
            'queues' => $this->getQueueMetrics(),
        ];
    }

    private function getSystemMetrics(): array
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'cpu_load' => sys_getloadavg()[0] ?? 0,
            'uptime' => 0, // Placeholder
        ];
    }

    private function getTradingMetrics(): array
    {
        // In a real app, these would be cached or aggregated efficiently
        $positions = Position::where('status', 'OPEN')->get();
        $pnl = $positions->sum('unrealized_pnl');
        $realizedPnl = Position::where('status', 'CLOSED')->whereDate('updated_at', today())->sum('realized_pnl');
        
        $fees = FeeCalculation::whereDate('created_at', today())->sum('total_fees');

        return [
            'open_positions' => $positions->count(),
            'unrealized_pnl' => $pnl,
            'realized_pnl' => $realizedPnl,
            'total_pnl' => $pnl + $realizedPnl,
            'total_fees' => $fees,
            'net_pnl' => ($pnl + $realizedPnl) - $fees,
        ];
    }

    private function getWorkerMetrics(): array
    {
        // Placeholder for worker metrics from Redis
        return [
            'active_workers' => 5,
            'processed_jobs' => 1250,
            'failed_jobs' => 2,
        ];
    }

    private function getQueueMetrics(): array
    {
        // Placeholder for queue metrics
        return [
            'pending_jobs' => 12,
            'latency_ms' => 45,
        ];
    }
}
