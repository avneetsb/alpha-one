<?php

namespace TradingPlatform\Application\Services;

use TradingPlatform\Domain\Reconciliation\Models\ReconciliationRun;
use TradingPlatform\Domain\Reconciliation\Models\ReconciliationItem;
use TradingPlatform\Infrastructure\Broker\Dhan\DhanAdapter;
use Illuminate\Support\Collection;

/**
 * Reconciliation Service
 * 
 * Handles comparison of system state vs broker state
 */
class ReconciliationService
{
    private array $adapters = [];

    public function registerAdapter(string $brokerId, $adapter): void
    {
        $this->adapters[$brokerId] = $adapter;
    }

    private function getAdapter(string $brokerId)
    {
        if (!isset($this->adapters[$brokerId])) {
            throw new \RuntimeException("No adapter registered for broker: {$brokerId}");
        }
        return $this->adapters[$brokerId];
    }

    public function startRun(string $brokerId, string $scope = 'all'): ReconciliationRun
    {
        return ReconciliationRun::create([
            'broker_id' => $brokerId,
            'scope' => $scope,
            'started_at' => now(),
            'status' => 'running',
        ]);
    }

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

    public function reconcilePositions(ReconciliationRun $run): void
    {
        $adapter = $this->getAdapter($run->broker_id);
        $brokerPositions = $adapter->getPositions();
        
        // Compare with system positions...
        // $systemPositions = Position::where('broker_id', $run->broker_id)->get();
        
        // Logic...
    }

    public function reconcileHoldings(ReconciliationRun $run): void
    {
        $adapter = $this->getAdapter($run->broker_id);
        $brokerHoldings = $adapter->getHoldings();
        
        // Compare...
    }

    public function completeRun(ReconciliationRun $run): void
    {
        $run->update([
            'completed_at' => now(),
            'status' => $run->mismatches_found > 0 ? 'completed_with_errors' : 'completed',
        ]);
    }

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
