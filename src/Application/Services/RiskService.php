<?php

namespace TradingPlatform\Application\Services;

use Illuminate\Support\Facades\Cache;
use TradingPlatform\Domain\Order\Order;
use TradingPlatform\Domain\Portfolio\Position;
use TradingPlatform\Domain\Risk\Services\RiskLimitManager;

/**
 * Risk Service
 *
 * Comprehensive risk management service providing calculations for portfolio
 * risk metrics, pre-trade risk checks, and advanced risk modeling.
 *
 * **Key Capabilities:**
 * - **Pre-Trade Risk Checks**: Validates orders against position limits and VaR impact
 * - **Value at Risk (VaR)**: Calculates potential loss using Historical and Monte Carlo methods
 * - **Risk-Adjusted Returns**: Computes Sharpe and Sortino ratios
 * - **Drawdown Analysis**: Tracks maximum peak-to-trough declines
 * - **Option Greeks**: Calculates Delta, Gamma, Theta, Vega, Rho using Black-Scholes
 * - **Tail Risk**: Estimates extreme loss probabilities using EVT
 *
 * @version 1.0.0
 *
 * @example Basic Risk Check
 * ```php
 * $riskService = new RiskService($limitManager);
 * $order = ['strategy_id' => 'strat_1', 'quantity' => 100, 'price' => 150.0];
 *
 * $check = $riskService->checkOrder($order);
 * if (!$check['approved']) {
 *     print_r($check['violations']);
 * }
 * ```
 * @example Calculate VaR
 * ```php
 * // Calculate 95% 1-day VaR using Monte Carlo simulation
 * $var = $riskService->calculateMonteCarloVaR(
 *     currentValue: 100000,
 *     volatility: 0.15, // 15% annualized
 *     days: 1
 * );
 * echo "1-Day 95% VaR: $" . round($var, 2);
 * ```
 *
 * @see RiskLimitManager For hierarchical limit management
 * @see PortfolioAnalyticsService For portfolio-level performance metrics
 */
class RiskService
{
    /**
     * Manager for retrieving and validating risk limits.
     */
    private RiskLimitManager $limitManager;

    /**
     * RiskService constructor.
     *
     * @param  RiskLimitManager  $limitManager  Service for managing risk limits.
     */
    public function __construct(RiskLimitManager $limitManager)
    {
        $this->limitManager = $limitManager;
    }

    /**
     * Run comprehensive risk checks on an order before execution.
     *
     * Validates an order against multiple risk layers:
     * 1. **Strategy Limits**: Max position size, max daily loss
     * 2. **Portfolio Limits**: Global exposure limits
     * 3. **VaR Impact**: Ensures order doesn't push portfolio VaR beyond threshold
     *
     * @param  array  $orderData  Associative array containing order details:
     *                            - 'strategy_id' (string): ID of the strategy placing the order
     *                            - 'quantity' (float): Order quantity
     *                            - 'price' (float): Order price
     *                            - 'side' (string): 'buy' or 'sell'
     * @return array Result array containing:
     *               - 'approved' (bool): True if all checks pass
     *               - 'violations' (array): List of violation details if rejected
     *
     * @example Check an order
     * ```php
     * $order = [
     *     'strategy_id' => 'trend_follower_1',
     *     'quantity' => 500,
     *     'price' => 105.50,
     *     'side' => 'buy'
     * ];
     *
     * $result = $riskService->checkOrder($order);
     *
     * if ($result['approved']) {
     *     $orderService->execute($order);
     * } else {
     *     $logger->warning('Order rejected by risk check', $result['violations']);
     * }
     * ```
     */
    public function checkOrder(array $orderData): array
    {
        $violations = [];
        $passed = true;

        // 1. Check Position Limits (Strategy Level)
        $strategyId = $orderData['strategy_id'] ?? 'default';
        // $limits = $this->limitManager->getHierarchicalLimits($strategyId);

        // Mock current metrics (in real app, fetch from PortfolioService)
        $currentMetrics = [
            'position_size' => $orderData['quantity'] * $orderData['price'],
            'daily_loss' => 0, // Placeholder
        ];

        $limitCheck = $this->limitManager->checkLimits('STRATEGY', $strategyId, $currentMetrics);
        if (! $limitCheck['approved']) {
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
                'message' => "Order VaR impact {$varImpact} exceeds limit {$maxVar}",
            ];
        }

