<?php

namespace TradingPlatform\Domain\Risk\Calculators;

/**
 * Class VaRCalculator
 *
 * Calculates Value at Risk (VaR) - a statistical measure of the potential loss
 * in portfolio value over a specific time period at a given confidence level.
 *
 * This calculator supports two industry-standard VaR calculation methods:
 * 1. Historical Simulation - Uses actual historical returns distribution
 * 2. Parametric (Variance-Covariance) - Assumes normal distribution of returns
 *
 * **Business Context:**
 * VaR is used for:
 * - Risk limit enforcement (e.g., "Daily VaR must not exceed ₹50,000")
 * - Regulatory reporting (Basel III compliance)
 * - Portfolio optimization and capital allocation
 * - Pre-trade risk assessment
 *
 * **Interpretation:**
 * A 95% VaR of ₹10,000 means: "We are 95% confident that our portfolio
 * will not lose more than ₹10,000 in the next trading day."
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Basic Historical VaR Calculation
 * ```php
 * $varCalc = new VaRCalculator();
 *
 * // Historical daily returns for the past 100 days (as decimals)
 * $returns = [-0.02, 0.01, -0.015, 0.03, -0.01, 0.02, ...];
 *
 * // Calculate 95% confidence VaR for a ₹1,000,000 portfolio
 * $var95 = $varCalc->calculateHistoricalVaR(
 *     returns: $returns,
 *     confidenceLevel: 0.95,
 *     portfolioValue: 1000000
 * );
 *
 * echo "95% Daily VaR: ₹" . number_format($var95, 2);
 * // Output: 95% Daily VaR: ₹20,000.00
 * // Interpretation: 95% confident we won't lose more than ₹20k tomorrow
 * ```
 * @example Parametric VaR for Normal Distribution
 * ```php
 * $varCalc = new VaRCalculator();
 *
 * // Portfolio statistics
 * $meanReturn = 0.001;  // 0.1% average daily return
 * $stdDev = 0.015;      // 1.5% daily volatility
 * $portfolioValue = 5000000;
 *
 * // Calculate 99% confidence VaR
 * $var99 = $varCalc->calculateParametricVaR(
 *     mean: $meanReturn,
 *     stdDev: $stdDev,
 *     confidenceLevel: 0.99,
 *     portfolioValue: $portfolioValue
 * );
 *
 * echo "99% Daily VaR: ₹" . number_format($var99, 2);
 * // Output: 99% Daily VaR: ₹116,300.00
 * ```
 * @example Risk Limit Enforcement
 * ```php
 * $varCalc = new VaRCalculator();
 * $riskLimit = 50000; // Maximum allowed VaR
 *
 * $currentVaR = $varCalc->calculateHistoricalVaR($returns, 0.95, $portfolioValue);
 *
 * if ($currentVaR > $riskLimit) {
 *     throw new RiskLimitExceededException(
 *         "VaR of ₹{$currentVaR} exceeds limit of ₹{$riskLimit}"
 *     );
 * }
 * ```
 *
 * @see https://en.wikipedia.org/wiki/Value_at_risk
 * @see RiskLimitManager For enforcing VaR-based risk limits
 * @see StressTestService For scenario-based risk analysis
 */
class VaRCalculator
{
    /**
     * Calculate Value at Risk using Historical Simulation method.
     *
     * This non-parametric method uses the actual distribution of historical
     * returns without assuming any specific distribution (e.g., normal).
     * It's more accurate when returns are not normally distributed.
     *
     * **Algorithm:**
     * 1. Sort historical returns from worst to best
     * 2. Find the return at the (1 - confidence level) percentile
     * 3. Multiply by portfolio value to get VaR in currency units
     *
     * **Advantages:**
     * - No distribution assumptions
     * - Captures fat tails and skewness
     * - Easy to understand and explain
     *
     * **Limitations:**
     * - Requires sufficient historical data (typically 250+ observations)
     * - Assumes future will resemble the past
     * - Cannot predict unprecedented events
     *
     * @param  array  $returns  Array of historical returns as decimals (e.g., -0.02 for -2%)
     *                          Minimum 20 observations recommended, 250+ ideal
     * @param  float  $confidenceLevel  Confidence level between 0 and 1 (e.g., 0.95 for 95%)
     *                                  Common values: 0.90, 0.95, 0.99
     * @param  float  $portfolioValue  Current portfolio value in currency units
     * @return float VaR amount in currency units (always positive)
     *
     * @example Calculate 95% VaR with 100 days of returns
     * ```php
     * $returns = [-0.03, -0.01, 0.02, 0.01, -0.02, ...]; // 100 values
     * $var = $varCalc->calculateHistoricalVaR($returns, 0.95, 1000000);
     * // Returns: 25000.0 (meaning ₹25,000 at 95% confidence)
     * ```
     *
     * @note Returns empty array handling: Returns 0.0 to prevent division errors
     * @note The method uses floor() for index calculation, which is conservative
     */
    public function calculateHistoricalVaR(array $returns, float $confidenceLevel, float $portfolioValue): float
    {
        if (empty($returns)) {
            return 0.0;
        }

        sort($returns);

        // Find the percentile index (e.g., 5th percentile for 95% confidence)
        $index = (int) floor((1 - $confidenceLevel) * count($returns));
        $varPercentage = abs($returns[$index]);

        return $portfolioValue * $varPercentage;
    }

