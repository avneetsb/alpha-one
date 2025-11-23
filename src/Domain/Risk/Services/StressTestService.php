<?php

namespace TradingPlatform\Domain\Risk\Services;

use TradingPlatform\Domain\Risk\Calculators\VaRCalculator;

/**
 * Class StressTestService
 *
 * Performs stress testing and Monte Carlo simulations to assess portfolio resilience
 * under extreme market conditions. Essential for risk management and regulatory compliance.
 *
 * **Stress Testing:**
 * Applies predefined market shocks (e.g., -20% crash, COVID-level volatility) to
 * evaluate potential losses. Helps answer "What if?" questions about portfolio risk.
 *
 * **Monte Carlo Simulation:**
 * Generates thousands of random price paths using Geometric Brownian Motion to
 * estimate the distribution of potential outcomes and calculate VaR/CVaR.
 *
 * **Business Applications:**
 * - Regulatory stress testing (SEBI, Basel III requirements)
 * - Risk limit calibration
 * - Capital adequacy assessment
 * - Scenario planning for extreme events
 * - Portfolio optimization under stress
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Running a Market Crash Scenario
 * ```php
 * $stressTest = new StressTestService(new VaRCalculator());
 *
 * $positions = [
 *     ['symbol' => 'AAPL', 'quantity' => 100, 'current_price' => 150.0],
 *     ['symbol' => 'GOOGL', 'quantity' => 50, 'current_price' => 2800.0],
 * ];
 *
 * $result = $stressTest->runScenario('market_crash_20', $positions);
 *
 * echo "Scenario: {$result['scenario']}\n";
 * echo "Initial Value: ₹" . number_format($result['initial_value'], 2) . "\n";
 * echo "Stressed Value: ₹" . number_format($result['stressed_value'], 2) . "\n";
 * echo "Estimated Loss: ₹" . number_format($result['estimated_loss'], 2) . "\n";
 * echo "Loss %: " . round($result['loss_percentage'], 2) . "%\n";
 * ```
 * @example Monte Carlo Risk Assessment
 * ```php
 * $stressTest = new StressTestService(new VaRCalculator());
 *
 * // Run 10,000 simulations with 25% annualized volatility
 * $result = $stressTest->runMonteCarloSimulation(
 *     positions: $positions,
 *     volatility: 0.25,
 *     iterations: 10000,
 *     days: 1
 * );
 *
 * echo "95% VaR: ₹" . number_format($result['var_95'], 2) . "\n";
 * echo "95% CVaR: ₹" . number_format($result['cvar_95'], 2) . "\n";
 * echo "Worst Case: ₹" . number_format($result['worst_case'], 2) . "\n";
 * ```
 *
 * @see VaRCalculator For Value-at-Risk calculations
 * @see RiskLimitManager For enforcing stress test-based limits
 */
class StressTestService
{
    private VaRCalculator $varCalculator;

    public function __construct(VaRCalculator $varCalculator)
    {
        $this->varCalculator = $varCalculator;
    }

