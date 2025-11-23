<?php

namespace TradingPlatform\Domain\Risk\Services;

/**
 * Class RiskLimitManager
 *
 * Manages and enforces hierarchical risk limits across portfolio, strategy, and
 * instrument levels. Essential for risk governance and regulatory compliance.
 *
 * **Hierarchical Structure:**
 * - PORTFOLIO: Global limits applying to entire portfolio
 * - STRATEGY: Limits specific to individual strategies
 * - INSTRUMENT: Limits for specific instruments/symbols
 *
 * **Supported Metrics:**
 * - max_position_size: Maximum notional value per position
 * - max_drawdown: Maximum allowed drawdown percentage
 * - var_95: Maximum Value at Risk at 95% confidence
 * - max_correlation: Maximum correlation between positions
 * - min_equity: Minimum account equity required
 *
 * **Business Applications:**
 * - Pre-trade risk checks
 * - Real-time limit monitoring
 * - Regulatory compliance (SEBI, exchange limits)
 * - Risk budget allocation
 * - Circuit breaker implementation
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Setting Portfolio-Level Limits
 * ```php
 * $riskManager = new RiskLimitManager();
 *
 * // Global portfolio limits
 * $riskManager->setLimit('PORTFOLIO', 'GLOBAL', 'var_95', 50000);
 * $riskManager->setLimit('PORTFOLIO', 'GLOBAL', 'max_drawdown', 15);
 * $riskManager->setLimit('PORTFOLIO', 'GLOBAL', 'min_equity', 100000);
 * ```
 * @example Strategy-Specific Limits
 * ```php
 * // Momentum strategy limits
 * $riskManager->setLimit('STRATEGY', 'MomentumStrategy', 'max_position_size', 100000);
 * $riskManager->setLimit('STRATEGY', 'MomentumStrategy', 'max_correlation', 0.7);
 *
 * // Conservative strategy with tighter limits
 * $riskManager->setLimit('STRATEGY', 'ConservativeStrategy', 'max_position_size', 50000);
 * $riskManager->setLimit('STRATEGY', 'ConservativeStrategy', 'var_95', 10000);
 * ```
 * @example Pre-Trade Risk Check
 * ```php
 * $metrics = [
 *     'var_95' => 45000,
 *     'max_drawdown' => 12,
 *     'current_equity' => 150000,
 * ];
 *
 * $result = $riskManager->checkLimits('PORTFOLIO', 'GLOBAL', $metrics);
 *
 * if (!$result['approved']) {
 *     foreach ($result['violations'] as $violation) {
 *         echo "VIOLATION: {$violation['metric']} = {$violation['current']} ";
 *         echo "exceeds limit of {$violation['limit']}\n";
 *     }
 *     throw new RiskLimitException('Risk limits violated');
 * }
 * ```
 *
 * @see VaRCalculator For calculating VaR metrics
 * @see StressTestService For stress testing against limits
 */
class RiskLimitManager
{
    private array $limits = [];

    /**
     * Set a risk limit for a specific entity at a given hierarchical level.
     *
     * Limits are stored in a hierarchical structure allowing for:
     * - Global portfolio-wide limits
     * - Strategy-specific limits
     * - Instrument-specific limits
     *
     * **Metric Types:**
     * - max_position_size: Maximum notional value (in currency)
     * - max_drawdown: Maximum drawdown percentage (0-100)
     * - var_95: Maximum 95% VaR (in currency)
     * - max_correlation: Maximum correlation coefficient (0-1)
     * - min_equity: Minimum account equity (in currency)
     *
     * **Hierarchy:**
     * Limits can be set at multiple levels and are checked independently.
     * More specific limits (INSTRUMENT) don't override broader limits (PORTFOLIO).
     *
     * @param  string  $level  Hierarchical level: 'PORTFOLIO', 'STRATEGY', or 'INSTRUMENT'
     * @param  string  $entityId  Entity identifier:
     *                            - 'GLOBAL' for portfolio level
     *                            - Strategy name for strategy level (e.g., 'MomentumStrategy')
     *                            - Symbol for instrument level (e.g., 'AAPL', 'NIFTY50')
     * @param  string  $metric  Metric name (see supported metrics above)
     * @param  float  $value  Limit value (units depend on metric type)
     *
     * @example Setting multiple limit types
     * ```php
     * $rm = new RiskLimitManager();
     *
     * // Portfolio limits
     * $rm->setLimit('PORTFOLIO', 'GLOBAL', 'var_95', 50000);  // ₹50k max VaR
     * $rm->setLimit('PORTFOLIO', 'GLOBAL', 'max_drawdown', 20);  // 20% max DD
     *
     * // Strategy limits
     * $rm->setLimit('STRATEGY', 'AggressiveStrategy', 'max_position_size', 200000);
     *
     * // Instrument limits
     * $rm->setLimit('INSTRUMENT', 'AAPL', 'max_position_size', 50000);
     * ```
     *
     * @note Limits are stored in memory and not persisted automatically
     * @note Setting a limit overwrites any previous limit for the same entity/metric
     */
    public function setLimit(string $level, string $entityId, string $metric, float $value): void
    {
        $this->limits[$level][$entityId][$metric] = $value;
    }

