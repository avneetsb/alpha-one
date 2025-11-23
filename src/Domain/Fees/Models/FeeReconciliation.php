<?php

namespace TradingPlatform\Domain\Fees\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FeeReconciliation
 *
 * Tracks daily fee reconciliation between platform-calculated fees and
 * broker statement fees. Essential for identifying discrepancies, ensuring
 * accurate P&L, and maintaining audit compliance.
 *
 * **Reconciliation Process:**
 * 1. Calculate total fees from FeeCalculation records
 * 2. Import broker statement fees (from contract notes/statements)
 * 3. Compare calculated vs broker fees
 * 4. Flag discrepancies for investigation
 * 5. Document resolution in notes
 *
 * **Status Values:**
 * - 'matched': Fees match within tolerance (₹0.01)
 * - 'mismatch': Discrepancy detected, needs investigation
 * - 'resolved': Mismatch investigated and resolved
 * - 'pending': Awaiting broker statement
 *
 * **Common Discrepancy Causes:**
 * - Rounding differences
 * - Missing trades in calculation
 * - Broker fee adjustments/waivers
 * - Tax calculation differences
 * - Timing differences (T+0 vs T+1)
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Daily reconciliation workflow
 * ```php
 * // Calculate platform fees for the day
 * $calculatedTotal = FeeCalculation::whereDate('calculation_timestamp', '2024-01-15')
 *     ->sum('total_fees');
 *
 * // Import broker statement total
 * $brokerTotal = 1250.50;  // From broker statement
 *
 * // Create reconciliation record
 * $recon = FeeReconciliation::create([
 *     'broker_id' => 'DHAN',
 *     'date' => '2024-01-15',
 *     'calculated_fees_total' => $calculatedTotal,
 *     'broker_statement_fees_total' => $brokerTotal,
 *     'discrepancy' => $calculatedTotal - $brokerTotal,
 *     'status' => abs($calculatedTotal - $brokerTotal) < 0.01 ? 'matched' : 'mismatch',
 * ]);
 * ```
 *
 * @property int $id Primary key
 * @property string $broker_id Broker identifier
 * @property \DateTime $date Reconciliation date
 * @property float $calculated_fees_total Sum of calculated fees
 * @property float $broker_statement_fees_total Fees from broker statement
 * @property float $discrepancy Difference (calculated - broker)
 * @property string $status Reconciliation status
 * @property string $notes Investigation notes
 */
class FeeReconciliation extends Model
{
    protected $table = 'fee_reconciliation';

    protected $fillable = [
        'broker_id',
        'date',
        'calculated_fees_total',
        'broker_statement_fees_total',
        'discrepancy',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'calculated_fees_total' => 'decimal:2',
        'broker_statement_fees_total' => 'decimal:2',
        'discrepancy' => 'decimal:2',
    ];

    /**
     * Check if fees match within acceptable tolerance.
     *
     * Returns true if status is 'matched', indicating calculated fees
     * match broker statement fees within ₹0.01 tolerance.
     *
     * @return bool true if fees match, false otherwise
     *
     * @example Checking reconciliation status
     * ```php
     * $recon = FeeReconciliation::whereDate('date', today())->first();
     *
     * if ($recon->isMatched()) {
     *     echo "Fees reconciled successfully";
     * } else {
     *     echo "Discrepancy: ₹" . abs($recon->discrepancy);
     * }
     * ```
     */
    public function isMatched(): bool
    {
        return $this->status === 'matched';
    }

    /**
     * Check if there's a fee mismatch requiring investigation.
     *
     * Returns true if status is 'mismatch', indicating a discrepancy
     * between calculated and broker fees that needs investigation.
     *
     * @return bool true if mismatch detected, false otherwise
     *
     * @example Alerting on mismatches
     * ```php
     * $mismatches = FeeReconciliation::whereDate('date', today())
     *     ->get()
     *     ->filter(fn($r) => $r->hasMismatch());
     *
     * if ($mismatches->count() > 0) {
     *     // Send alert to operations team
     *     foreach ($mismatches as $m) {
     *         echo "Mismatch on {$m->date}: ₹{$m->discrepancy}\n";
     *     }
     * }
     * ```
     */
    public function hasMismatch(): bool
    {
        return $this->status === 'mismatch';
    }

    /**
     * Get discrepancy as percentage of broker statement fees.
     *
     * Calculates the discrepancy percentage to assess severity.
     * Useful for setting alert thresholds and prioritizing investigations.
     *
     * **Typical Thresholds:**
     * - <0.1%: Acceptable rounding difference
     * - 0.1-1%: Minor discrepancy, investigate if recurring
     * - 1-5%: Significant discrepancy, investigate immediately
     * - >5%: Critical discrepancy, halt trading until resolved
     *
     * @return float Discrepancy percentage (always positive)
     *
     * @example Prioritizing investigations
     * ```php
     * $mismatches = FeeReconciliation::mismatched()->get();
     *
     * foreach ($mismatches as $recon) {
     *     $pct = $recon->getDiscrepancyPercentage();
     *
     *     if ($pct > 5) {
     *         echo "CRITICAL: {$pct}% discrepancy on {$recon->date}\n";
     *     } elseif ($pct > 1) {
     *         echo "WARNING: {$pct}% discrepancy on {$recon->date}\n";
     *     }
     * }
     * ```
     */
    public function getDiscrepancyPercentage(): float
    {
        if ($this->broker_statement_fees_total == 0) {
            return 0.0;
        }

        return abs(($this->discrepancy / $this->broker_statement_fees_total) * 100);
    }

    /**
     * Scope query to mismatched reconciliations.
     *
     * Filters to reconciliation records with status 'mismatch',
     * useful for generating investigation reports and alerts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query builder instance
     * @return \Illuminate\Database\Eloquent\Builder Modified query
     *
     * @example Monthly mismatch report
     * ```php
     * $monthlyMismatches = FeeReconciliation::mismatched()
     *     ->whereMonth('date', date('m'))
     *     ->get();
     *
     * $totalDiscrepancy = $monthlyMismatches->sum('discrepancy');
     * echo "Total discrepancy this month: ₹" . number_format(abs($totalDiscrepancy), 2);
     * ```
     */
    public function scopeMismatched($query)
    {
        return $query->where('status', 'mismatch');
    }

    /**
     * Scope query to reconciliations within a date range.
     *
     * Filters reconciliation records by date between specified
     * start and end dates (inclusive).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query builder instance
     * @param  \DateTime  $from  Start date (inclusive)
     * @param  \DateTime  $to  End date (inclusive)
     * @return \Illuminate\Database\Eloquent\Builder Modified query
     *
     * @example Quarterly reconciliation summary
     * ```php
     * $q1Start = new \DateTime('2024-01-01');
     * $q1End = new \DateTime('2024-03-31');
     *
     * $q1Recons = FeeReconciliation::forDateRange($q1Start, $q1End)->get();
     *
     * $matched = $q1Recons->filter(fn($r) => $r->isMatched())->count();
     * $mismatched = $q1Recons->filter(fn($r) => $r->hasMismatch())->count();
     *
     * echo "Q1 Reconciliation: {$matched} matched, {$mismatched} mismatched\n";
     * echo "Match rate: " . round(($matched / $q1Recons->count()) * 100, 2) . "%";
     * ```
     */
    public function scopeForDateRange($query, \DateTime $from, \DateTime $to)
    {
        return $query->whereBetween('date', [
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
        ]);
    }
}