    /**
     * Calculate Value at Risk using Variance-Covariance (Parametric) method.
     *
     * This parametric method assumes returns follow a normal distribution
     * and uses the mean and standard deviation to calculate VaR analytically.
     *
     * **Formula:**
     * VaR = Portfolio Value × (Z-score × σ - μ)
     * Where:
     * - Z-score = Standard normal quantile for confidence level
     * - σ = Standard deviation of returns
     * - μ = Mean return
     *
     * **Advantages:**
     * - Requires less data than historical simulation
     * - Computationally efficient
     * - Easy to scale to multi-asset portfolios
     *
     * **Limitations:**
     * - Assumes normal distribution (may underestimate tail risk)
     * - Not suitable for options or non-linear instruments
     * - May not capture extreme market events
     *
     * @param  float  $mean  Average return as decimal (e.g., 0.001 for 0.1% daily return)
     * @param  float  $stdDev  Standard deviation of returns as decimal (e.g., 0.02 for 2% volatility)
     * @param  float  $confidenceLevel  Confidence level between 0 and 1 (e.g., 0.95 for 95%)
     * @param  float  $portfolioValue  Current portfolio value in currency units
     * @return float VaR amount in currency units (always positive)
     *
     * @example Calculate VaR for a volatile portfolio
     * ```php
     * $mean = 0.0005;    // 0.05% average daily return
     * $stdDev = 0.025;   // 2.5% daily volatility (high)
     * $var = $varCalc->calculateParametricVaR($mean, $stdDev, 0.95, 2000000);
     * // Returns: ~82,250 (₹82,250 at 95% confidence)
     * ```
     *
     * @note Uses simplified Z-score lookup table for common confidence levels
     * @note Returns max(0, result) to ensure non-negative VaR
     */
    public function calculateParametricVaR(float $mean, float $stdDev, float $confidenceLevel, float $portfolioValue): float
    {
        // Z-score for confidence level (assuming normal distribution)
        // 0.95 -> 1.645, 0.99 -> 2.326
        $zScore = $this->getZScore($confidenceLevel);

        $varPercentage = ($zScore * $stdDev) - $mean;

        return $portfolioValue * max(0, $varPercentage);
    }

    /**
     * Get Z-score (standard normal quantile) for a given confidence level.
     *
     * Maps confidence levels to their corresponding Z-scores from the
     * standard normal distribution. Uses a lookup table for common values.
     *
     * **Z-Score Reference:**
     * - 90% confidence → Z = 1.282
     * - 95% confidence → Z = 1.645
     * - 99% confidence → Z = 2.326
     *
     * @param  float  $confidenceLevel  Confidence level between 0 and 1
     * @return float Z-score for the confidence level
     *
     * @note For production use, consider implementing inverse normal CDF
     *       for arbitrary confidence levels
     * @note Returns 0.0 for confidence levels below 90% (not recommended)
     *
     * @internal This is a simplified implementation. For more precision,
     *           use a statistical library with inverse normal distribution.
     */
    private function getZScore(float $confidenceLevel): float
    {
        // Simple lookup for common confidence levels
        // For a full implementation, use an inverse normal distribution function
        if ($confidenceLevel >= 0.99) {
            return 2.326;
        }
        if ($confidenceLevel >= 0.95) {
            return 1.645;
        }
        if ($confidenceLevel >= 0.90) {
            return 1.282;
        }

        return 0.0;
    }
}
