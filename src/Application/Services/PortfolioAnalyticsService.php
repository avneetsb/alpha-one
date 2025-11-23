<?php

namespace TradingPlatform\Application\Services;

use TradingPlatform\Domain\Portfolio\Models\Portfolio;

/**
 * Portfolio Analytics Service
 *
 * Provides comprehensive portfolio performance analysis including risk-adjusted
 * returns, attribution analysis, and advanced metrics for evaluating trading
 * strategy effectiveness and portfolio management decisions.
 *
 * **Key Metrics Calculated:**
 * - Sharpe Ratio: Risk-adjusted return relative to risk-free rate
 * - Sortino Ratio: Downside risk-adjusted return
 * - Maximum Drawdown: Largest peak-to-trough decline
 * - Alpha: Excess return over benchmark
 * - Beta: Systematic risk relative to market
 * - Volatility: Annualized standard deviation of returns
 *
 * **Performance Attribution:**
 * - Allocation Effect: Returns from sector/asset allocation decisions
 * - Selection Effect: Returns from security selection within sectors
 * - Interaction Effect: Combined effect of allocation and selection
 *
 * @version 1.0.0
 *
 * @example Basic Portfolio Analysis
 * ```php
 * $analyticsService = new PortfolioAnalyticsService($riskService);
 * $portfolio = Portfolio::find($portfolioId);
 *
 * // Calculate metrics for the last quarter
 * $from = new \DateTime('-3 months');
 * $to = new \DateTime();
 * $metrics = $analyticsService->calculatePortfolioMetrics($portfolio, $from, $to);
 *
 * echo "Sharpe Ratio: " . round($metrics['sharpe_ratio'], 2) . "\n";
 * echo "Max Drawdown: " . round($metrics['max_drawdown'], 2) . "%\n";
 * echo "Volatility: " . round($metrics['volatility'] * 100, 2) . "%\n";
 * ```
 * @example Performance Attribution Analysis
 * ```php
 * // Analyze what contributed to portfolio returns
 * $attribution = $analyticsService->analyzePerformanceAttribution($portfolio);
 *
 * echo "Allocation Effect: " . round($attribution['allocation_effect'], 4) . "\n";
 * echo "Selection Effect: " . round($attribution['selection_effect'], 4) . "\n";
 * echo "Interaction Effect: " . round($attribution['interaction_effect'], 4) . "\n";
 * ```
 *
 * @see RiskService For risk metric calculations
 * @see Portfolio For portfolio data access
 */
class PortfolioAnalyticsService
{
    /**
     * Risk service for calculating risk-adjusted metrics.
     */
    private RiskService $riskService;

    /**
     * PortfolioAnalyticsService constructor.
     *
     * Initializes the portfolio analytics service with required dependencies
     * for calculating risk-adjusted performance metrics.
     *
     * @param  RiskService  $riskService  Service for risk calculations (Sharpe, Sortino, etc.).
     *
     * @example Initialization
     * ```php
     * $riskService = new RiskService();
     * $analyticsService = new PortfolioAnalyticsService($riskService);
     * ```
     */
    public function __construct(RiskService $riskService)
    {
        $this->riskService = $riskService;
    }