    /**
     * Check if current metrics violate any defined limits for an entity.
     *
     * Compares current metric values against defined limits and returns
     * a detailed report of any violations. Used for pre-trade checks and
     * real-time monitoring.
     *
     * **Violation Logic:**
     * - max_* metrics: Violation if current > limit
     * - min_* metrics: Violation if current < limit
     * - max_drawdown: Special handling for percentage (positive representation)
     *
     * **Use Cases:**
     * - Pre-trade risk validation
     * - Real-time position monitoring
     * - End-of-day risk reporting
     * - Circuit breaker triggers
     *
     * @param  string  $level  Hierarchical level to check: 'PORTFOLIO', 'STRATEGY', or 'INSTRUMENT'
     * @param  string  $entityId  Entity identifier (must match a previously set limit)
     * @param  array  $metrics  Associative array of current metric values
     *                          Keys must match metric names used in setLimit()
     *                          Example: ['var_95' => 45000, 'max_drawdown' => 12]
     * @return array Result containing:
     *               - 'approved' (bool): true if no violations, false otherwise
     *               - 'violations' (array): List of violations, each with:
     *               - 'metric': Name of violated metric
     *               - 'limit': Configured limit value
     *               - 'current': Current value that violated the limit
     *
     * @example Pre-trade risk check
     * ```php
     * // Before placing order, check if it would violate limits
     * $currentMetrics = [
     *     'var_95' => 48000,
     *     'max_drawdown' => 14,
     *     'max_position_size' => 95000,
     * ];
     *
     * $result = $rm->checkLimits('PORTFOLIO', 'GLOBAL', $currentMetrics);
     *
     * if (!$result['approved']) {
     *     $msg = "Risk limit violations:\n";
     *     foreach ($result['violations'] as $v) {
     *         $msg .= "- {$v['metric']}: {$v['current']} exceeds {$v['limit']}\n";
     *     }
     *     throw new RiskLimitException($msg);
     * }
     * ```
     * @example Strategy-level monitoring
     * ```php
     * $strategyMetrics = [
     *     'max_position_size' => 105000,
     *     'max_correlation' => 0.75,
     * ];
     *
     * $result = $rm->checkLimits('STRATEGY', 'MomentumStrategy', $strategyMetrics);
     *
     * if (!$result['approved']) {
     *     // Log violation, send alert, or halt strategy
     *     logger()->warning('Strategy limit violated', $result['violations']);
     * }
     * ```
     *
     * @note Only checks metrics that have defined limits (ignores others)
     * @note Returns approved=true if no limits are defined for the entity
     */
    public function checkLimits(string $level, string $entityId, array $metrics): array
    {
        $violations = [];
        $definedLimits = $this->limits[$level][$entityId] ?? [];

        foreach ($definedLimits as $metric => $limit) {
            if (! isset($metrics[$metric])) {
                continue;
            }

            $currentValue = $metrics[$metric];
            $isViolation = false;

            // Determine violation based on metric type
            switch ($metric) {
                case 'max_drawdown':
                    // Drawdown is usually negative, so a larger negative number is worse
                    // Or if represented as positive percentage (e.g. 20%), then > 20 is bad
                    // Assuming positive percentage representation here for simplicity
                    $isViolation = $currentValue > $limit;
                    break;

                case 'min_equity':
                    $isViolation = $currentValue < $limit;
                    break;

                default:
                    // Standard "max" limit (VaR, Position Size, Correlation)
                    $isViolation = $currentValue > $limit;
                    break;
            }

            if ($isViolation) {
                $violations[] = [
                    'metric' => $metric,
                    'limit' => $limit,
                    'current' => $currentValue,
                ];
            }
        }

        return [
            'approved' => empty($violations),
            'violations' => $violations,
        ];
    }

    /**
     * Get hierarchical limits for a strategy (strategy + portfolio levels).
     *
     * Returns both strategy-specific limits and global portfolio limits,
     * allowing for comprehensive risk checking at multiple levels.
     *
     * **Use Case:**
     * When evaluating a strategy's risk, you often need to check both:
     * 1. Strategy-specific limits (e.g., max position size for this strategy)
     * 2. Portfolio-wide limits (e.g., total portfolio VaR)
     *
     * This method retrieves both in a single call.
     *
     * @param  string  $strategyId  Strategy identifier (e.g., 'MomentumStrategy')
     * @return array Hierarchical limits structure:
     *               - 'strategy': Array of strategy-specific limits
     *               - 'portfolio': Array of global portfolio limits
     *               Each array maps metric names to limit values
     *
     * @example Checking multi-level limits
     * ```php
     * $limits = $rm->getHierarchicalLimits('MomentumStrategy');
     *
     * // Check strategy-specific limits
     * if (isset($limits['strategy']['max_position_size'])) {
     *     $strategyLimit = $limits['strategy']['max_position_size'];
     *     echo "Strategy max position: ₹" . number_format($strategyLimit, 2);
     * }
     *
     * // Check portfolio-wide limits
     * if (isset($limits['portfolio']['var_95'])) {
     *     $portfolioVaRLimit = $limits['portfolio']['var_95'];
     *     echo "Portfolio VaR limit: ₹" . number_format($portfolioVaRLimit, 2);
     * }
     * ```
     * @example Comprehensive pre-trade check
     * ```php
     * $limits = $rm->getHierarchicalLimits('AggressiveStrategy');
     *
     * // Check both levels
     * $strategyCheck = $rm->checkLimits('STRATEGY', 'AggressiveStrategy', $metrics);
     * $portfolioCheck = $rm->checkLimits('PORTFOLIO', 'GLOBAL', $metrics);
     *
     * if (!$strategyCheck['approved'] || !$portfolioCheck['approved']) {
     *     // Reject trade - violates limits at some level
     * }
     * ```
     *
     * @note Returns empty arrays if no limits are defined at either level
     * @note Does not include INSTRUMENT-level limits
     */
    public function getHierarchicalLimits(string $strategyId): array
    {
        // Return merged limits for Strategy + Global Portfolio
        return [
            'strategy' => $this->limits['STRATEGY'][$strategyId] ?? [],
            'portfolio' => $this->limits['PORTFOLIO']['GLOBAL'] ?? [],
        ];
    }
}
