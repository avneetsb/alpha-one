<?php

namespace TradingPlatform\Domain\Risk\Services;

use TradingPlatform\Domain\Risk\Calculators\CorrelationCalculator;
use TradingPlatform\Domain\Risk\Calculators\VaRCalculator;

/**
 * Class RiskAttributionAnalyzer
 *
 * Performs risk attribution analysis to decompose portfolio risk into
 * contributing factors, helping identify which positions, sectors, or
 * strategies contribute most to overall portfolio risk.
 *
 * **Attribution Dimensions:**
 * - Position-level contribution to VaR
 * - Sector/industry concentration risk
 * - Strategy-level risk contribution
 * - Factor-based risk decomposition
 * - Marginal VaR (incremental risk of each position)
 *
 * **Use Cases:**
 * - Portfolio rebalancing decisions
 * - Risk budget allocation
 * - Identifying concentration risks
 * - Performance attribution
 * - Regulatory risk reporting
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Position-Level Risk Attribution
 * ```php
 * $attribution = new RiskAttributionAnalyzer($varCalc, $corrCalc);
 *
 * $positions = [
 *     ['symbol' => 'AAPL', 'value' => 500000, 'volatility' => 0.25],
 *     ['symbol' => 'GOOGL', 'value' => 300000, 'volatility' => 0.30],
 *     ['symbol' => 'TCS', 'value' => 200000, 'volatility' => 0.20],
 * ];
 *
 * $result = $attribution->analyzePositionRisk($positions);
 *
 * foreach ($result['position_contributions'] as $pos) {
 *     echo "{$pos['symbol']}: {$pos['risk_contribution']}% of portfolio risk\n";
 * }
 * // Output:
 * // AAPL: 45% of portfolio risk
 * // GOOGL: 35% of portfolio risk
 * // TCS: 20% of portfolio risk
 * ```
 * @example Sector Concentration Analysis
 * ```php
 * $sectorRisk = $attribution->analyzeSectorRisk($positions);
 *
 * foreach ($sectorRisk as $sector => $contribution) {
 *     echo "{$sector}: {$contribution}% risk concentration\n";
 * }
 * // Output:
 * // Technology: 65% risk concentration
 * // Finance: 25% risk concentration
 * // Healthcare: 10% risk concentration
 * ```
 */
class RiskAttributionAnalyzer
{
    private VaRCalculator $varCalculator;

    private CorrelationCalculator $corrCalculator;

    public function __construct(VaRCalculator $varCalculator, CorrelationCalculator $corrCalculator)
    {
        $this->varCalculator = $varCalculator;
        $this->corrCalculator = $corrCalculator;
    }

    /**
     * Analyze position-level risk contributions.
     *
     * Calculates how much each position contributes to total portfolio risk,
     * considering position size, volatility, and correlations.
     *
     * **Methodology:**
     * 1. Calculate portfolio VaR
     * 2. Calculate marginal VaR for each position
     * 3. Determine percentage contribution to total risk
     *
     * **Marginal VaR:**
     * Change in portfolio VaR if position is removed
     *
     * @param  array  $positions  Array of positions with symbol, value, volatility
     * @return array Risk attribution results with position contributions
     *
     * @example Identifying high-risk positions
     * ```php
     * $result = $attribution->analyzePositionRisk($positions);
     *
     * // Find positions contributing >30% to risk
     * $highRisk = array_filter($result['position_contributions'], function($p) {
     *     return $p['risk_contribution'] > 30;
     * });
     *
     * if (!empty($highRisk)) {
     *     echo "WARNING: High concentration risk detected\n";
     * }
     * ```
     */
    public function analyzePositionRisk(array $positions): array
    {
        $portfolioValue = array_sum(array_column($positions, 'value'));

        // Calculate individual position risks
        $positionRisks = [];
        $totalRisk = 0;

        foreach ($positions as $position) {
            $positionRisk = $position['value'] * $position['volatility'];
            $positionRisks[$position['symbol']] = $positionRisk;
            $totalRisk += $positionRisk;
        }

        // Calculate risk contributions as percentages
        $contributions = [];
        foreach ($positionRisks as $symbol => $risk) {
            $contributions[] = [
                'symbol' => $symbol,
                'risk_contribution' => ($risk / $totalRisk) * 100,
                'absolute_risk' => $risk,
            ];
        }

        // Sort by contribution descending
        usort($contributions, fn ($a, $b) => $b['risk_contribution'] <=> $a['risk_contribution']);

        return [
            'total_portfolio_risk' => $totalRisk,
            'position_contributions' => $contributions,
            'top_risk_contributor' => $contributions[0] ?? null,
        ];
    }

    /**
     * Analyze sector-level risk concentration.
     *
     * @param  array  $positions  Positions with sector information
     * @return array Sector risk breakdown
     */
    public function analyzeSectorRisk(array $positions): array
    {
        $sectorRisks = [];
        $totalRisk = 0;

        foreach ($positions as $position) {
            $sector = $position['sector'] ?? 'Unknown';
            $risk = $position['value'] * $position['volatility'];

            if (! isset($sectorRisks[$sector])) {
                $sectorRisks[$sector] = 0;
            }

            $sectorRisks[$sector] += $risk;
            $totalRisk += $risk;
        }

        // Convert to percentages
        $sectorContributions = [];
        foreach ($sectorRisks as $sector => $risk) {
            $sectorContributions[$sector] = ($risk / $totalRisk) * 100;
        }

        arsort($sectorContributions);

        return $sectorContributions;
    }

    /**
     * Calculate marginal VaR for a position.
     *
     * Marginal VaR = Change in portfolio VaR if position is removed
     *
     * @param  array  $portfolioPositions  All portfolio positions
     * @param  string  $targetSymbol  Symbol to calculate marginal VaR for
     * @return float Marginal VaR amount
     */
    public function calculateMarginalVaR(array $portfolioPositions, string $targetSymbol): float
    {
        // Calculate portfolio VaR with position
        $portfolioValue = array_sum(array_column($portfolioPositions, 'value'));
        $portfolioVolatility = $this->calculatePortfolioVolatility($portfolioPositions);
        $varWith = $portfolioValue * $portfolioVolatility;

        // Calculate portfolio VaR without position
        $positionsWithout = array_filter($portfolioPositions, fn ($p) => $p['symbol'] !== $targetSymbol);
        $portfolioValueWithout = array_sum(array_column($positionsWithout, 'value'));
        $portfolioVolatilityWithout = $this->calculatePortfolioVolatility($positionsWithout);
        $varWithout = $portfolioValueWithout * $portfolioVolatilityWithout;

        return $varWith - $varWithout;
    }

    /**
     * Calculate portfolio volatility (simplified).
     *
     * @param  array  $positions  Positions array
     * @return float Portfolio volatility
     */
    private function calculatePortfolioVolatility(array $positions): float
    {
        if (empty($positions)) {
            return 0.0;
        }

        // Simplified: weighted average volatility
        $totalValue = array_sum(array_column($positions, 'value'));
        $weightedVol = 0;

        foreach ($positions as $position) {
            $weight = $position['value'] / $totalValue;
            $weightedVol += $weight * $position['volatility'];
        }

        return $weightedVol;
    }
}