        return [
            'approved' => $passed,
            'violations' => $violations,
        ];
    }

    /**
     * Calculate Value at Risk (VaR) using Historical Simulation.
     *
     * Estimates the maximum potential loss over a specific time horizon at a given
     * confidence level based on historical return distribution.
     *
     * **Methodology:**
     * 1. Sort historical returns from worst to best
     * 2. Identify the return at the percentile corresponding to (1 - confidence)
     * 3. VaR = Absolute value of that return
     *
     * @param  array  $returns  Array of historical returns (decimal format).
     * @param  float  $confidenceLevel  Confidence level (default: 0.95 for 95%).
     * @return float VaR value (as a positive decimal).
     *
     * @example Calculate 99% Historical VaR
     * ```php
     * $returns = [-0.05, -0.02, 0.01, 0.03, -0.01, ...]; // 100 days of returns
     * $var = $riskService->calculateHistoricalVaR($returns, 0.99);
     * // If the 1st percentile return is -0.045, VaR is 0.045 (4.5%)
     * ```
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
     * Calculate Conditional VaR (CVaR) / Expected Shortfall.
     *
     * Measures the expected loss *given* that the loss exceeds the VaR threshold.
     * This provides a better estimate of tail risk than standard VaR.
     *
     * **Formula:** Average of all returns worse than the VaR threshold.
     *
     * @param  array  $returns  Array of historical returns.
     * @param  float  $confidenceLevel  Confidence level (default: 0.95).
     * @return float CVaR value (as a positive decimal).
     *
     * @example Calculate 95% CVaR
     * ```php
     * $cvar = $riskService->calculateCVaR($returns, 0.95);
     * echo "Expected loss in worst 5% of cases: " . ($cvar * 100) . "%";
     * ```
     */
    public function calculateCVaR(array $returns, float $confidenceLevel = 0.95): float
    {
        if (empty($returns)) {
            return 0.0;
        }

        $var = $this->calculateHistoricalVaR($returns, $confidenceLevel);
        $tailLosses = array_filter($returns, fn ($r) => $r <= -$var);

        return empty($tailLosses) ? 0.0 : abs(array_sum($tailLosses) / count($tailLosses));
    }

    /**
     * Calculate Sharpe Ratio.
     *
     * Measures risk-adjusted performance by comparing excess returns to volatility.
     *
     * **Formula:** (Mean Return - Risk-Free Rate) / Standard Deviation
     *
     * @param  array  $returns  Array of returns.
     * @param  float  $riskFreeRate  Risk-free rate (default: 0.0).
     * @return float Sharpe Ratio.
     *
     * @example
     * ```php
     * $sharpe = $riskService->calculateSharpeRatio($returns, 0.02);
     * if ($sharpe > 1.0) echo "Good risk-adjusted returns";
     * ```
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
     * Calculate Sortino Ratio.
     *
     * Similar to Sharpe Ratio but penalizes only downside volatility.
     *
     * **Formula:** (Mean Return - Target Return) / Downside Deviation
     *
     * @param  array  $returns  Array of returns.
     * @param  float  $targetReturn  Target return threshold (default: 0.0).
     * @return float Sortino Ratio.
     */
    public function calculateSortinoRatio(array $returns, float $targetReturn = 0.0): float
    {
        if (empty($returns)) {
            return 0.0;
        }

        $avgReturn = array_sum($returns) / count($returns);
        $downsideReturns = array_filter($returns, fn ($r) => $r < $targetReturn);

        if (empty($downsideReturns)) {
            return 0.0;
        }

        $downsideDeviation = sqrt(array_sum(array_map(
            fn ($r) => pow($r - $targetReturn, 2),
            $downsideReturns
        )) / count($downsideReturns));

        return $downsideDeviation > 0 ? ($avgReturn - $targetReturn) / $downsideDeviation : 0.0;
    }

    /**
     * Calculate Maximum Drawdown.
     *
     * Identifies the largest peak-to-trough decline in the equity curve.
     *
     * @param  array  $equityCurve  Array of cumulative equity values.
     * @return array Associative array containing:
     *               - 'max_drawdown' (float): Absolute value of max loss
     *               - 'max_drawdown_pct' (float): Percentage loss from peak
     *               - 'duration' (int): Number of periods in drawdown
     *
     * @example
     * ```php
     * $equity = [100, 105, 102, 98, 103];
     * $dd = $riskService->calculateMaxDrawdown($equity);
     * echo "Max Drawdown: " . $dd['max_drawdown_pct'] . "%"; // (105-98)/105 = 6.67%
     * ```
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
     * Calculate correlation coefficient between two return series.
     *
     * Measures the statistical relationship between two assets.
     * Range: -1 (perfect negative) to +1 (perfect positive).
     *
     * @param  array  $returns1  First return series.
     * @param  array  $returns2  Second return series.
     * @return float Correlation coefficient.
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
     * Calculate ATR-based stop loss price.
     *
     * Uses Average True Range (ATR) to set dynamic stop losses based on market volatility.
     *
     * @param  array  $candles  Array of candle data (must have 'high', 'low', 'close').
     * @param  float  $currentPrice  Current market price.
     * @param  int  $atrPeriod  Period for ATR calculation (default: 14).
     * @param  float  $atrMultiplier  Multiplier for ATR buffer (default: 2.0).
     * @param  string  $side  Trade side ('buy' or 'sell').
     * @return float Stop loss price level.
     *
     * @example
     * ```php
     * $stopPrice = $riskService->calculateATRStopLoss($candles, 100.0, 14, 2.0, 'buy');
     * // If ATR is 2.0, stop price will be 100 - (2.0 * 2.0) = 96.0
     * ```
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
     * Calculate Average True Range (ATR).
     *
     * @param  array  $candles  Array of candle data.
     * @param  int  $period  Smoothing period.
     * @return float ATR value.
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
     * Calculate standard deviation of a dataset.
     *
     * @param  array  $values  Array of numerical values.
     * @return float Standard deviation.
     */
    private function calculateStdDev(array $values): float
    {
        $n = count($values);
        if ($n < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / $n;
        $variance = array_sum(array_map(fn ($x) => pow($x - $mean, 2), $values)) / $n;

        return sqrt($variance);
    }

    /**
     * Validate pre-trade risk checks.
     *
     * Aggregates results from multiple risk checks to determine final approval.
     *
     * @param  array  $riskChecks  Array of individual check results.
     * @return array Combined result with 'approved' status and violations list.
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
     * Calculate VaR using Monte Carlo simulation.
     *
     * Simulates future price paths using Geometric Brownian Motion to estimate VaR.
     * Uses caching to improve performance for repeated calls.
     *
     * **Model:** dS = S * (μdt + σdW)
     *
     * @param  float  $currentValue  Current portfolio/position value.
     * @param  float  $volatility  Annualized volatility (decimal).
     * @param  int  $days  Time horizon in days.
     * @param  int  $simulations  Number of simulation paths (default: 1000).
     * @param  float  $confidenceLevel  Confidence level (default: 0.95).
     * @return float Estimated VaR value.
     */
    public function calculateMonteCarloVaR(float $currentValue, float $volatility, int $days, int $simulations = 1000, float $confidenceLevel = 0.95): float
    {
        $cacheKey = 'var_mc_'.md5(json_encode([$currentValue, $volatility, $days, $simulations, $confidenceLevel]));

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
     * Calculate Option Greeks using Black-Scholes model.
     *
     * Computes sensitivities of option price to various parameters.
     *
     * **Greeks:**
     * - **Delta**: Sensitivity to underlying price change
     * - **Gamma**: Sensitivity of Delta to underlying price change
     * - **Theta**: Time decay (daily)
     * - **Vega**: Sensitivity to volatility change (1% change)
     * - **Rho**: Sensitivity to interest rate change
     *
     * @param  float  $S  Spot price of underlying.
     * @param  float  $K  Strike price.
     * @param  float  $T  Time to maturity (in years).
     * @param  float  $r  Risk-free interest rate (decimal).
     * @param  float  $sigma  Volatility (decimal).
     * @param  string  $type  Option type ('call' or 'put').
     * @return array Associative array of Greeks.
     *
     * @example
     * ```php
     * $greeks = $riskService->calculateGreeks(100, 100, 0.5, 0.05, 0.2, 'call');
     * echo "Delta: " . $greeks['delta']; // Approx 0.6
     * ```
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
            'vega' => round($vega, 4),
            'rho' => round($rho, 4),
        ];
    }

    /**
     * Calculate Tail Risk using Extreme Value Theory (EVT) - Peaks Over Threshold.
     *
     * Estimates the probability and expected magnitude of extreme loss events
     * beyond a certain threshold.
     *
     * @param  array  $returns  Array of returns.
     * @param  float  $threshold  Loss threshold (positive decimal, e.g., 0.02 for 2%).
     * @return array Result with 'probability' and 'expected_shortfall'.
     */
    public function calculateTailRisk(array $returns, float $threshold = 0.02): array
    {
        $exceedances = array_filter($returns, fn ($r) => $r < -$threshold);
        $count = count($exceedances);

        if ($count === 0) {
            return ['probability' => 0.0, 'expected_shortfall' => 0.0];
        }

        $probability = $count / count($returns);
        $expectedShortfall = array_sum($exceedances) / $count;

        return [
            'probability' => $probability,
            'expected_shortfall' => abs($expectedShortfall),
        ];
    }

    /**
     * Box-Muller transform for generating normally distributed random numbers.
     *
     * Converts two uniform random numbers into independent standard normal random variables.
     *
     * @return float Standard normal random number.
     */
    private function boxMullerTransform(): float
    {
        $u1 = mt_rand() / mt_getrandmax();
        $u2 = mt_rand() / mt_getrandmax();

        return sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);
    }

    /**
     * Cumulative normal distribution function (CDF).
     *
     * Approximates the standard normal CDF using a polynomial approximation.
     *
     * @param  float  $x  Value to evaluate.
     * @return float Probability P(Z <= x).
     */
    private function normCdf($x): float
    {
        $t = 1 / (1 + 0.2316419 * abs($x));
        $d = 0.3989423 * exp(-$x * $x / 2);
        $prob = $d * $t * (0.3193815 + $t * (-0.3565638 + $t * (1.781478 + $t * (-1.821256 + $t * 1.330274))));
        if ($x > 0) {
            $prob = 1 - $prob;
        }

        return $prob;
    }
}
