<?php

namespace TradingPlatform\Domain\Risk\Calculators;

/**
 * Class CorrelationCalculator
 *
 * Calculates correlation coefficients between asset returns to measure how assets
 * move together. Correlation is a key metric for portfolio diversification and
 * risk management.
 *
 * **Correlation Interpretation:**
 * - +1.0: Perfect positive correlation (assets move together)
 * - 0.0: No correlation (assets move independently)
 * - -1.0: Perfect negative correlation (assets move opposite)
 *
 * **Business Applications:**
 * - Portfolio diversification analysis
 * - Risk limit enforcement (e.g., "Max correlation between positions: 0.7")
 * - Pair trading strategy identification
 * - Hedging effectiveness measurement
 * - Sector concentration risk assessment
 *
 * **Statistical Method:**
 * Uses Pearson correlation coefficient, which measures linear relationships
 * between two variables. Assumes returns are normally distributed.
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Basic Correlation Calculation
 * ```php
 * $corrCalc = new CorrelationCalculator();
 *
 * // Daily returns for two tech stocks (30 days)
 * $aaplReturns = [0.02, -0.01, 0.03, -0.02, 0.01, ...];
 * $msftReturns = [0.015, -0.008, 0.025, -0.015, 0.012, ...];
 *
 * $correlation = $corrCalc->calculateCorrelation($aaplReturns, $msftReturns);
 * echo "AAPL-MSFT Correlation: " . round($correlation, 3);
 * // Output: AAPL-MSFT Correlation: 0.847
 * // Interpretation: Highly correlated - both move together
 * ```
 * @example Portfolio Correlation Matrix
 * ```php
 * $corrCalc = new CorrelationCalculator();
 *
 * $portfolioReturns = [
 *     'AAPL' => [0.02, -0.01, 0.03, ...],
 *     'GOOGL' => [0.015, -0.012, 0.028, ...],
 *     'TCS' => [0.01, 0.005, -0.008, ...],
 *     'INFY' => [0.012, 0.008, -0.005, ...],
 * ];
 *
 * $matrix = $corrCalc->calculateCorrelationMatrix($portfolioReturns);
 *
 * // Check diversification
 * foreach ($matrix as $asset1 => $correlations) {
 *     foreach ($correlations as $asset2 => $corr) {
 *         if ($asset1 !== $asset2 && $corr > 0.8) {
 *             echo "Warning: {$asset1} and {$asset2} highly correlated ({$corr})\n";
 *         }
 *     }
 * }
 * ```
 * @example Risk Limit Enforcement
 * ```php
 * $corrCalc = new CorrelationCalculator();
 * $maxAllowedCorrelation = 0.7;
 *
 * $correlation = $corrCalc->calculateCorrelation($position1Returns, $position2Returns);
 *
 * if (abs($correlation) > $maxAllowedCorrelation) {
 *     throw new RiskLimitException(
 *         "Correlation {$correlation} exceeds limit {$maxAllowedCorrelation}. " .
 *         "Positions are too concentrated."
 *     );
 * }
 * ```
 *
 * @see https://en.wikipedia.org/wiki/Pearson_correlation_coefficient
 * @see RiskLimitManager For enforcing correlation-based risk limits
 * @see VaRCalculator For portfolio-level risk measurement
 */
class CorrelationCalculator
{
    /**
     * Calculate Pearson correlation coefficient between two series of returns.
     *
     * Measures the strength and direction of the linear relationship between
     * two assets. The result ranges from -1 (perfect negative correlation) to
     * +1 (perfect positive correlation).
     *
     * **Formula:**
     * ρ(X,Y) = Cov(X,Y) / (σ_X × σ_Y)
     *
     * Where:
     * - Cov(X,Y) = Covariance between X and Y
     * - σ_X, σ_Y = Standard deviations of X and Y
     *
     * **Use Cases:**
     * - Measuring diversification benefit between two positions
     * - Identifying pairs for pair trading strategies
     * - Assessing hedge effectiveness
     * - Detecting sector concentration risk
     *
     * **Interpretation Guide:**
     * - 0.8 to 1.0: Very strong positive correlation
     * - 0.6 to 0.8: Strong positive correlation
     * - 0.4 to 0.6: Moderate positive correlation
     * - 0.2 to 0.4: Weak positive correlation
     * - -0.2 to 0.2: Little to no correlation
     * - Similar ranges for negative values
     *
     * @param  array  $returnsA  Array of returns for asset A (as decimals, e.g., 0.02 for 2%)
     *                           Minimum 20 observations recommended for statistical significance
     * @param  array  $returnsB  Array of returns for asset B (must match length of returnsA)
     * @return float Correlation coefficient between -1.0 and 1.0
     *
     * @throws \InvalidArgumentException If arrays have different lengths
     * @throws \InvalidArgumentException If insufficient data points (< 2)
     *
     * @example Detecting highly correlated tech stocks
     * ```php
     * $aapl = [0.02, -0.01, 0.03, -0.015, 0.025];
     * $msft = [0.018, -0.012, 0.028, -0.014, 0.023];
     *
     * $corr = $corrCalc->calculateCorrelation($aapl, $msft);
     * // Returns: ~0.99 (almost perfect correlation)
     * ```
     * @example Checking hedge effectiveness
     * ```php
     * $stockReturns = [0.03, -0.02, 0.04, -0.01];
     * $hedgeReturns = [-0.025, 0.018, -0.035, 0.012];
     *
     * $corr = $corrCalc->calculateCorrelation($stockReturns, $hedgeReturns);
     * // Returns: ~-0.98 (excellent negative correlation = good hedge)
     * ```
     *
     * @note Returns 0.0 if standard deviation of either series is zero (constant values)
     * @note Assumes linear relationships - may not capture non-linear dependencies
     */
    public function calculateCorrelation(array $returnsA, array $returnsB): float
    {
        $n = count($returnsA);

        if ($n !== count($returnsB)) {
            throw new \InvalidArgumentException('Return series must have the same length');
        }

        if ($n < 2) {
            throw new \InvalidArgumentException('Insufficient data points for correlation');
        }

        $meanA = array_sum($returnsA) / $n;
        $meanB = array_sum($returnsB) / $n;

        $sumAB = 0;
        $sumSqA = 0;
        $sumSqB = 0;

        for ($i = 0; $i < $n; $i++) {
            $diffA = $returnsA[$i] - $meanA;
            $diffB = $returnsB[$i] - $meanB;

            $sumAB += $diffA * $diffB;
            $sumSqA += $diffA * $diffA;
            $sumSqB += $diffB * $diffB;
        }

        $denominator = sqrt($sumSqA * $sumSqB);

        if ($denominator == 0) {
            return 0.0;
        }

        return $sumAB / $denominator;
    }