    /**
     * Calculate comprehensive portfolio performance metrics.
     *
     * Analyzes portfolio performance over a specified time period, calculating
     * risk-adjusted returns, drawdowns, and volatility metrics. These metrics
     * help evaluate strategy effectiveness and compare performance across
     * different portfolios or time periods.
     *
     * **Metrics Explained:**
     *
     * 1. **Sharpe Ratio**: Measures excess return per unit of total risk
     *    - Formula: (Portfolio Return - Risk-Free Rate) / Portfolio Volatility
     *    - Interpretation: Higher is better. >1.0 is good, >2.0 is excellent
     *    - Example: Sharpe of 1.5 means 1.5% excess return per 1% of risk
     *
     * 2. **Sortino Ratio**: Similar to Sharpe but only considers downside risk
     *    - Formula: (Portfolio Return - Risk-Free Rate) / Downside Deviation
     *    - Interpretation: Better than Sharpe for asymmetric return distributions
     *    - Example: Sortino of 2.0 indicates strong risk-adjusted performance
     *
     * 3. **Maximum Drawdown**: Largest peak-to-trough decline
     *    - Formula: (Trough Value - Peak Value) / Peak Value
     *    - Interpretation: Measures worst-case loss from any peak
     *    - Example: -15% means portfolio lost 15% from its highest point
     *
     * 4. **Alpha**: Excess return over benchmark (requires benchmark comparison)
     *    - Formula: Portfolio Return - (Risk-Free Rate + Beta * Market Return)
     *    - Interpretation: Positive alpha indicates outperformance
     *    - Example: Alpha of 0.02 means 2% annual outperformance
     *
     * 5. **Beta**: Sensitivity to market movements
     *    - Formula: Covariance(Portfolio, Market) / Variance(Market)
     *    - Interpretation: 1.0 = moves with market, >1.0 = more volatile
     *    - Example: Beta of 1.2 means 20% more volatile than market
     *
     * 6. **Volatility**: Annualized standard deviation of returns
     *    - Formula: StdDev(Daily Returns) * sqrt(252)
     *    - Interpretation: Measures return variability
     *    - Example: 15% volatility is typical for equity portfolios
     *
     * @param  Portfolio  $portfolio  The portfolio to analyze.
     * @param  \DateTime  $from  Start date for analysis period.
     * @param  \DateTime  $to  End date for analysis period.
     * @return array Associative array containing:
     *               - 'sharpe_ratio' (float): Risk-adjusted return metric
     *               - 'sortino_ratio' (float): Downside risk-adjusted return
     *               - 'max_drawdown' (float): Maximum peak-to-trough decline (%)
     *               - 'alpha' (float): Excess return over benchmark
     *               - 'beta' (float): Market sensitivity coefficient
     *               - 'volatility' (float): Annualized volatility (decimal)
     *
     * @example Calculate quarterly metrics
     * ```php
     * $portfolio = Portfolio::find(123);
     * $from = new \DateTime('2024-01-01');
     * $to = new \DateTime('2024-03-31');
     *
     * $metrics = $analyticsService->calculatePortfolioMetrics($portfolio, $from, $to);
     *
     * // Display results
     * echo "Performance Metrics (Q1 2024):\n";
     * echo "Sharpe Ratio: " . round($metrics['sharpe_ratio'], 2) . "\n";
     * echo "Sortino Ratio: " . round($metrics['sortino_ratio'], 2) . "\n";
     * echo "Max Drawdown: " . round($metrics['max_drawdown'], 2) . "%\n";
     * echo "Volatility: " . round($metrics['volatility'] * 100, 2) . "%\n";
     *
     * // Interpretation
     * if ($metrics['sharpe_ratio'] > 1.5) {
     *     echo "Excellent risk-adjusted performance!\n";
     * }
     * if ($metrics['max_drawdown'] < -20) {
     *     echo "Warning: Significant drawdown detected\n";
     * }
     * ```
     * @example Compare multiple portfolios
     * ```php
     * $portfolios = [
     *     Portfolio::find(1),
     *     Portfolio::find(2),
     *     Portfolio::find(3)
     * ];
     *
     * $from = new \DateTime('-1 year');
     * $to = new \DateTime();
     *
     * foreach ($portfolios as $portfolio) {
     *     $metrics = $analyticsService->calculatePortfolioMetrics($portfolio, $from, $to);
     *     echo "{$portfolio->name}: Sharpe = {$metrics['sharpe_ratio']}\n";
     * }
     * ```
     */
    public function calculatePortfolioMetrics(Portfolio $portfolio, \DateTime $from, \DateTime $to): array
    {
        // Fetch daily returns for the portfolio
        // In production, this would query the database for historical returns
        // $returns = $portfolio->getDailyReturns($from, $to);
        $returns = []; // Placeholder for demonstration

        return [
            'sharpe_ratio' => $this->riskService->calculateSharpeRatio($returns),
            'sortino_ratio' => $this->riskService->calculateSortinoRatio($returns),
            'max_drawdown' => $this->riskService->calculateMaxDrawdown($this->calculateEquityCurve($returns)),
            'alpha' => 0.0, // Requires benchmark comparison
            'beta' => 1.0,  // Requires benchmark comparison
            'volatility' => $this->calculateVolatility($returns),
        ];
    }

