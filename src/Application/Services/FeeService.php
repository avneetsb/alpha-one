<?php

namespace TradingPlatform\Application\Services;

use TradingPlatform\Domain\Fees\FeeCalculatorInterface;
use TradingPlatform\Domain\Fees\Calculators\DhanFeeCalculator;
use TradingPlatform\Domain\Fees\Models\FeeCalculation;
use TradingPlatform\Domain\Fees\Models\FeeReconciliation;

/**
 * Fee Management Service
 * 
 * Central service for all fee-related operations
 */
class FeeService
{
    private array $calculators = [];

    public function __construct()
    {
        // Register default calculators
        $this->registerCalculator('dhan', new DhanFeeCalculator());
    }

    /**
     * Register a fee calculator for a broker
     */
    public function registerCalculator(string $brokerId, FeeCalculatorInterface $calculator): void
    {
        $this->calculators[$brokerId] = $calculator;
    }

    /**
     * Get calculator for broker
     */
    private function getCalculator(string $brokerId): FeeCalculatorInterface
    {
        if (!isset($this->calculators[$brokerId])) {
            throw new \RuntimeException("No fee calculator registered for broker: {$brokerId}");
        }

        return $this->calculators[$brokerId];
    }

    /**
     * Estimate pre-trade fees
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
     * Calculate and persist fees for an order
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
     * Get total fees for a date range
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
     * Get fee breakdown for a date range
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
     * Reconcile fees with broker statement
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
     * Get fee analytics for a period
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
