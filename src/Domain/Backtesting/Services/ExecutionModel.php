<?php

namespace TradingPlatform\Domain\Backtesting\Services;

use TradingPlatform\Domain\Order\Order;
use TradingPlatform\Domain\MarketData\Models\Candle;

class ExecutionModel
{
    private float $latencyMs;
    private float $slippageModel; // 'fixed', 'percentage', 'volatility'
    private float $slippageValue;

    public function __construct(float $latencyMs = 100, string $slippageModel = 'percentage', float $slippageValue = 0.0005)
    {
        $this->latencyMs = $latencyMs;
        $this->slippageModel = $slippageModel;
        $this->slippageValue = $slippageValue;
    }

    public function simulateExecution(Order $order, Candle $candle, array $marketDepth = []): array
    {
        // 1. Latency Delay
        // In a real event loop, we'd delay processing. Here we just adjust timestamp.
        $executionTime = $order->created_at->addMilliseconds($this->latencyMs);

        // 2. Price Determination with Slippage
        $basePrice = $order->price ?? $candle->close; // Limit vs Market
        $executedPrice = $this->applySlippage($basePrice, $order->side, $candle->volatility ?? 0);

        // 3. Fill Probability & Quantity (Liquidity)
        $fillQty = $this->calculateFillQty($order->quantity, $candle->volume, $marketDepth);

        return [
            'price' => $executedPrice,
            'quantity' => $fillQty,
            'timestamp' => $executionTime,
            'status' => $fillQty == $order->quantity ? 'FILLED' : 'PARTIALLY_FILLED',
        ];
    }

    private function applySlippage(float $price, string $side, float $volatility): float
    {
        $slippage = 0.0;

        if ($this->slippageModel === 'fixed') {
            $slippage = $this->slippageValue;
        } elseif ($this->slippageModel === 'percentage') {
            $slippage = $price * $this->slippageValue;
        } elseif ($this->slippageModel === 'volatility') {
            $slippage = $price * ($volatility * 0.1); // 10% of volatility
        }

        // Buy orders slip higher, Sell orders slip lower
        return $side === 'buy' ? $price + $slippage : $price - $slippage;
    }

    private function calculateFillQty(int $orderQty, float $candleVolume, array $marketDepth): int
    {
        // Simple volume constraint: max 10% of candle volume
        $maxLiquidity = $candleVolume * 0.1;
        
        return min($orderQty, (int) $maxLiquidity);
    }
}
