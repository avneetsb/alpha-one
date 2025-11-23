<?php

namespace TradingPlatform\Application\Services;

use TradingPlatform\Domain\Fees\Calculators\DhanFeeCalculator;
use TradingPlatform\Domain\Fees\FeeCalculatorInterface;
use TradingPlatform\Domain\Fees\Models\FeeCalculation;
use TradingPlatform\Domain\Fees\Models\FeeReconciliation;

/**
 * Fee Management Service
 *
 * Central service for all fee-related operations, including estimation,
 * calculation, persistence, and reconciliation. Handles broker-specific
 * fee structures via registered calculators.
 *
 * @version 1.0.0
 */
class FeeService
{
    /**
     * @var array<string, FeeCalculatorInterface> Registered fee calculators.
     */
    private array $calculators = [];

    /**
     * FeeService constructor.
     * Registers default calculators.
     */
    public function __construct()
    {
        // Register default calculators
        $this->registerCalculator('dhan', new DhanFeeCalculator);
    }

    /**
     * Register a fee calculator for a broker.
     *
     * Allows dynamic registration of fee calculators for different brokers,
     * enabling the service to support multi-broker environments.
     *
     * @param  string  $brokerId  Broker identifier (e.g., 'dhan', 'zerodha').
     * @param  FeeCalculatorInterface  $calculator  The calculator instance implementing the interface.
     *
     * @example Registering a calculator
     * ```php
     * $service->registerCalculator('zerodha', new ZerodhaFeeCalculator());
     * ```
     */
    public function registerCalculator(string $brokerId, FeeCalculatorInterface $calculator): void
    {
        $this->calculators[$brokerId] = $calculator;
    }

    /**
     * Get calculator for broker.
     *
     * @param  string  $brokerId  Broker identifier.
     * @return FeeCalculatorInterface The fee calculator instance.
     *
     * @throws \RuntimeException If no calculator is registered.
     */
    private function getCalculator(string $brokerId): FeeCalculatorInterface
    {
        if (! isset($this->calculators[$brokerId])) {
            throw new \RuntimeException("No fee calculator registered for broker: {$brokerId}");
        }

        return $this->calculators[$brokerId];
    }

    /**
     * Estimate pre-trade fees.
     *
     * Provides an estimation of the total fees and taxes for a potential order.
     * Useful for showing "Estimated Charges" in the order placement UI.
     *
     * @param  string  $brokerId  Broker identifier.
     * @param  string  $instrument  Instrument symbol (e.g., 'RELIANCE').
     * @param  float  $quantity  Order quantity.
     * @param  float  $price  Order price.
     * @param  string  $side  Order side ('BUY' or 'SELL').
     * @param  string  $segment  Trading segment ('intraday', 'delivery', 'futures', 'options').
     * @return array Fee breakdown including brokerage, taxes, and total.
     *
     * @example Estimating fees
     * ```php
     * $fees = $service->estimateFees('dhan', 'NIFTY', 50, 19500, 'BUY', 'options');
     * // Returns: ['brokerage' => 20, 'stt' => 10, 'total' => 35.5, ...]
     * ```
     */
    public function estimateFees(
        string $brokerId,
        string $instrument,
        float $quantity,
        float $price,
        string $side,
        string $segment = 'intraday'
    ): array {
        $calculator = $this->getCalculator($brokerId);

        return $calculator->estimateFees(
            $instrument,
            $quantity,
            $price,
            $side,
            $segment
        );
    }

    /**
     * Calculate and persist fees for an order.
     *
     * Calculates the actual fees incurred for an executed order and saves the
     * breakdown to the database for reporting and reconciliation.
     *
     * @param  array  $orderData  Order details including ID, broker, instrument, price, qty, etc.
     * @return FeeCalculation The persisted fee record.
     *
     * @example Persisting fees
     * ```php
     * $order = ['broker_id' => 'dhan', 'price' => 100, 'quantity' => 10, ...];
     * $record = $service->calculateAndPersistFees($order);
     * ```
     */
    public function calculateAndPersistFees(array $orderData): FeeCalculation
    {
        $brokerId = $orderData['broker_id'];
        $calculator = $this->getCalculator($brokerId);

        // Calculate fees
        $fees = $calculator->calculateFees($orderData);

        // Persist to database
        $feeCalculation = FeeCalculation::create([
            'order_id' => $orderData['order_id'] ?? null,
            'trade_id' => $orderData['trade_id'] ?? null,
            'broker_id' => $brokerId,
            'instrument_id' => $orderData['instrument_id'],
            'asset_class' => $orderData['asset_class'] ?? 'equity',
            'segment' => $orderData['segment'] ?? 'intraday',
            'order_value' => $orderData['order_value'],
            'quantity' => $orderData['quantity'],
            'brokerage' => $fees['brokerage'],
            'stt' => $fees['stt'],
            'ctt' => $fees['ctt'],
            'exchange_transaction_charges' => $fees['exchange_transaction_charges'],
            'gst' => $fees['gst'],
            'sebi_charges' => $fees['sebi_charges'],
            'stamp_duty' => $fees['stamp_duty'],
            'total_fees' => $fees['total_fees'],
            'calculation_timestamp' => now(),
        ]);

        return $feeCalculation;
    }

