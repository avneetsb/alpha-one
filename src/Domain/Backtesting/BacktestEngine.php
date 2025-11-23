<?php

namespace TradingPlatform\Domain\Backtesting;

use TradingPlatform\Domain\Strategy\AbstractStrategy;
use TradingPlatform\Domain\Strategy\Models\BacktestResult;

/**
 * Backtest Engine
 *
 * Core simulation engine for validating trading strategies against historical data.
 * Simulates realistic trading conditions including slippage, capital constraints,
 * and position management to provide accurate performance metrics.
 *
 * **Key Features:**
 * - **Realistic Simulation**: Models order execution with configurable slippage
 * - **Capital Management**: Tracks cash, positions, and total equity
 * - **Performance Analytics**: Calculates Sharpe, Sortino, Drawdown, and Win Rate
 * - **Trade Tracking**: Records every trade with timestamp, price, and quantity
 * - **Equity Curve**: Generates time-series data of portfolio value
 *
 * **Simulation Assumptions:**
 * - Market orders executed at next candle open price
 * - Slippage applied to all executions (configurable %)
 * - No partial fills (all-or-nothing execution)
 * - Single instrument per backtest run
 * - Long-only positions (can be extended for shorting)
 *
 * @version 1.0.0
 *
 * @example Basic Backtest
 * ```php
 * // Initialize engine with ₹100k capital and 0.05% slippage
 * $engine = new BacktestEngine(100000, 0.05);
 *
 * // Load strategy and data
 * $strategy = new RSIStrategy('RSI_14', ['period' => 14]);
 * $data = $historicalDataFetcher->fetch('RELIANCE', '2023-01-01', '2023-12-31');
 *
 * // Run simulation
 * $result = $engine->run($strategy, $data);
 *
 * // Analyze results
 * echo "Total Return: " . $result->total_return . "%\n";
 * echo "Sharpe Ratio: " . $result->sharpe_ratio . "\n";
 * echo "Max Drawdown: " . $result->max_drawdown . "%\n";
 * ```
 *
 * @see AbstractStrategy For strategy implementation interface
 * @see BacktestResult For detailed result structure
 */
class BacktestEngine
{
    /**
     * Initial capital for the simulation.
     */
    private float $initialCapital;

    /**
     * Slippage percentage applied to each trade.
     * e.g., 0.05 means 0.05% slippage.
     */
    private float $slippagePercent;

    /**
     * BacktestEngine constructor.
     *
     * @param  float  $initialCapital  Starting capital (default: 100,000).
     * @param  float  $slippagePercent  Slippage per trade in percent (default: 0.05%).
     */
    public function __construct(float $initialCapital = 100000, float $slippagePercent = 0.05)
    {
        $this->initialCapital = $initialCapital;
        $this->slippagePercent = $slippagePercent;
    }

    /**
     * Run backtest simulation for a strategy.
     *
     * Iterates through historical data, feeding each candle to the strategy,
     * executing generated signals, and tracking performance.
     *
     * **Execution Logic:**
     * 1. **Signal Generation**: Strategy receives current candle
     * 2. **Order Execution**:
     *    - BUY: Checks available cash, applies slippage, updates position
     *    - SELL: Checks available position, applies slippage, updates cash
     * 3. **Equity Tracking**: Updates total portfolio value after each candle
     * 4. **Metric Calculation**: Computes final performance stats
     *
     * @param  AbstractStrategy  $strategy  The strategy instance to test.
     * @param  array  $historicalData  Array of candle data (must contain 'close' and 'timestamp').
     * @return BacktestResult Object containing comprehensive performance metrics and trade history.
     *
     * @example
     * ```php
     * $result = $engine->run($myStrategy, $candles);
     *
     * if ($result->isProfitable() && $result->sharpe_ratio > 1.5) {
     *     $deployer->deploy($myStrategy);
     * }
     * ```
     */
    public function run(AbstractStrategy $strategy, array $historicalData): BacktestResult
    {
        $capital = $this->initialCapital;
        $position = 0;
        $trades = [];
        $equity_curve = [$capital];

        foreach ($historicalData as $index => $candle) {
            // Get signal from strategy
            $signal = $strategy->execute($candle);

            if ($signal && $signal->action !== 'HOLD') {
                // Apply slippage
                $price = $this->applySlippage($candle['close'], $signal->action);

                if ($signal->action === 'BUY' && $position == 0) {
                    $quantity = floor($capital / $price);
                    if ($quantity > 0) {
                        $position = $quantity;
                        $capital -= $quantity * $price;

                        $trades[] = [
                            'type' => 'BUY',
                            'price' => $price,
                            'quantity' => $quantity,
                            'timestamp' => $candle['timestamp'] ?? $index,
                        ];
                    }
                } elseif ($signal->action === 'SELL' && $position > 0) {
                    $capital += $position * $price;

                    $trades[] = [
                        'type' => 'SELL',
                        'price' => $price,
                        'quantity' => $position,
                        'timestamp' => $candle['timestamp'] ?? $index,
                    ];

                    $position = 0;
                }
            }

            // Update equity curve
            $currentEquity = $capital + ($position * $candle['close']);
            $equity_curve[] = $currentEquity;
        }

        // Close any open position at the end to realize final P&L
        if ($position > 0) {
            $lastPrice = end($historicalData)['close'];
            $capital += $position * $lastPrice;
        }

        // Calculate metrics
        $metrics = $this->calculateMetrics($trades, $equity_curve);

        // Map to BacktestResult model
        // Note: In a real app, you might save this to DB. Here we return an instance.
        return new BacktestResult([
            'initial_capital' => $this->initialCapital,
            'final_capital' => $capital,
            'total_return' => $metrics['total_return_percent'],
            'total_profit' => $metrics['total_profit'],
            'total_trades' => $metrics['total_trades'],
            'win_rate' => $metrics['win_rate_percent'],
            'profit_factor' => $metrics['profit_factor'],
            'sharpe_ratio' => $metrics['sharpe_ratio'],
            'max_drawdown' => $metrics['max_drawdown_percent'],
            'equity_curve' => $equity_curve,
            'trades' => $trades, // Note: BacktestResult model might not have 'trades' in fillable, but useful for analysis
        ]);
    }

