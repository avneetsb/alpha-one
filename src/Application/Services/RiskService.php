<?php

namespace TradingPlatform\Application\Services;

use TradingPlatform\Domain\Order\Order;
use TradingPlatform\Domain\Portfolio\Position;
use TradingPlatform\Infrastructure\Logger\LoggerService;
use Illuminate\Support\Facades\Cache;

/**
 * Risk Management Service
 * 
 * Comprehensive risk calculations including VaR, stress testing, and correlation monitoring
 */
class RiskService
{
    private RiskLimitManager $limitManager;

    public function __construct(RiskLimitManager $limitManager)
    {
        $this->limitManager = $limitManager;
    }

    /**
     * Run comprehensive risk checks on an order
     */
    public function checkOrder(array $orderData): array
    {
        $violations = [];
        $passed = true;

        // 1. Check Position Limits (Strategy Level)
        $strategyId = $orderData['strategy_id'] ?? 'default';
        $limits = $this->limitManager->getHierarchicalLimits($strategyId);
        
        // Mock current metrics (in real app, fetch from PortfolioService)
        $currentMetrics = [
            'position_size' => $orderData['quantity'] * $orderData['price'],
            'daily_loss' => 0, // Placeholder
        ];

        $limitCheck = $this->limitManager->checkLimits('STRATEGY', $strategyId, $currentMetrics);
        if (!$limitCheck['approved']) {
            $passed = false;
            $violations = array_merge($violations, $limitCheck['violations']);
        }

        // 2. Check Pre-Trade Risk (VaR impact)
        // Calculate VaR impact of this new order
        // Simplified: Estimate volatility from recent history (mocked here)
        $volatility = 0.02; // 2% daily vol
        $orderValue = $orderData['quantity'] * $orderData['price'];
        
        $varImpact = $this->calculateMonteCarloVaR($orderValue, $volatility, 1);
        
        // Check against VaR limit (e.g., 5% of capital)
        $maxVar = 50000; // Mock limit
        
        if ($varImpact > $maxVar) {
            $passed = false;
            $violations[] = [
                'metric' => 'VaR',
                'limit' => $maxVar,
                'current' => $varImpact,
                'message' => "Order VaR impact {$varImpact} exceeds limit {$maxVar}"
            ];
        }

        return [
            'approved' => $passed,
            'violations' => $violations
        ];
    }

    /**
     * Calculate Value at Risk (VaR) - Historical Method
     * 
     * @param array $returns Array of historical returns
     * @param float $confidenceLevel Confidence level (e.g., 0.95 for 95%)
     * @return float VaR value
     */
    public function calculateHistoricalVaR(array $returns, float $confidenceLevel = 0.95): float
    {
        if (empty($returns)) {
            return 0.0;
        }

        sort($returns);
        $index = (int) floor((1 - $confidenceLevel) * count($returns));
        
        return abs($returns[$index] ?? 0.0);
    }

    /**
     * Calculate Conditional VaR (CVaR / Expected Shortfall)
     */
    public function calculateCVaR(array $returns, float $confidenceLevel = 0.95): float
    {
        if (empty($returns)) {
            return 0.0;
        }

        $var = $this->calculateHistoricalVaR($returns, $confidenceLevel);
        $tailLosses = array_filter($returns, fn($r) => $r <= -$var);
        
        return empty($tailLosses) ? 0.0 : abs(array_sum($tailLosses) / count($tailLosses));
    }

    /**
     * Calculate Sharpe Ratio
     */
    public function calculateSharpeRatio(array $returns, float $riskFreeRate = 0.0): float
    {
        if (empty($returns)) {
            return 0.0;
        }

        $avgReturn = array_sum($returns) / count($returns);
        $stdDev = $this->calculateStdDev($returns);

        return $stdDev > 0 ? ($avgReturn - $riskFreeRate) / $stdDev : 0.0;
    }

    /**
     * Calculate Sortino Ratio (downside deviation)
     */
    public function calculateSortinoRatio(array $returns, float $targetReturn = 0.0): float
    {
        if (empty($returns)) {
            return 0.0;
        }

        $avgReturn = array_sum($returns) / count($returns);
        $downsideReturns = array_filter($returns, fn($r) => $r < $targetReturn);
        
        if (empty($downsideReturns)) {
            return 0.0;
        }

        $downsideDeviation = sqrt(array_sum(array_map(
            fn($r) => pow($r - $targetReturn, 2),
            $downsideReturns
        )) / count($downsideReturns));

        return $downsideDeviation > 0 ? ($avgReturn - $targetReturn) / $downsideDeviation : 0.0;
    }