    /**
     * Get total fees for a date range.
     *
     * Aggregates the total fees incurred across all trades within the specified
     * date range for a specific broker.
     *
     * @param  string  $brokerId  Broker identifier.
     * @param  \DateTime  $from  Start date.
     * @param  \DateTime  $to  End date.
     * @return float Total fees.
     *
     * @example Calculating total fees
     * ```php
     * $total = $service->getTotalFeesForDateRange('dhan', new DateTime('2023-01-01'), new DateTime('2023-01-31'));
     * // Returns: 1500.50
     * ```
     */
    public function getTotalFeesForDateRange(
        string $brokerId,
        \DateTime $from,
        \DateTime $to
    ): float {
        return FeeCalculation::forBroker($brokerId)
            ->forDateRange($from, $to)
            ->sum('total_fees');
    }

    /**
     * Get fee breakdown for a date range.
     *
     * Provides a detailed breakdown of fees (Brokerage, STT, GST, etc.) for
     * tax reporting and analysis purposes.
     *
     * @param  string  $brokerId  Broker identifier.
     * @param  \DateTime  $from  Start date.
     * @param  \DateTime  $to  End date.
     * @return array Breakdown of fee components.
     *
     * @example Fee breakdown
     * ```php
     * $breakdown = $service->getFeeBreakdownForDateRange('dhan', $start, $end);
     * // Returns: ['total_brokerage' => 500, 'total_stt' => 200, ...]
     * ```
     */
    public function getFeeBreakdownForDateRange(
        string $brokerId,
        \DateTime $from,
        \DateTime $to
    ): array {
        $calculations = FeeCalculation::forBroker($brokerId)
            ->forDateRange($from, $to)
            ->get();

        return [
            'total_brokerage' => $calculations->sum('brokerage'),
            'total_stt' => $calculations->sum('stt'),
            'total_ctt' => $calculations->sum('ctt'),
            'total_exchange_charges' => $calculations->sum('exchange_transaction_charges'),
            'total_gst' => $calculations->sum('gst'),
            'total_sebi_charges' => $calculations->sum('sebi_charges'),
            'total_stamp_duty' => $calculations->sum('stamp_duty'),
            'total_fees' => $calculations->sum('total_fees'),
            'trade_count' => $calculations->count(),
        ];
    }

    /**
     * Reconcile fees with broker statement.
     *
     * Compares the system-calculated fees against the total fees reported in the
     * broker's daily contract note or ledger. Identifies discrepancies within
     * a tolerance limit.
     *
     * @param  string  $brokerId  Broker identifier.
     * @param  \DateTime  $date  Reconciliation date.
     * @param  float  $brokerStatementTotal  Total fees from broker statement.
     * @return FeeReconciliation The reconciliation record.
     *
     * @example Reconciling fees
     * ```php
     * $recon = $service->reconcileFees('dhan', new DateTime('2023-10-27'), 150.50);
     * echo $recon->status; // 'matched' or 'mismatch'
     * ```
     */
    public function reconcileFees(
        string $brokerId,
        \DateTime $date,
        float $brokerStatementTotal
    ): FeeReconciliation {
        $from = (clone $date)->setTime(0, 0, 0);
        $to = (clone $date)->setTime(23, 59, 59);

        $calculatedTotal = $this->getTotalFeesForDateRange($brokerId, $from, $to);
        $discrepancy = $calculatedTotal - $brokerStatementTotal;

        // Determine status based on tolerance (0.01% tolerance)
        $tolerance = abs($brokerStatementTotal * 0.0001);
        $status = abs($discrepancy) <= $tolerance ? 'matched' : 'mismatch';

        return FeeReconciliation::create([
            'broker_id' => $brokerId,
            'date' => $date,
            'calculated_fees_total' => $calculatedTotal,
            'broker_statement_fees_total' => $brokerStatementTotal,
            'discrepancy' => $discrepancy,
            'status' => $status,
        ]);
    }

    /**
     * Get fee analytics for a period.
     *
     * Calculates derived metrics such as average fee per trade to help analyze
     * trading costs and efficiency.
     *
     * @param  string  $brokerId  Broker identifier.
     * @param  \DateTime  $from  Start date.
     * @param  \DateTime  $to  End date.
     * @return array Analytics data including averages.
     *
     * @example Fee analytics
     * ```php
     * $analytics = $service->getFeeAnalytics('dhan', $start, $end);
     * echo "Avg Cost: " . $analytics['avg_fee_per_trade'];
     * ```
     */
    public function getFeeAnalytics(
        string $brokerId,
        \DateTime $from,
        \DateTime $to
    ): array {
        $breakdown = $this->getFeeBreakdownForDateRange($brokerId, $from, $to);

        $avgFeePerTrade = $breakdown['trade_count'] > 0
            ? $breakdown['total_fees'] / $breakdown['trade_count']
            : 0;

        return array_merge($breakdown, [
            'avg_fee_per_trade' => round($avgFeePerTrade, 2),
            'period_start' => $from->format('Y-m-d'),
            'period_end' => $to->format('Y-m-d'),
        ]);
    }
}