    /**
     * Run a predefined stress test scenario on a set of positions.
     *
     * Applies a market shock to all positions and calculates the resulting
     * portfolio value and loss. Useful for "what-if" analysis and regulatory
     * stress testing requirements.
     *
     * **Available Scenarios:**
     * - 'market_crash_20': -20% market decline (2020-style crash)
     * - 'market_correction_10': -10% correction
     * - 'black_monday': -22% (1987 Black Monday)
     * - 'covid_crash': -30% (March 2020 COVID crash)
     * - 'tech_bubble_burst': -40% (2000 dot-com bubble)
     * - 'bull_run_10': +10% (stress test for short positions)
     *
     * **Assumptions:**
     * - Shock applies uniformly to all assets (beta = 1)
     * - No correlation effects considered
     * - Instantaneous price movement (no liquidity constraints)
     *
     * **Use Cases:**
     * - Regulatory stress testing (SEBI requirements)
     * - Risk limit validation
     * - Portfolio resilience assessment
     * - Capital adequacy planning
     *
     * @param  string  $scenarioName  Name of the predefined scenario
     * @param  array  $positions  Array of positions, each with:
     *                            - 'symbol': Stock symbol
     *                            - 'quantity': Number of shares/contracts
     *                            - 'current_price': Current market price
     * @return array Stress test results containing:
     *               - 'scenario': Scenario name
     *               - 'shock_factor': Applied shock (e.g., -0.20 for -20%)
     *               - 'initial_value': Portfolio value before shock
     *               - 'stressed_value': Portfolio value after shock
     *               - 'estimated_loss': Absolute loss amount
     *               - 'loss_percentage': Loss as percentage of initial value
     *
     * @example Testing portfolio resilience
     * ```php
     * $positions = [
     *     ['symbol' => 'TCS', 'quantity' => 100, 'current_price' => 3500],
     *     ['symbol' => 'INFY', 'quantity' => 200, 'current_price' => 1450],
     * ];
     *
     * $result = $stressTest->runScenario('covid_crash', $positions);
     *
     * if ($result['loss_percentage'] > 25) {
     *     echo "WARNING: Portfolio would lose {$result['loss_percentage']}% in COVID scenario";
     * }
     * ```
     *
     * @note For more sophisticated analysis, consider asset-specific betas
     * @note Shock is applied instantaneously (no time dimension)
     */
    public function runScenario(string $scenarioName, array $positions): array
    {
        $shockFactor = $this->getScenarioShock($scenarioName);

        $totalLoss = 0;
        $initialValue = 0;
        $stressedValue = 0;

        foreach ($positions as $position) {
            $value = $position['quantity'] * $position['current_price'];
            $initialValue += $value;

            // Apply shock
            // For simplicity, we assume the shock applies to all assets equally (beta = 1)
            // In a real system, we would adjust by beta/correlation
            $stressedPrice = $position['current_price'] * (1 + $shockFactor);
            $stressedValue += $position['quantity'] * $stressedPrice;
        }

        $totalLoss = $initialValue - $stressedValue;

        return [
            'scenario' => $scenarioName,
            'shock_factor' => $shockFactor,
            'initial_value' => $initialValue,
            'stressed_value' => $stressedValue,
            'estimated_loss' => $totalLoss,
            'loss_percentage' => $initialValue > 0 ? ($totalLoss / $initialValue) * 100 : 0,
        ];
    }

