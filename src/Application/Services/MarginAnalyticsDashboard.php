<?php

namespace TradingPlatform\Application\Services;

use TradingPlatform\Domain\Margin\Services\CrossMarginCalculator;

/**
 * Class MarginAnalyticsDashboard
 *
 * Provides comprehensive margin analytics and monitoring capabilities for
 * real-time margin utilization tracking, trend analysis, and risk assessment.
 *
 * **Dashboard Metrics:**
 * - Real-time margin utilization percentage
 * - Available margin vs used margin
 * - Margin call proximity alerts
 * - Historical margin usage trends
 * - Cross-margin benefit tracking
 * - Margin efficiency ratios
 *
 * **Use Cases:**
 * - Real-time risk monitoring
 * - Capital allocation optimization
 * - Margin call prevention
 * - Performance analytics
 * - Regulatory reporting
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Real-Time Margin Dashboard
 * ```php
 * $dashboard = new MarginAnalyticsDashboard($db, $crossMarginCalc);
 *
 * $metrics = $dashboard->getCurrentMetrics('ACC001');
 *
 * echo "Total Margin: ₹" . number_format($metrics['total_margin'], 2) . "\n";
 * echo "Used Margin: ₹" . number_format($metrics['used_margin'], 2) . "\n";
 * echo "Available: ₹" . number_format($metrics['available_margin'], 2) . "\n";
 * echo "Utilization: " . round($metrics['utilization_percentage'], 2) . "%\n";
 *
 * if ($metrics['margin_call_risk'] === 'HIGH') {
 *     echo "WARNING: Margin call risk is HIGH!\n";
 * }
 * ```
 */
class MarginAnalyticsDashboard
{
    private $database;

    private CrossMarginCalculator $crossMarginCalc;

    public function __construct($database, CrossMarginCalculator $crossMarginCalc)
    {
        $this->database = $database;
        $this->crossMarginCalc = $crossMarginCalc;
    }

    /**
     * Get current margin metrics for an account.
     *
     * Retrieves the latest snapshot of margin utilization, including total,
     * used, and available margin, along with a calculated risk level.
     *
     * @param  string  $accountId  Account identifier (e.g., 'ACC001').
     * @return array Current margin metrics.
     *
     * @example Fetching metrics
     * ```php
     * $metrics = $dashboard->getCurrentMetrics('ACC001');
     * // Returns:
     * // [
     * //     'total_margin' => 100000,
     * //     'used_margin' => 45000,
     * //     'utilization_percentage' => 45.0,
     * //     'margin_call_risk' => 'LOW'
     * // ]
     * ```
     */
    public function getCurrentMetrics(string $accountId): array
    {
        // Fetch latest margin utilization
        $utilization = $this->database->query(
            'SELECT * FROM margin_utilization WHERE account_id = ? ORDER BY timestamp DESC LIMIT 1',
            [$accountId]
        )->fetch();

        if (! $utilization) {
            return $this->getEmptyMetrics();
        }

        $utilizationPercentage = ($utilization['used_margin'] / $utilization['total_margin']) * 100;

        return [
            'total_margin' => $utilization['total_margin'],
            'used_margin' => $utilization['used_margin'],
            'available_margin' => $utilization['available_margin'],
            'utilization_percentage' => $utilizationPercentage,
            'margin_call_risk' => $this->assessMarginCallRisk($utilizationPercentage),
            'timestamp' => $utilization['timestamp'],
        ];
    }

    /**
     * Get margin utilization trend over time period.
     *
     * Analyzes historical margin usage to identify trends, peak usage periods,
     * and average utilization. Useful for capital allocation planning.
     *
     * @param  string  $accountId  Account identifier.
     * @param  int  $days  Number of days to analyze (default: 30).
     * @return array Trend data including daily averages and peaks.
     *
     * @example Fetching trend
     * ```php
     * $trend = $dashboard->getUtilizationTrend('ACC001', 7);
     * // Returns: ['average_utilization' => 42.5, 'peak_utilization' => 65.0, ...]
     * ```
     */
    public function getUtilizationTrend(string $accountId, int $days = 30): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $data = $this->database->query(
            'SELECT DATE(timestamp) as date, 
                    AVG(utilization_percentage) as avg_utilization,
                    MAX(utilization_percentage) as max_utilization
             FROM margin_utilization 
             WHERE account_id = ? AND timestamp >= ?
             GROUP BY DATE(timestamp)
             ORDER BY date',
            [$accountId, $startDate]
        )->fetchAll();

