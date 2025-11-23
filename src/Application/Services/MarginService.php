<?php

namespace TradingPlatform\Application\Services;

use TradingPlatform\Domain\Exchange\Models\Instrument;
use TradingPlatform\Domain\Portfolio\Position;

/**
 * Class MarginService
 *
 * Handles margin calculations, utilization tracking, and margin call alerts.
 * Implements standard exchange margin models (SPAN + Exposure) to ensure
 * compliance and risk safety.
 *
 * @version 1.0.0
 */
class MarginService
{
    /**
     * Calculate SPAN margin for futures/options.
     *
     * Standard Portfolio Analysis of Risk (SPAN) calculation.
     * Estimates the maximum probable loss for a portfolio under various market scenarios.
     *
     * @param  Instrument  $instrument  The trading instrument.
     * @param  float  $quantity  The quantity.
     * @param  float  $price  The current price.
     * @return float The calculated SPAN margin.
     *
     * @example Calculating SPAN
     * ```php
     * $span = $service->calculateSpanMargin($niftyFut, 50, 19500);
     * // Returns: 117000 (approx 12%)
     * ```
     */
    public function calculateSpanMargin(Instrument $instrument, float $quantity, float $price): float
    {
        // Simplified SPAN calculation
        // In production, this would use exchange-provided SPAN files
        $lotSize = $instrument->lot_size ?? 1;
        $contractValue = $quantity * $price * $lotSize;

        // Approximate 10-15% of contract value for futures
        $spanRate = 0.12;

        return $contractValue * $spanRate;
    }

    /**
     * Calculate exposure margin.
     *
     * Additional margin collected by exchanges to cover risks beyond SPAN.
     * Typically a fixed percentage of the contract value.
     *
     * @param  Instrument  $instrument  The trading instrument.
     * @param  float  $quantity  The quantity.
     * @param  float  $price  The current price.
     * @return float The calculated exposure margin.
     *
     * @example Calculating Exposure
     * ```php
     * $exposure = $service->calculateExposureMargin($niftyFut, 50, 19500);
     * // Returns: 39000 (approx 4%)
     * ```
     */
    public function calculateExposureMargin(Instrument $instrument, float $quantity, float $price): float
    {
        $lotSize = $instrument->lot_size ?? 1;
        $contractValue = $quantity * $price * $lotSize;

        // Approximate 3-5% exposure margin
        $exposureRate = 0.04;

        return $contractValue * $exposureRate;
    }

    /**
     * Calculate total margin required.
     *
     * Sum of SPAN and Exposure margins. This is the amount blocked in the
     * user's account for the position.
     *
     * @param  Instrument  $instrument  The trading instrument.
     * @param  float  $quantity  The quantity.
     * @param  float  $price  The current price.
     * @param  string  $segment  The market segment (default: 'futures').
     * @return array Associative array with 'span_margin', 'exposure_margin', and 'total_margin'.
     *
     * @example Total Margin
     * ```php
     * $total = $service->calculateTotalMargin($niftyFut, 50, 19500);
     * // Returns: ['span_margin' => 117000, 'exposure_margin' => 39000, 'total_margin' => 156000]
     * ```
     */
    public function calculateTotalMargin(Instrument $instrument, float $quantity, float $price, string $segment = 'futures'): array
    {
        $spanMargin = $this->calculateSpanMargin($instrument, $quantity, $price);
        $exposureMargin = $this->calculateExposureMargin($instrument, $quantity, $price);

        return [
            'span_margin' => round($spanMargin, 2),
            'exposure_margin' => round($exposureMargin, 2),
            'total_margin' => round($spanMargin + $exposureMargin, 2),
        ];
    }

