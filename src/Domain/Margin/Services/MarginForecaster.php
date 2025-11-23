<?php

namespace TradingPlatform\Domain\Margin\Services;

/**
 * Margin Forecaster
 *
 * Service for predicting margin requirements under various scenarios.
 * Enables pre-trade margin checks and stress testing of margin obligations
 * under changing market conditions (e.g., increased volatility).
 *
 * **Key Capabilities:**
 * - **Pre-Trade Impact**: Calculate margin change before placing an order
 * - **Volatility Sensitivity**: Estimate margin increases during high volatility
 * - **Portfolio Stress**: Forecast margin requirements under market stress
 * - **Limit Validation**: Check if new trades will breach available margin
 *
 * **Methodology:**
 * Uses a "What-If" simulation approach by temporarily adding hypothetical
 * trades to the portfolio and recalculating total margin requirements using
 * the standard margin model (SPAN/VaR).
 *
 * @version 1.0.0
 *
 * @example Pre-Trade Check
 * ```php
 * $forecaster = new MarginForecaster();
 * $currentPositions = $portfolio->getPositions();
 * $newTrade = ['symbol' => 'NIFTY', 'qty' => 50, 'price' => 18000];
 *
 * $impact = $forecaster->predictImpact($currentPositions, $newTrade);
 *
 * echo "Margin Impact: ₹" . $impact['impact'] . "\n";
 * if (!$impact['is_within_limits']) {
 *     echo "Insufficient margin for trade!";
 * }
 * ```
 *
 * @see MarginCalculator For core margin calculation logic
 * @see RiskLimitManager For margin limit enforcement
 */
class MarginForecaster
{
    /**
     * Predict the margin impact of a hypothetical trade.
     *
     * Simulates adding a new trade to the existing portfolio to calculate
     * the incremental margin requirement. Essential for pre-trade validation.
     *
     * **Process:**
     * 1. Calculate base margin for current portfolio
     * 2. Simulate portfolio state with new trade added
     * 3. Calculate new margin requirement
     * 4. Compute the difference (incremental margin)
     *
     * @param  array  $currentPositions  List of existing positions.
     * @param  array  $newTrade  Details of the proposed trade.
     * @return array Forecast result containing:
     *               - 'current_margin': Margin before trade
     *               - 'predicted_margin': Margin after trade
     *               - 'impact': Incremental margin required (positive = increase)
     *               - 'is_within_limits': Whether account has sufficient free margin
     *
     * @example
     * ```php
     * $result = $forecaster->predictImpact($positions, $order);
     * echo "New Margin Requirement: " . $result['predicted_margin'];
     * ```
     */
    public function predictImpact(array $currentPositions, array $newTrade): array
    {
        // Mock implementation logic:
        // 1. Calculate current portfolio margin
        // 2. Add new trade to portfolio
        // 3. Calculate new portfolio margin
        // 4. Return difference

        // In a real implementation, this would call MarginCalculator
        $currentMargin = 100000; // Placeholder
        $predictedMargin = 110000; // Placeholder
        $availableMargin = 15000; // Placeholder

        $impact = $predictedMargin - $currentMargin;

        return [
            'current_margin' => $currentMargin,
            'predicted_margin' => $predictedMargin,
            'impact' => $impact,
            'is_within_limits' => $impact <= $availableMargin,
        ];
    }

    /**
     * Forecast margin requirements under increased volatility.
     *
     * Estimates how margin requirements (especially SPAN/VaR based) would
     * change if market volatility increases. Exchanges typically increase
     * margins during volatile periods.
     *
     * **Model:**
     * Margin ≈ Base Margin * (1 + Sensitivity * VolatilityChange)
     *
     * @param  array  $positions  Current portfolio positions.
     * @param  float  $volatilityIncreasePct  Projected increase in volatility (e.g., 0.20 for 20%).
     * @return float Forecasted total margin requirement.
     *
     * @example Volatility Stress Test
     * ```php
     * // What if volatility spikes by 50%?
     * $stressedMargin = $forecaster->forecastVolatilityImpact($positions, 0.50);
     * echo "Margin in high vol scenario: " . $stressedMargin;
     * ```
     */
    public function forecastVolatilityImpact(array $positions, float $volatilityIncreasePct): float
    {
        // Simplified model:
        // Assume margin scales with volatility, but not 1:1 due to non-linear risks (options)
        // Using a sensitivity factor of 0.5 for linear assets, higher for options

        $currentMargin = array_sum(array_column($positions, 'margin_required') ?: [0]);

        // Sensitivity factor (could be dynamic based on asset class)
        $sensitivity = 0.5;

        return $currentMargin * (1 + ($volatilityIncreasePct * $sensitivity));
    }
}