    /**
     * Run a Monte Carlo simulation to estimate portfolio risk.
     *
     * Generates thousands of random price paths using Geometric Brownian Motion
     * to simulate potential portfolio outcomes. Calculates VaR, CVaR, and worst-case
     * scenarios from the distribution of simulated returns.
     *
     * **Methodology:**
     * Uses Geometric Brownian Motion (GBM) with the formula:
     * S_t = S_0 × exp((μ - 0.5σ²)t + σ√t × Z)
     *
     * Where:
     * - S_t = Price at time t
     * - S_0 = Initial price
     * - μ = Drift (assumed 0 for short horizons)
     * - σ = Volatility
     * - Z = Standard normal random variable (Box-Muller)
     *
     * **Output Metrics:**
     * - VaR 95%: Maximum loss at 95% confidence
     * - CVaR 95%: Expected loss in worst 5% of cases (Expected Shortfall)
     * - Worst Case: Maximum loss across all simulations
     *
     * **Use Cases:**
     * - Risk limit calibration
     * - Capital allocation decisions
     * - Regulatory capital requirements
     * - Portfolio optimization
     *
     * @param  array  $positions  Array of positions (same format as runScenario)
     * @param  float  $volatility  Annualized portfolio volatility (e.g., 0.25 for 25%)
     *                             Typical ranges: 0.15-0.30 for equity portfolios
     * @param  int  $iterations  Number of simulation paths (default: 1000)
     *                           Recommended: 1000 for quick analysis, 10000+ for precision
     * @param  int  $days  Time horizon in trading days (default: 1)
     *                     Use 1 for daily VaR, 10 for 2-week, 252 for annual
     * @return array Simulation results containing:
     *               - 'iterations': Number of paths simulated
     *               - 'horizon_days': Time horizon used
     *               - 'portfolio_value': Initial portfolio value
     *               - 'var_95': 95% Value at Risk
     *               - 'cvar_95': 95% Conditional VaR (Expected Shortfall)
     *               - 'worst_case': Maximum loss across all simulations
     *
     * @example Daily VaR calculation
     * ```php
     * // High-volatility tech portfolio
     * $result = $stressTest->runMonteCarloSimulation(
     *     positions: $techPositions,
     *     volatility: 0.35,  // 35% annualized volatility
     *     iterations: 5000,
     *     days: 1
     * );
     *
     * echo "Daily 95% VaR: ₹" . number_format($result['var_95'], 2);
     * echo "Expected loss in worst 5%: ₹" . number_format($result['cvar_95'], 2);
     * ```
     * @example Multi-day risk assessment
     * ```php
     * // 10-day VaR for holding period risk
     * $result = $stressTest->runMonteCarloSimulation(
     *     positions: $positions,
     *     volatility: 0.20,
     *     iterations: 10000,
     *     days: 10  // 2-week holding period
     * );
     * ```
     *
     * @note Assumes log-normal distribution of returns (GBM)
     * @note Drift (μ) is assumed to be 0 for short time horizons
     * @note Volatility is annualized and converted to daily using √252
     */
    public function runMonteCarloSimulation(array $positions, float $volatility, int $iterations = 1000, int $days = 1): array
    {
        $portfolioValue = array_reduce($positions, function ($carry, $item) {
            return $carry + ($item['quantity'] * $item['current_price']);
        }, 0.0);

        $dailyVol = $volatility / sqrt(252);
        $simulatedReturns = [];

        for ($i = 0; $i < $iterations; $i++) {
            // Geometric Brownian Motion: S_t = S_0 * exp((mu - 0.5 * sigma^2) * t + sigma * W_t)
            // Assuming mu (drift) = 0 for short horizons
            $randomShock = $this->boxMullerRandom();
            $simulatedReturn = exp(-0.5 * pow($dailyVol, 2) * $days + $dailyVol * sqrt($days) * $randomShock) - 1;
            $simulatedReturns[] = $simulatedReturn;
        }

        sort($simulatedReturns);

        // Calculate VaR 95%
        $varIndex = (int) floor(0.05 * $iterations);
        $var95Return = $simulatedReturns[$varIndex];
        $var95 = abs($var95Return * $portfolioValue);

        // Calculate CVaR 95% (Expected Shortfall)
        $tailLosses = array_slice($simulatedReturns, 0, $varIndex);
        $cvar95Return = ! empty($tailLosses) ? array_sum($tailLosses) / count($tailLosses) : 0;
        $cvar95 = abs($cvar95Return * $portfolioValue);

        return [
            'iterations' => $iterations,
            'horizon_days' => $days,
            'portfolio_value' => $portfolioValue,
            'var_95' => $var95,
            'cvar_95' => $cvar95,
            'worst_case' => abs($simulatedReturns[0] * $portfolioValue),
        ];
    }

    private function getScenarioShock(string $scenarioName): float
    {
        return match ($scenarioName) {
            'market_crash_20' => -0.20,
            'market_correction_10' => -0.10,
            'black_monday' => -0.22,
            'covid_crash' => -0.30,
            'tech_bubble_burst' => -0.40,
            'bull_run_10' => 0.10, // Stress testing for short positions
            default => 0.0,
        };
    }

    /**
     * Generate a standard normal random number using Box-Muller transform.
     *
     * Converts uniform random numbers into standard normal distribution (μ=0, σ=1)
     * using the Box-Muller transformation. Essential for Monte Carlo simulations.
     *
     * **Algorithm:**
     * Given two independent uniform random variables U1, U2 ~ U(0,1):
     * Z = √(-2 ln(U1)) × cos(2πU2)
     *
     * Results in Z ~ N(0,1) (standard normal distribution)
     *
     * @return float Random number from standard normal distribution N(0,1)
     *
     * @internal Used by runMonteCarloSimulation for generating random shocks
     *
     * @note For production, consider using a cryptographically secure RNG
     */
    private function boxMullerRandom(): float
    {
        $u1 = mt_rand() / mt_getrandmax();
        $u2 = mt_rand() / mt_getrandmax();

        return sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);
    }
}