    /**
     * Pre-trade margin validation.
     *
     * Checks if the account has sufficient available margin to place a new order.
     * Returns the shortfall amount if validation fails.
     *
     * @param  float  $availableMargin  The available margin in the account.
     * @param  Instrument  $instrument  The trading instrument.
     * @param  float  $quantity  The quantity.
     * @param  float  $price  The price.
     * @return array Validation result including 'is_valid', 'required_margin', 'available_margin', and 'shortfall'.
     *
     * @example Validating an order
     * ```php
     * $result = $service->validateMarginForOrder(100000, $niftyFut, 50, 19500);
     * if (!$result['is_valid']) {
     *     echo "Insufficient funds. Shortfall: " . $result['shortfall'];
     * }
     * ```
     */
    public function validateMarginForOrder(
        float $availableMargin,
        Instrument $instrument,
        float $quantity,
        float $price
    ): array {
        $required = $this->calculateTotalMargin($instrument, $quantity, $price);
        $isValid = $availableMargin >= $required['total_margin'];

        return [
            'is_valid' => $isValid,
            'required_margin' => $required['total_margin'],
            'available_margin' => $availableMargin,
            'shortfall' => $isValid ? 0 : ($required['total_margin'] - $availableMargin),
        ];
    }

    /**
     * Calculate margin utilization percentage.
     *
     * @param  float  $usedMargin  The margin currently used.
     * @param  float  $totalMargin  The total margin available.
     * @return float The utilization percentage (0-100).
     */
    public function calculateUtilizationPercentage(float $usedMargin, float $totalMargin): float
    {
        if ($totalMargin == 0) {
            return 0.0;
        }

        return round(($usedMargin / $totalMargin) * 100, 2);
    }

    /**
     * Check for margin call conditions.
     *
     * Evaluates if the account's margin utilization has breached critical thresholds.
     *
     * @param  float  $usedMargin  The margin currently used.
     * @param  float  $totalMargin  The total margin available.
     * @return array|null An array with alert details if a margin call is needed, null otherwise.
     *
     * @example Checking for margin call
     * ```php
     * $alert = $service->checkMarginCall(96000, 100000);
     * // Returns: ['severity' => 'critical', 'message' => 'Critical margin utilization...']
     * ```
     */
    public function checkMarginCall(float $usedMargin, float $totalMargin): ?array
    {
        $utilization = $this->calculateUtilizationPercentage($usedMargin, $totalMargin);

        if ($utilization >= 95) {
            return [
                'severity' => 'critical',
                'message' => "Critical margin utilization: {$utilization}%. Immediate action required.",
                'utilization' => $utilization,
            ];
        } elseif ($utilization >= 85) {
            return [
                'severity' => 'high',
                'message' => "High margin utilization: {$utilization}%. Monitor closely.",
                'utilization' => $utilization,
            ];
        } elseif ($utilization >= 75) {
            return [
                'severity' => 'medium',
                'message' => "Medium margin utilization: {$utilization}%.",
                'utilization' => $utilization,
            ];
        }

        return null; // No margin call
    }

    /**
     * Perform margin stress testing under various scenarios.
     *
     * Simulates market shocks (price crashes, volatility spikes) to estimate
     * the impact on margin requirements.
     *
     * @param  array  $positions  List of positions to test.
     * @param  array  $scenarios  Associative array of scenarios (e.g., ['crash' => ['price_change_pct' => -10]]).
     * @return array Results of the stress test for each scenario.
     *
     * @example Stress testing
     * ```php
     * $scenarios = ['market_crash' => ['price_change_pct' => -10, 'volatility_change_pct' => 20]];
     * $results = $service->stressTestMargin($positions, $scenarios);
     * echo "Stressed Margin: " . $results['market_crash']['stressed_margin'];
     * ```
     */
    public function stressTestMargin(array $positions, array $scenarios): array
    {
        $results = [];

        foreach ($scenarios as $name => $params) {
            // $params might be ['price_change_pct' => -10, 'volatility_change_pct' => 20]
            $stressedMargin = 0.0;

            foreach ($positions as $pos) {
                // Apply stress factors to price/volatility and recalculate
                // If pos is a Position model, we assume it has a calculated margin_required property injected
                // or we use a fallback
                $baseMargin = $pos->margin_required ?? 0.0;
                $impact = 1.0 + (abs($params['price_change_pct'] ?? 0) / 100) + (($params['volatility_change_pct'] ?? 0) / 100);
                $stressedMargin += $baseMargin * $impact;
            }

            $results[$name] = [
                'stressed_margin' => $stressedMargin,
                'increase_pct' => (($stressedMargin - array_sum(array_column($positions, 'margin_required'))) / array_sum(array_column($positions, 'margin_required'))) * 100,
            ];
        }

        return $results;
    }
}