    /**
     * Calculate Maximum Drawdown
     */
    public function calculateMaxDrawdown(array $equityCurve): array
    {
        if (empty($equityCurve)) {
            return ['max_drawdown' => 0.0, 'max_drawdown_pct' => 0.0, 'duration' => 0];
        }

        $peak = $equityCurve[0];
        $maxDrawdown = 0.0;
        $maxDrawdownPct = 0.0;
        $drawdownStart = 0;
        $maxDuration = 0;
        $currentDuration = 0;

        foreach ($equityCurve as $i => $value) {
            if ($value > $peak) {
                $peak = $value;
                $currentDuration = 0;
            } else {
                $drawdown = $peak - $value;
                $drawdownPct = ($peak > 0) ? ($drawdown / $peak) * 100 : 0;
                
                if ($drawdownPct > $maxDrawdownPct) {
                    $maxDrawdownPct = $drawdownPct;
                    $maxDrawdown = $drawdown;
                    $drawdownStart = $i;
                }
                
                $currentDuration++;
                $maxDuration = max($maxDuration, $currentDuration);
            }
        }

        return [
            'max_drawdown' => round($maxDrawdown, 2),
            'max_drawdown_pct' => round($maxDrawdownPct, 2),
            'duration' => $maxDuration,
        ];
    }

    /**
     * Calculate correlation between two return series
     */
    public function calculateCorrelation(array $returns1, array $returns2): float
    {
        $n = min(count($returns1), count($returns2));
        if ($n < 2) {
            return 0.0;
        }

        $mean1 = array_sum($returns1) / $n;
        $mean2 = array_sum($returns2) / $n;

        $covariance = 0.0;
        $variance1 = 0.0;
        $variance2 = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $diff1 = $returns1[$i] - $mean1;
            $diff2 = $returns2[$i] - $mean2;
            
            $covariance += $diff1 * $diff2;
            $variance1 += $diff1 * $diff1;
            $variance2 += $diff2 * $diff2;
        }

        $stdDev1 = sqrt($variance1 / $n);
        $stdDev2 = sqrt($variance2 / $n);

