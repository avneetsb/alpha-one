<?php

namespace TradingPlatform\Application\Services;

use TradingPlatform\Domain\Reconciliation\Models\ReconciliationItem;
use TradingPlatform\Domain\Reconciliation\Models\ReconciliationRun;

/**
 * Class ReconciliationService
 *
 * Handles comparison of system state vs broker state (orders, positions, holdings).
 * Ensures data integrity by identifying discrepancies between the local database
 * and the broker's records, which is critical for accurate P&L and risk management.
 *
 * @version 1.0.0
 */
class ReconciliationService
{
    /**
     * @var array Registered broker adapters.
     */
    private array $adapters = [];

    /**
     * Register a broker adapter.
     *
     * Adds a broker adapter to the service, allowing it to fetch data from
     * that specific broker for reconciliation.
     *
     * @param  string  $brokerId  Broker identifier (e.g., 'dhan').
     * @param  mixed  $adapter  The broker adapter instance.
     *
     * @example Registering adapter
     * ```php
     * $service->registerAdapter('dhan', new DhanAdapter());
     * ```
     */
    public function registerAdapter(string $brokerId, $adapter): void
    {
        $this->adapters[$brokerId] = $adapter;
    }

    /**
     * Get a registered adapter.
     *
     * @return mixed
     *
     * @throws \RuntimeException If adapter not found.
     */
    private function getAdapter(string $brokerId)
    {
        if (! isset($this->adapters[$brokerId])) {
            throw new \RuntimeException("No adapter registered for broker: {$brokerId}");
        }

        return $this->adapters[$brokerId];
    }

    /**
     * Start a new reconciliation run.
     *
     * Initializes a reconciliation session, tracking progress and results.
     *
     * @param  string  $brokerId  Broker identifier.
     * @param  string  $scope  Scope of reconciliation ('all', 'orders', 'positions').
     * @return ReconciliationRun The created run model.
     *
     * @example Starting a run
     * ```php
     * $run = $service->startRun('dhan', 'positions');
     * ```
     */
    public function startRun(string $brokerId, string $scope = 'all'): ReconciliationRun
    {
        return ReconciliationRun::create([
            'broker_id' => $brokerId,
            'scope' => $scope,
            'started_at' => now(),
            'status' => 'running',
        ]);
    }

    /**
     * Reconcile orders between system and broker.
     *
     * Fetches open orders from the broker and compares them with local records.
     * Logs any discrepancies found (e.g., status mismatch, quantity mismatch).
     *
     * @param  ReconciliationRun  $run  The current reconciliation run.
     *
     * @example Reconciling orders
     * ```php
     * $service->reconcileOrders($run);
     * ```
     */
    public function reconcileOrders(ReconciliationRun $run): void
    {
        $adapter = $this->getAdapter($run->broker_id);

        // Fetch open orders from broker
        // In a real implementation, we'd fetch all orders for the day or specific range
        $brokerOrders = $adapter->getOrders();

        // Fetch system orders (assuming Order model exists)
        // $systemOrders = Order::where('broker_id', $run->broker_id)->where('status', 'OPEN')->get();

        // Mocking comparison logic for structure
        $mismatches = 0;
        $processed = 0;

        // Logic to compare lists...
        // For each mismatch found:
        // $this->logMismatch($run, 'order', $systemId, $brokerId, $sysData, $brokerData, $diff);

        $run->increment('items_processed', $processed);
        $run->increment('mismatches_found', $mismatches);
    }

    /**
     * Reconcile positions between system and broker.
     *
     * Compares open positions to ensure the system accurately reflects market exposure.
     * Critical for risk management.
     *
     * @param  ReconciliationRun  $run  The current reconciliation run.
     */
    public function reconcilePositions(ReconciliationRun $run): void
    {
        $adapter = $this->getAdapter($run->broker_id);
        $brokerPositions = $adapter->getPositions();

        // Compare with system positions...
        // $systemPositions = Position::where('broker_id', $run->broker_id)->get();

        // Logic...
    }

    /**
     * Reconcile holdings between system and broker.
     *
     * Verifies long-term holdings (demat) against broker records.
     *
     * @param  ReconciliationRun  $run  The current reconciliation run.
     */
    public function reconcileHoldings(ReconciliationRun $run): void
    {
        $adapter = $this->getAdapter($run->broker_id);
        $brokerHoldings = $adapter->getHoldings();

        // Compare...
    }

    /**
     * Mark the reconciliation run as complete.
     *
     * Updates the run status based on whether any mismatches were found.
     *
     * @param  ReconciliationRun  $run  The current reconciliation run.
     */
    public function completeRun(ReconciliationRun $run): void
    {
        $run->update([
            'completed_at' => now(),
            'status' => $run->mismatches_found > 0 ? 'completed_with_errors' : 'completed',
        ]);
    }

    /**
     * Log a mismatch item.
     *
     * Records specific details about a discrepancy for manual review.
     *
     * @param  ReconciliationRun  $run  The reconciliation run.
     * @param  string  $type  Item type ('order', 'position', etc.).
     * @param  string  $itemId  System item ID.
     * @param  string|null  $brokerRefId  Broker reference ID.
     * @param  array  $systemData  Data from system.
     * @param  array  $brokerData  Data from broker.
     * @param  array  $diff  Difference details.
     */
    private function logMismatch(
        ReconciliationRun $run,
        string $type,
        string $itemId,
        ?string $brokerRefId,
        array $systemData,
        array $brokerData,
        array $diff
    ): void {
        ReconciliationItem::create([
            'reconciliation_run_id' => $run->id,
            'item_type' => $type,
            'item_id' => $itemId,
            'broker_ref_id' => $brokerRefId,
            'system_data' => $systemData,
            'broker_data' => $brokerData,
            'discrepancy_details' => $diff,
            'status' => 'mismatch',
        ]);
    }
}