        return [
            'period_days' => $days,
            'data_points' => count($data),
            'trend_data' => $data,
            'average_utilization' => $this->calculateAverage($data, 'avg_utilization'),
            'peak_utilization' => $this->calculateMax($data, 'max_utilization'),
        ];
    }

    /**
     * Get cross-margin benefit analytics.
     *
     * Calculates the capital efficiency gained by using cross-margining
     * (offsetting positions) compared to isolated margin requirements.
     *
     * @param  string  $accountId  Account identifier.
     * @return array Cross-margin analytics including total benefit and efficiency gain.
     *
     * @example Cross-margin analysis
     * ```php
     * $benefit = $dashboard->getCrossMarginAnalytics('ACC001');
     * echo "Saved: " . $benefit['total_benefit'];
     * ```
     */
    public function getCrossMarginAnalytics(string $accountId): array
    {
        // Get all positions
        $positions = $this->database->query(
            "SELECT * FROM positions WHERE account_id = ? AND status = 'OPEN'",
            [$accountId]
        )->fetchAll();

        if (empty($positions)) {
            return ['total_benefit' => 0, 'strategies' => []];
        }

        // Calculate cross-margin benefits
        $result = $this->crossMarginCalc->calculatePortfolioCrossMargin($positions);

        return [
            'total_benefit' => $result['total_cross_margin_benefit'],
            'strategies' => $result['strategy_breakdown'],
            'efficiency_gain' => $this->calculateEfficiencyGain($result),
        ];
    }

    /**
     * Get margin call proximity alert.
     *
     * Evaluates the current margin status against critical thresholds to determine
     * the risk of a margin call. Provides recommended actions based on risk level.
     *
     * @param  string  $accountId  Account identifier.
     * @return array Alert information including risk level and recommended action.
     *
     * @example Checking alerts
     * ```php
     * $alert = $dashboard->getMarginCallAlert('ACC001');
     * if ($alert['risk_level'] === 'CRITICAL') {
     *     // Trigger notification
     * }
     * ```
     */
    public function getMarginCallAlert(string $accountId): array
    {
        $metrics = $this->getCurrentMetrics($accountId);

        $thresholds = [
            'CRITICAL' => 95,  // 95%+ utilization
            'HIGH' => 85,      // 85-95% utilization
            'MEDIUM' => 75,    // 75-85% utilization
            'LOW' => 0,        // <75% utilization
        ];

        $utilization = $metrics['utilization_percentage'];
        $riskLevel = $this->assessMarginCallRisk($utilization);

        return [
            'risk_level' => $riskLevel,
            'current_utilization' => $utilization,
            'distance_to_margin_call' => max(0, 100 - $utilization),
            'recommended_action' => $this->getRecommendedAction($riskLevel),
        ];
    }

    /**
     * Assess margin call risk based on utilization percentage.
     *
     * @param  float  $utilization  Margin utilization percentage.
     * @return string Risk level (CRITICAL, HIGH, MEDIUM, LOW).
     */
    private function assessMarginCallRisk(float $utilization): string
    {
        if ($utilization >= 95) {
            return 'CRITICAL';
        }
        if ($utilization >= 85) {
            return 'HIGH';
        }
        if ($utilization >= 75) {
            return 'MEDIUM';
        }

        return 'LOW';
    }

    /**
     * Get recommended action based on risk level.
     *
     * @param  string  $riskLevel  Risk level.
     * @return string Recommended action.
     */
    private function getRecommendedAction(string $riskLevel): string
    {
        return match ($riskLevel) {
            'CRITICAL' => 'URGENT: Add funds or close positions immediately',
            'HIGH' => 'WARNING: Consider reducing positions or adding margin',
            'MEDIUM' => 'CAUTION: Monitor closely, prepare to add margin',
            'LOW' => 'OK: Margin utilization is healthy',
        };
    }

    /**
     * Get empty metrics structure.
     *
     * @return array Empty metrics.
     */
    private function getEmptyMetrics(): array
    {
        return [
            'total_margin' => 0,
            'used_margin' => 0,
            'available_margin' => 0,
            'utilization_percentage' => 0,
            'margin_call_risk' => 'LOW',
            'timestamp' => null,
        ];
    }

    /**
     * Calculate average value from data array.
     *
     * @param  array  $data  Data array.
     * @param  string  $field  Field name to average.
     * @return float Average value.
     */
    private function calculateAverage(array $data, string $field): float
    {
        if (empty($data)) {
            return 0.0;
        }

        return array_sum(array_column($data, $field)) / count($data);
    }

    /**
     * Calculate maximum value from data array.
     *
     * @param  array  $data  Data array.
     * @param  string  $field  Field name to find max.
     * @return float Maximum value.
     */
    private function calculateMax(array $data, string $field): float
    {
        if (empty($data)) {
            return 0.0;
        }

        return max(array_column($data, $field));
    }

    /**
     * Calculate efficiency gain from cross-margin result.
     *
     * @param  array  $crossMarginResult  Cross-margin calculation result.
     * @return float Efficiency gain percentage.
     */
    private function calculateEfficiencyGain(array $crossMarginResult): float
    {
        // Calculate efficiency gain percentage
        return 0.0; // Placeholder
    }
}
