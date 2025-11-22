<?php

namespace TradingPlatform\Domain\Backtesting\Services;

use TradingPlatform\Domain\Strategy\Models\Strategy;
use TradingPlatform\Domain\MarketData\Models\Candle;

class BacktestSimulator
{
    private ExecutionModel $executionModel;

    public function __construct(ExecutionModel $executionModel)
    {
        $this->executionModel = $executionModel;
    }

    public function run(Strategy $strategy, array $candles): array
    {
        $trades = [];
        $equity = 100000; // Initial capital
        $position = null;

        foreach ($candles as $candle) {
            // 1. Strategy Signal
            $signal = $strategy->onCandle($candle);

            // 2. Order Generation
            if ($signal) {
                $order = $this->createOrderFromSignal($signal, $candle);
                
                // 3. Execution Simulation
                $execution = $this->executionModel->simulateExecution($order, $candle);

                // 4. Update State
                if ($execution['status'] === 'FILLED') {
                    $trades[] = $execution;
                    // Update equity/position logic...
                }
            }
        }

        return [
            'trades' => $trades,
            'final_equity' => $equity,
            // ... metrics
        ];
    }

    private function createOrderFromSignal($signal, $candle)
    {
        // Mock order creation
        return new \TradingPlatform\Domain\Order\Order([
            'side' => $signal->type,
            'quantity' => 100,
            'price' => $candle->close,
            'created_at' => $candle->timestamp,
        ]);
    }
}