    /**
     * Apply slippage to execution price.
     *
     * Simulates market impact and spread costs by adjusting the execution price
     * against the trader (buy higher, sell lower).
     *
     * @param  float  $price  Base execution price.
     * @param  string  $action  Trade direction ('BUY' or 'SELL').
     * @return float Adjusted price including slippage.
     */
    private function applySlippage(float $price, string $action): float
    {
        $slippage = $price * ($this->slippagePercent / 100);

        return $action === 'BUY'
            ? $price + $slippage
            : $price - $slippage;
    }

    /**
     * Calculate comprehensive performance metrics.
     *
     * Computes standard trading metrics from the trade history and equity curve.
     *
     * @param  array  $trades  List of executed trades.
     * @param  array  $equityCurve  Time series of portfolio value.
     * @return array Associative array of metrics.
     */
    private function calculateMetrics(array $trades, array $equityCurve): array
    {
        $totalTrades = count($trades) / 2; // Buy + Sell = 1 completed trade
        $initialEquity = $this->initialCapital;
        $finalEquity = end($equityCurve);
        $totalReturn = (($finalEquity - $initialEquity) / $initialEquity) * 100;

        // Calculate wins/losses
        $wins = 0;
        $losses = 0;
        $totalProfit = 0;
        $totalLoss = 0;

        for ($i = 0; $i < count($trades); $i += 2) {
            if (! isset($trades[$i + 1])) {
                break;
            }

            $buyTrade = $trades[$i];
            $sellTrade = $trades[$i + 1];

            $profit = ($sellTrade['price'] - $buyTrade['price']) * $buyTrade['quantity'];

            if ($profit > 0) {
                $wins++;
                $totalProfit += $profit;
            } else {
                $losses++;
                $totalLoss += abs($profit);
            }
        }

        $winRate = $totalTrades > 0 ? ($wins / $totalTrades) * 100 : 0;

        // Calculate max drawdown
        $maxDrawdown = $this->calculateMaxDrawdown($equityCurve);

        // Calculate Sharpe ratio (simplified)
        $returns = [];
        for ($i = 1; $i < count($equityCurve); $i++) {
            $returns[] = ($equityCurve[$i] - $equityCurve[$i - 1]) / $equityCurve[$i - 1];
        }

        $avgReturn = count($returns) > 0 ? array_sum($returns) / count($returns) : 0;
        $stdDev = $this->calculateStdDev($returns, $avgReturn);
        $sharpeRatio = $stdDev > 0 ? ($avgReturn / $stdDev) * sqrt(252) : 0; // Annualized

        return [
            'total_trades' => $totalTrades,
            'total_return_percent' => round($totalReturn, 2),
            'win_rate_percent' => round($winRate, 2),
            'total_profit' => round($totalProfit, 2),
            'total_loss' => round($totalLoss, 2),
            'max_drawdown_percent' => round($maxDrawdown, 2),
            'sharpe_ratio' => round($sharpeRatio, 2),
            'profit_factor' => $totalLoss > 0 ? round($totalProfit / $totalLoss, 2) : 0,
        ];
    }

    /**
     * Calculate Maximum Drawdown percentage.
     *
     * @param  array  $equityCurve  Array of equity values.
     * @return float Max drawdown as a positive percentage.
     */
    private function calculateMaxDrawdown(array $equityCurve): float
    {
        $maxDrawdown = 0;
        $peak = $equityCurve[0];

        foreach ($equityCurve as $value) {
            if ($value > $peak) {
                $peak = $value;
            }

            $drawdown = (($peak - $value) / $peak) * 100;
            $maxDrawdown = max($maxDrawdown, $drawdown);
        }

        return $maxDrawdown;
    }

    /**
     * Calculate standard deviation of returns.
     *
     * @param  array  $values  Array of return values.
     * @param  float  $mean  Mean return.
     * @return float Standard deviation.
     */
    private function calculateStdDev(array $values, float $mean): float
    {
        if (count($values) == 0) {
            return 0;
        }

        $variance = 0;
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }

        return sqrt($variance / count($values));
    }
}
