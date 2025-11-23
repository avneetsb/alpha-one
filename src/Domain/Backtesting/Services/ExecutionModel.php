<?php

namespace TradingPlatform\Domain\Backtesting\Services;

use TradingPlatform\Domain\MarketData\Models\Candle;
use TradingPlatform\Domain\Order\Order;

/**
 * Execution Model
 *
 * Simulates the realistic execution of orders in a backtest environment.
 * Accounts for market friction factors such as latency, slippage, and
 * limited liquidity to produce more accurate performance metrics.
 *
 * **Slippage Models:**
 * - **Fixed**: Constant price penalty per share/contract (e.g., $0.01)
 * - **Percentage**: Proportional penalty (e.g., 0.05% of price)
 * - **Volatility**: Dynamic penalty based on current market volatility
 *
 * **Liquidity Constraints:**
 * - Limits fill quantity to a percentage of candle volume (e.g., max 10%)
 * - Simulates partial fills for large orders
 *
 * @version 1.0.0
 *
 * @example Fixed Slippage
 * ```php
 * $model = new ExecutionModel(100, 'fixed', 0.02); // 100ms latency, $0.02 slippage
 * ```
 * @example Volatility-based Slippage
 * ```php
 * $model = new ExecutionModel(50, 'volatility'); // 50ms latency, dynamic slippage
 * ```
 */
class ExecutionModel
{
    /**
     * @var float Simulated network/processing latency in milliseconds.
     */
    private float $latencyMs;

    /**
     * @var string Type of slippage model ('fixed', 'percentage', 'volatility').
     */
    private string $slippageModel;

    /**
     * @var float Parameter value for the slippage calculation.
     */
    private float $slippageValue;

    /**
     * ExecutionModel constructor.
     *
     * @param  float  $latencyMs  Latency in milliseconds (default: 100).
     * @param  string  $slippageModel  Slippage model type: 'fixed', 'percentage', 'volatility' (default: 'percentage').
     * @param  float  $slippageValue  Value for slippage (default: 0.0005 i.e., 0.05%).
     *                                Ignored for 'volatility' model.
     */
    public function __construct(float $latencyMs = 100, string $slippageModel = 'percentage', float $slippageValue = 0.0005)
    {
        $this->latencyMs = $latencyMs;
        $this->slippageModel = $slippageModel;
        $this->slippageValue = $slippageValue;
    }

    /**
     * Simulate the execution of an order.
     *
     * Calculates the execution price and quantity based on the configured models
     * and current market conditions. This method is the core of the execution
     * simulation, combining latency, slippage, and liquidity constraints to
     * produce a realistic fill result.
     *
     * @param  Order  $order  The order to execute.
     * @param  Candle  $candle  The current market candle (price, volume, volatility).
     * @param  array  $marketDepth  Optional market depth snapshot for advanced liquidity modeling.
     * @return array Execution details:
     *               - 'price': Executed price
     *               - 'quantity': Filled quantity
     *               - 'timestamp': Execution time
     *               - 'status': 'FILLED' or 'PARTIALLY_FILLED'
     *
     * @example Simulating execution
     * ```php
     * $order = new Order(['side' => 'BUY', 'quantity' => 100, 'price' => 150.00]);
     * $candle = new Candle(['close' => 150.10, 'volume' => 10000]);
     * $result = $model->simulateExecution($order, $candle);
     *
     * echo "Filled at: " . $result['price'];
     * ```
     */
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

    /**
     * Apply slippage to the execution price.
     *
     * Adjusts the execution price based on the configured slippage model.
     * Slippage is always unfavorable: higher for buys, lower for sells.
     *
     * @param  float  $price  The base execution price.
     * @param  string  $side  Order side ('buy' or 'sell').
     * @param  float  $volatility  Current market volatility (used for 'volatility' model).
     * @return float The adjusted price including slippage.
     *
     * @example Calculating slippage
     * ```php
     * // Buy order with 0.05% slippage
     * $price = $model->applySlippage(100.00, 'buy', 0.02);
     * // Returns 100.05
     * ```
     */
    private function applySlippage(float $price, string $side, float $volatility): float
    {
        $slippage = 0.0;

        if ($this->slippageModel === 'fixed') {
            $slippage = $this->slippageValue;
        } elseif ($this->slippageModel === 'percentage') {
            $slippage = $price * $this->slippageValue;
        } elseif ($this->slippageModel === 'volatility') {
            $slippage = $price * ($volatility * 0.1); // Assume 10% of volatility as impact
        }

        // Buy orders slip higher, Sell orders slip lower
        return $side === 'buy' ? $price + $slippage : $price - $slippage;
    }

    /**
     * Calculate the filled quantity based on available liquidity.
     *
     * Limits the fill quantity to a percentage of the candle's total volume
     * to simulate realistic liquidity constraints. This prevents the backtest
     * from assuming it can execute infinite volume at the candle price.
     *
     * @param  int  $orderQty  The requested order quantity.
     * @param  float  $candleVolume  Total volume of the current candle.
     * @param  array  $marketDepth  Optional market depth.
     * @return int The quantity that can be filled.
     *
     * @example Liquidity check
     * ```php
     * // Order for 1000 shares, candle volume 5000
     * // Max liquidity 10% = 500
     * $fill = $model->calculateFillQty(1000, 5000, []);
     * // Returns 500 (partial fill)
     * ```
     */
    private function calculateFillQty(int $orderQty, float $candleVolume, array $marketDepth): int
    {
        // Simple volume constraint: max 10% of candle volume
        // This prevents unrealistic fills on low liquidity candles
        $maxLiquidity = $candleVolume * 0.1;

        return min($orderQty, (int) $maxLiquidity);
    }
}