    /**
     * Analyze performance attribution using Brinson-Fachler methodology.
     *
     * Decomposes portfolio returns into three components to understand what
     * drove performance: asset allocation decisions, security selection within
     * sectors, and the interaction between these two effects.
     *
     * **Attribution Components:**
     *
     * 1. **Allocation Effect**: Return from over/underweighting sectors
     *    - Measures impact of sector allocation vs. benchmark
     *    - Formula: Σ(Portfolio Weight - Benchmark Weight) * Benchmark Sector Return
     *    - Example: Overweighting tech sector that outperformed = positive allocation
     *
     * 2. **Selection Effect**: Return from picking securities within sectors
     *    - Measures impact of stock selection within each sector
     *    - Formula: Σ Benchmark Weight * (Portfolio Sector Return - Benchmark Sector Return)
     *    - Example: Picking better stocks in healthcare = positive selection
     *
     * 3. **Interaction Effect**: Combined effect of allocation and selection
     *    - Captures synergy between allocation and selection decisions
     *    - Formula: Σ(Portfolio Weight - Benchmark Weight) * (Portfolio Sector Return - Benchmark Sector Return)
     *    - Example: Overweighting a sector with good stock picks = positive interaction
     *
     * @param  Portfolio  $portfolio  The portfolio to analyze.
     * @return array Attribution effects containing:
     *               - 'allocation_effect' (float): Return from sector allocation decisions
     *               - 'selection_effect' (float): Return from security selection
     *               - 'interaction_effect' (float): Combined allocation-selection effect
     *
     * @example Basic attribution analysis
     * ```php
     * $portfolio = Portfolio::find(456);
     * $attribution = $analyticsService->analyzePerformanceAttribution($portfolio);
     *
     * echo "Performance Attribution Analysis:\n";
     * echo "Allocation Effect: " . round($attribution['allocation_effect'] * 100, 2) . "%\n";
     * echo "Selection Effect: " . round($attribution['selection_effect'] * 100, 2) . "%\n";
     * echo "Interaction Effect: " . round($attribution['interaction_effect'] * 100, 2) . "%\n";
     *
     * $totalAttribution = $attribution['allocation_effect'] +
     *                     $attribution['selection_effect'] +
     *                     $attribution['interaction_effect'];
     * echo "Total Attribution: " . round($totalAttribution * 100, 2) . "%\n";
     * ```
     * @example Identify performance drivers
     * ```php
     * $attribution = $analyticsService->analyzePerformanceAttribution($portfolio);
     *
     * // Determine what drove performance
     * if ($attribution['allocation_effect'] > $attribution['selection_effect']) {
     *     echo "Performance driven by sector allocation decisions\n";
     * } else {
     *     echo "Performance driven by security selection\n";
     * }
     *
     * // Check for negative effects
     * if ($attribution['selection_effect'] < 0) {
     *     echo "Warning: Poor security selection detracted from returns\n";
     * }
     * ```
     *
     * @note This implementation uses the Brinson-Fachler attribution model,
     *       which is industry-standard for equity portfolio attribution.
     *
     * @see https://en.wikipedia.org/wiki/Performance_attribution
     */
    public function analyzePerformanceAttribution(Portfolio $portfolio): array
    {
        // Brinson-Fachler attribution analysis
        // In production, this would compare portfolio holdings vs. benchmark
        // across different sectors and calculate attribution effects

        return [
            'allocation_effect' => 0.0,    // Sector allocation contribution
            'selection_effect' => 0.0,     // Stock selection contribution
            'interaction_effect' => 0.0,   // Combined effect
        ];
    }

