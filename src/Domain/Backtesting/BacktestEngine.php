<?php

namespace TradingPlatform\Domain\Backtesting;

use TradingPlatform\Domain\Strategy\AbstractStrategy;

/**
 * Backtesting engine for strategy validation
 */
class BacktestEngine
{
    private float $initialCapital;
    private float $slippagePercent;
    private array $results = [];

    public function __construct(float $initialCapital = 100000, float $slippagePercent = 0.05)
    {
        $this->initialCapital = $initialCapital;
        $this->slippagePercent = $slippagePercent;
    }

    /**
     * Run backtest
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

            if ($signal &&$signal->action !== 'HOLD') {
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

        // Close any open position
        if ($position > 0) {
            $lastPrice = end($historicalData)['close'];
            $capital += $position * $lastPrice;
        }

        return new BacktestResult([
            'initial_capital' => $this->initialCapital,
            'final_capital' => $capital,
            'trades' => $trades,
            'equity_curve' => $equity_curve,
            'metrics' => $this->calculateMetrics($trades, $equity_curve),
        ]);
    }

    private function applySlippage(float $price, string $action): float
    {
        $slippage = $price * ($this->slippagePercent / 100);
        
        return $action === 'BUY' 
            ? $price + $slippage
            : $price - $slippage;
    }

    private function calculateMetrics(array $trades, array $equityCurve): array
    {
        $totalTrades = count($trades) / 2; // Buy + Sell = 1 trade
        $initialEquity = $this->initialCapital;
        $finalEquity = end($equityCurve);
        $totalReturn = (($finalEquity - $initialEquity) / $initialEquity) * 100;

        // Calculate wins/losses
        $wins = 0;
        $losses = 0;
        $totalProfit = 0;
        $totalLoss = 0;

        for ($i = 0; $i < count($trades); $i += 2) {
            if (!isset($trades[$i + 1])) break;
            
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

    private function calculateStdDev(array $values, float $mean): float
    {
        if (count($values) == 0) return 0;
        
        $variance = 0;
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        return sqrt($variance / count($values));
    }
}

/**
 * Backtest result
 */
class BacktestResult
{
    public float $initialCapital;
    public float $finalCapital;
    public array $trades;
    public array $equityCurve;
    public array $metrics;

    public function __construct(array $data)
    {
        $this->initialCapital = $data['initial_capital'];
        $this->finalCapital = $data['final_capital'];
        $this->trades = $data['trades'];
        $this->equityCurve = $data['equity_curve'];
        $this->metrics = $data['metrics'];
    }

    public function printSummary(): string
    {
        return sprintf(
            "Initial Capital: ₹%.2f\nFinal Capital: ₹%.2f\nTotal Return: %.2f%%\nTotal Trades: %d\nWin Rate: %.2f%%\nMax Drawdown: %.2f%%\nSharpe Ratio: %.2f\nProfit Factor: %.2f",
            $this->initialCapital,
            $this->finalCapital,
            $this->metrics['total_return_percent'],
            $this->metrics['total_trades'],
            $this->metrics['win_rate_percent'],
            $this->metrics['max_drawdown_percent'],
            $this->metrics['sharpe_ratio'],
            $this->metrics['profit_factor']
        );
    }
}