    /**
     * Calculate correlation matrix for multiple assets.
     *
     * Generates a symmetric matrix showing pairwise correlations between all
     * assets in a portfolio. This is essential for understanding portfolio
     * diversification and concentration risk.
     *
     * **Matrix Properties:**
     * - Symmetric: corr(A,B) = corr(B,A)
     * - Diagonal is always 1.0 (asset perfectly correlates with itself)
     * - Off-diagonal values show inter-asset correlations
     *
     * **Business Applications:**
     * - Portfolio diversification analysis
     * - Identifying concentrated sector exposure
     * - Optimizing asset allocation
     * - Risk factor decomposition
     *
     * **Performance Optimization:**
     * The method calculates each unique pair only once, leveraging the
     * symmetric property to reduce computations by ~50%.
     *
     * @param  array  $portfolioReturns  Associative array of asset returns
     *                                   Format: ['SYMBOL' => [return1, return2, ...], ...]
     *                                   All return arrays must have the same length
     * @return array Correlation matrix as nested associative array
     *               Format: ['AAPL' => ['AAPL' => 1.0, 'GOOGL' => 0.75, ...], ...]
     *
     * @example Analyzing a 4-stock portfolio
     * ```php
     * $returns = [
     *     'AAPL'  => [0.02, -0.01, 0.03, -0.02],
     *     'GOOGL' => [0.018, -0.012, 0.028, -0.018],
     *     'TCS'   => [0.01, 0.005, -0.008, 0.012],
     *     'INFY'  => [0.012, 0.008, -0.005, 0.015],
     * ];
     *
     * $matrix = $corrCalc->calculateCorrelationMatrix($returns);
     *
     * // Access specific correlation
     * $aaplGoogleCorr = $matrix['AAPL']['GOOGL'];
     * echo "AAPL-GOOGL: " . round($aaplGoogleCorr, 3);
     *
     * // Find most correlated pair
     * $maxCorr = 0;
     * $pair = '';
     * foreach ($matrix as $asset1 => $correlations) {
     *     foreach ($correlations as $asset2 => $corr) {
     *         if ($asset1 !== $asset2 && $corr > $maxCorr) {
     *             $maxCorr = $corr;
     *             $pair = "{$asset1}-{$asset2}";
     *         }
     *     }
     * }
     * echo "Most correlated: {$pair} ({$maxCorr})";
     * ```
     * @example Checking diversification quality
     * ```php
     * $matrix = $corrCalc->calculateCorrelationMatrix($portfolioReturns);
     *
     * $avgCorrelation = 0;
     * $count = 0;
     *
     * foreach ($matrix as $asset1 => $correlations) {
     *     foreach ($correlations as $asset2 => $corr) {
     *         if ($asset1 !== $asset2) {
     *             $avgCorrelation += abs($corr);
     *             $count++;
     *         }
     *     }
     * }
     *
     * $avgCorrelation /= $count;
     *
     * if ($avgCorrelation > 0.7) {
     *     echo "Warning: Portfolio is poorly diversified (avg corr: {$avgCorrelation})";
     * } else {
     *     echo "Good diversification (avg corr: {$avgCorrelation})";
     * }
     * ```
     *
     * @note The matrix is symmetric, so corr(A,B) = corr(B,A)
     * @note Diagonal values are always 1.0 (perfect self-correlation)
     * @note Complexity: O(n²) where n is the number of assets
     */
    public function calculateCorrelationMatrix(array $portfolioReturns): array
    {
        $assets = array_keys($portfolioReturns);
        $matrix = [];

        foreach ($assets as $assetA) {
            foreach ($assets as $assetB) {
                if ($assetA === $assetB) {
                    $matrix[$assetA][$assetB] = 1.0;
                } else {
                    // Only calculate if not already calculated (symmetric matrix)
                    if (! isset($matrix[$assetB][$assetA])) {
                        $correlation = $this->calculateCorrelation(
                            $portfolioReturns[$assetA],
                            $portfolioReturns[$assetB]
                        );
                        $matrix[$assetA][$assetB] = $correlation;
                    } else {
                        $matrix[$assetA][$assetB] = $matrix[$assetB][$assetA];
                    }
                }
            }
        }

        return $matrix;
    }
}