        return ($stdDev1 * $stdDev2) > 0 ? ($covariance / $n) / ($stdDev1 * $stdDev2) : 0.0;
    }

    /**
     * Calculate ATR-based stop loss
     */
    public function calculateATRStopLoss(
        array $candles,
        float $currentPrice,
        int $atrPeriod = 14,
        float $atrMultiplier = 2.0,
        string $side = 'buy'
    ): float {
        $atr = $this->calculateATR($candles, $atrPeriod);
        
        if ($side === 'buy') {
            return $currentPrice - ($atr * $atrMultiplier);
        } else {
            return $currentPrice + ($atr * $atrMultiplier);
        }
    }

    /**
     * Calculate ATR (Average True Range)
     */
    private function calculateATR(array $candles, int $period = 14): float
    {
        if (count($candles) < $period + 1) {
            return 0.0;
        }

        $trueRanges = [];
        for ($i = 1; $i < count($candles); $i++) {
            $high = $candles[$i]['high'];
            $low = $candles[$i]['low'];
            $prevClose = $candles[$i - 1]['close'];
            
            $tr = max(
                $high - $low,
                abs($high - $prevClose),
                abs($low - $prevClose)
            );
            
            $trueRanges[] = $tr;
        }

        return array_sum(array_slice($trueRanges, -$period)) / $period;
    }

    /**
     * Calculate standard deviation
     */
    private function calculateStdDev(array $values): float
    {
        $n = count($values);
        if ($n < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / $n;
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / $n;
        
        return sqrt($variance);
    }

    /**
     * Validate pre-trade risk
     */
    public function validatePreTradeRisk(array $riskChecks): array
    {
        $violations = [];
        $approved = true;

        foreach ($riskChecks as $check) {
            if ($check['current'] > $check['limit']) {
                $violations[] = $check['type'];
                $approved = false;
            }
        }

        return [
            'approved' => $approved,
            'violations' => $violations,
        ];
    }
    /**
     * Calculate VaR using Monte Carlo simulation
     */
    public function calculateMonteCarloVaR(float $currentValue, float $volatility, int $days, int $simulations = 1000, float $confidenceLevel = 0.95): float
    {
        $cacheKey = "var_mc_" . md5(json_encode([$currentValue, $volatility, $days, $simulations, $confidenceLevel]));

        return Cache::remember($cacheKey, 60, function () use ($currentValue, $volatility, $days, $simulations, $confidenceLevel) {
            $simulatedReturns = [];
            $dt = 1 / 252; // Daily time step
    
            for ($i = 0; $i < $simulations; $i++) {
                // Geometric Brownian Motion: dS = S * (mu * dt + sigma * dW)
                // Simplified: return = drift + random_shock
                $randomShock = $this->boxMullerTransform();
                $simulatedReturn = ($volatility * sqrt($dt) * $randomShock);
                $simulatedReturns[] = $simulatedReturn;
            }
    
            sort($simulatedReturns);
            $index = (int) floor((1 - $confidenceLevel) * count($simulatedReturns));
            $varReturn = abs($simulatedReturns[$index] ?? 0.0);
    
            return $currentValue * $varReturn * sqrt($days);
        });
    }

    /**
     * Calculate Option Greeks (Black-Scholes approximation)
     */
    public function calculateGreeks(float $S, float $K, float $T, float $r, float $sigma, string $type = 'call'): array
    {
        $d1 = (log($S / $K) + ($r + 0.5 * pow($sigma, 2)) * $T) / ($sigma * sqrt($T));
        $d2 = $d1 - $sigma * sqrt($T);

        $nd1 = $this->normCdf($d1);
        $nd2 = $this->normCdf($d2);
        $nPrimeD1 = (1 / sqrt(2 * M_PI)) * exp(-0.5 * pow($d1, 2));

        if ($type === 'call') {
            $delta = $nd1;
            $theta = (-($S * $sigma * $nPrimeD1) / (2 * sqrt($T)) - $r * $K * exp(-$r * $T) * $nd2) / 365;
            $rho = $K * $T * exp(-$r * $T) * $nd2;
        } else {
            $delta = $nd1 - 1;
            $theta = (-($S * $sigma * $nPrimeD1) / (2 * sqrt($T)) + $r * $K * exp(-$r * $T) * (1 - $nd2)) / 365;
            $rho = -$K * $T * exp(-$r * $T) * (1 - $nd2);
        }

        $gamma = $nPrimeD1 / ($S * $sigma * sqrt($T));
        $vega = $S * sqrt($T) * $nPrimeD1 / 100; // Scaled for 1% vol change

        return [
            'delta' => round($delta, 4),
            'gamma' => round($gamma, 6),
            'theta' => round($theta, 4),
            'vega'  => round($vega, 4),
            'rho'   => round($rho, 4),
        ];
    }

    /**
     * Calculate Tail Risk (Extreme Value Theory - Peaks Over Threshold)
     * Simplified implementation
     */
    public function calculateTailRisk(array $returns, float $threshold = 0.02): array
    {
        $exceedances = array_filter($returns, fn($r) => $r < -$threshold);
        $count = count($exceedances);
        
        if ($count === 0) {
            return ['probability' => 0.0, 'expected_shortfall' => 0.0];
        }

        $probability = $count / count($returns);
        $expectedShortfall = array_sum($exceedances) / $count;

        return [
            'probability' => $probability,
            'expected_shortfall' => abs($expectedShortfall)
        ];
    }

    private function boxMullerTransform(): float
    {
        $u1 = mt_rand() / mt_getrandmax();
        $u2 = mt_rand() / mt_getrandmax();
        return sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);
    }

    private function normCdf($x): float
    {
        $t = 1 / (1 + 0.2316419 * abs($x));
        $d = 0.3989423 * exp(-$x * $x / 2);
        $prob = $d * $t * (0.3193815 + $t * (-0.3565638 + $t * (1.781478 + $t * (-1.821256 + $t * 1.330274))));
        if ($x > 0) $prob = 1 - $prob;
        return $prob;
    }
}