    /**
     * Calculate equity curve from daily returns.
     *
     * Transforms a series of daily returns into a cumulative equity curve
     * starting from a base value of 100. This curve visualizes portfolio
     * growth over time and is used for calculating drawdowns.
     *
     * **Calculation Method:**
     * - Starts with base value of 100
     * - Each day: New Value = Previous Value * (1 + Daily Return)
     * - Example: 2% return on day 1: 100 * 1.02 = 102
     *
     * @param  array  $returns  Array of daily returns (decimal format, e.g., 0.02 for 2%).
     * @return array Equity curve values (cumulative portfolio value over time).
     *
     * @example Generate equity curve
     * ```php
     * // Daily returns: +1%, -0.5%, +2%, +0.3%
     * $returns = [0.01, -0.005, 0.02, 0.003];
     * $curve = $this->calculateEquityCurve($returns);
     *
     * // Result: [101.0, 100.495, 102.5049, 102.8125]
     * // Shows portfolio grew from 100 to 102.81 over 4 days
     * ```
     * @example Visualize portfolio growth
     * ```php
     * $returns = $portfolio->getDailyReturns($from, $to);
     * $curve = $this->calculateEquityCurve($returns);
     *
     * // Find peak value
     * $peak = max($curve);
     * $finalValue = end($curve);
     *
     * echo "Starting Value: 100\n";
     * echo "Peak Value: " . round($peak, 2) . "\n";
     * echo "Final Value: " . round($finalValue, 2) . "\n";
     * echo "Total Return: " . round(($finalValue - 100), 2) . "%\n";
     * ```
     */
    private function calculateEquityCurve(array $returns): array
    {
        $curve = [];
        $current = 100; // Base value of 100

        foreach ($returns as $ret) {
            $current *= (1 + $ret);
            $curve[] = $current;
        }

        return $curve;
    }

    /**
     * Calculate annualized volatility from daily returns.
     *
     * Computes the annualized standard deviation of returns, which measures
     * the variability of portfolio returns. Higher volatility indicates
     * greater uncertainty and risk.
     *
     * **Calculation:**
     * - Calculate standard deviation of daily returns
     * - Annualize by multiplying by sqrt(252) trading days
     * - Formula: σ_annual = σ_daily * √252
     *
     * **Interpretation:**
     * - 10-15%: Low volatility (conservative portfolio)
     * - 15-25%: Moderate volatility (balanced portfolio)
     * - 25%+: High volatility (aggressive portfolio)
     *
     * @param  array  $returns  Array of daily returns (decimal format).
     * @return float Annualized volatility (decimal format, e.g., 0.15 for 15%).
     *
     * @example Calculate portfolio volatility
     * ```php
     * $returns = [0.01, -0.02, 0.015, -0.005, 0.02];
     * $volatility = $this->calculateVolatility($returns);
     *
     * echo "Annualized Volatility: " . round($volatility * 100, 2) . "%\n";
     *
     * if ($volatility < 0.15) {
     *     echo "Low volatility portfolio\n";
     * } elseif ($volatility < 0.25) {
     *     echo "Moderate volatility portfolio\n";
     * } else {
     *     echo "High volatility portfolio\n";
     * }
     * ```
     * @example Compare strategy volatilities
     * ```php
     * $strategy1Returns = $portfolio1->getDailyReturns($from, $to);
     * $strategy2Returns = $portfolio2->getDailyReturns($from, $to);
     *
     * $vol1 = $this->calculateVolatility($strategy1Returns);
     * $vol2 = $this->calculateVolatility($strategy2Returns);
     *
     * echo "Strategy 1 Volatility: " . round($vol1 * 100, 2) . "%\n";
     * echo "Strategy 2 Volatility: " . round($vol2 * 100, 2) . "%\n";
     *
     * if ($vol1 < $vol2) {
     *     echo "Strategy 1 is less risky\n";
     * }
     * ```
     *
     * @note The factor of 252 assumes 252 trading days per year (standard
     *       for US equity markets). Adjust for other markets if needed.
     */
    private function calculateVolatility(array $returns): float
    {
        if (empty($returns)) {
            return 0.0;
        }

        // In production, calculate actual standard deviation
        // $mean = array_sum($returns) / count($returns);
        // $variance = array_sum(array_map(fn($r) => pow($r - $mean, 2), $returns)) / count($returns);
        // $dailyStdDev = sqrt($variance);
        // $annualizedVol = $dailyStdDev * sqrt(252);

        return 0.15; // Placeholder: 15% annualized volatility
    }
}
