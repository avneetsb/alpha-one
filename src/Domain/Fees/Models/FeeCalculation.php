<?php

namespace TradingPlatform\Domain\Fees\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class FeeCalculation
 *
 * Stores detailed fee breakdown for each order/trade execution.
 * Essential for accurate P&L calculation, fee reconciliation, and tax reporting.
 *
 * **Fee Components Tracked:**
 * - Brokerage: Broker commission (flat or percentage)
 * - STT: Securities Transaction Tax (equity only)
 * - CTT: Commodity Transaction Tax (commodities only)
 * - Exchange charges: NSE/BSE/MCX transaction fees
 * - GST: 18% on brokerage + exchange charges
 * - SEBI charges: ₹10 per crore turnover
 * - Stamp duty: State-specific (0.002% to 0.015%)
 *
 * **Use Cases:**
 * - Post-trade fee verification
 * - Daily/monthly fee reconciliation
 * - Tax reporting (STT, CTT for ITR)
 * - P&L calculation (net of fees)
 * - Broker fee comparison
 * - Audit trail for regulatory compliance
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Storing fee calculation for an order
 * ```php
 * $feeCalc = FeeCalculation::create([
 *     'order_id' => 'ORD123',
 *     'broker_id' => 'DHAN',
 *     'asset_class' => 'equity',
 *     'segment' => 'delivery',
 *     'order_value' => 100000,
 *     'quantity' => 100,
 *     'brokerage' => 20.0,
 *     'stt' => 100.0,
 *     'exchange_transaction_charges' => 35.0,
 *     'gst' => 9.90,
 *     'sebi_charges' => 0.10,
 *     'stamp_duty' => 15.0,
 *     'total_fees' => 180.0,
 * ]);
 * ```
 *
 * @property int $id Primary key
 * @property string $order_id Order identifier
 * @property string $trade_id Trade identifier (if executed)
 * @property string $broker_id Broker identifier (e.g., 'DHAN', 'ZERODHA')
 * @property int $instrument_id Foreign key to instruments table
 * @property string $asset_class Asset class: 'equity', 'currency', 'commodity'
 * @property string $segment Trading segment: 'intraday', 'delivery', 'futures', 'options'
 * @property float $order_value Total order value (quantity × price)
 * @property int $quantity Number of shares/contracts
 * @property float $brokerage Broker commission amount
 * @property float $stt Securities Transaction Tax
 * @property float $ctt Commodity Transaction Tax
 * @property float $exchange_transaction_charges Exchange fees
 * @property float $gst GST on taxable components (18%)
 * @property float $sebi_charges SEBI regulatory charges
 * @property float $stamp_duty Government stamp duty
 * @property float $total_fees Sum of all fee components
 * @property \DateTime $calculation_timestamp When fees were calculated
 */
class FeeCalculation extends Model
{
    protected $fillable = [
        'order_id',
        'trade_id',
        'broker_id',
        'instrument_id',
        'asset_class',
        'segment',
        'order_value',
        'quantity',
        'brokerage',
        'stt',
        'ctt',
        'exchange_transaction_charges',
        'gst',
        'sebi_charges',
        'stamp_duty',
        'total_fees',
        'calculation_timestamp',
    ];

    protected $casts = [
        'order_value' => 'decimal:2',
        'quantity' => 'integer',
        'brokerage' => 'decimal:2',
        'stt' => 'decimal:2',
        'ctt' => 'decimal:2',
        'exchange_transaction_charges' => 'decimal:2',
        'gst' => 'decimal:2',
        'sebi_charges' => 'decimal:2',
        'stamp_duty' => 'decimal:2',
        'total_fees' => 'decimal:2',
        'calculation_timestamp' => 'datetime',
    ];

    /**
     * Get the order this fee calculation belongs to.
     *
     * Establishes relationship to the Order model for accessing
     * order details like symbol, side, status, etc.
     *
     * @return BelongsTo Eloquent relationship to Order model
     *
     * @example Accessing order details from fee calculation
     * ```php
     * $feeCalc = FeeCalculation::find(1);
     * $order = $feeCalc->order;
     *
     * echo "Order: {$order->order_id}";
     * echo "Symbol: {$order->symbol}";
     * echo "Fees: ₹{$feeCalc->total_fees}";
     * ```
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo('TradingPlatform\Domain\Order\Order');
    }

    /**
     * Get the instrument for this fee calculation.
     *
     * Provides access to instrument details like symbol, exchange,
     * asset class, and derivative specifications.
     *
     * @return BelongsTo Eloquent relationship to Instrument model
     *
     * @example Getting instrument details
     * ```php
     * $feeCalc = FeeCalculation::find(1);
     * $instrument = $feeCalc->instrument;
     *
     * echo "Symbol: {$instrument->symbol}";
     * echo "Exchange: {$instrument->exchange}";
     * ```
     */
    public function instrument(): BelongsTo
    {
        return $this->belongsTo('TradingPlatform\Domain\Exchange\Models\Instrument');
    }

    /**
     * Get fee breakdown as associative array.
     *
     * Returns all fee components in a structured array format,
     * useful for displaying in UI, generating reports, or API responses.
     *
     * @return array Fee breakdown with all components:
     *               - brokerage: Broker commission
     *               - stt: Securities Transaction Tax
     *               - ctt: Commodity Transaction Tax
     *               - exchange_transaction_charges: Exchange fees
     *               - gst: GST amount
     *               - sebi_charges: SEBI regulatory charges
     *               - stamp_duty: Stamp duty
     *               - total_fees: Sum of all fees
     *
     * @example Displaying fee breakdown in UI
     * ```php
     * $feeCalc = FeeCalculation::find(1);
     * $breakdown = $feeCalc->getBreakdown();
     *
     * foreach ($breakdown as $component => $amount) {
     *     echo ucfirst(str_replace('_', ' ', $component)) . ": ₹" . number_format($amount, 2) . "\n";
     * }
     * // Output:
     * // Brokerage: ₹20.00
     * // Stt: ₹100.00
     * // Exchange transaction charges: ₹35.00
     * // ...
     * ```
     */
    public function getBreakdown(): array
    {
        return [
            'brokerage' => $this->brokerage,
            'stt' => $this->stt,
            'ctt' => $this->ctt,
            'exchange_transaction_charges' => $this->exchange_transaction_charges,
            'gst' => $this->gst,
            'sebi_charges' => $this->sebi_charges,
            'stamp_duty' => $this->stamp_duty,
            'total_fees' => $this->total_fees,
        ];
    }

    /**
     * Scope query to fee calculations within a date range.
     *
     * Filters fee calculations by calculation_timestamp between
     * the specified start and end dates (inclusive).
     *
     * **Use Cases:**
     * - Monthly fee reconciliation
     * - Tax period reporting (FY, quarter)
     * - Performance analysis
     * - Broker fee comparison
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query builder instance
     * @param  \DateTime  $from  Start date (inclusive)
     * @param  \DateTime  $to  End date (inclusive)
     * @return \Illuminate\Database\Eloquent\Builder Modified query
     *
     * @example Get all fees for current month
     * ```php
     * $from = new \DateTime('first day of this month');
     * $to = new \DateTime('last day of this month');
     *
     * $monthlyFees = FeeCalculation::forDateRange($from, $to)->get();
     * $totalFees = $monthlyFees->sum('total_fees');
     *
     * echo "Total fees this month: ₹" . number_format($totalFees, 2);
     * ```
     * @example Financial year fee report
     * ```php
     * $fyStart = new \DateTime('2024-04-01');
     * $fyEnd = new \DateTime('2025-03-31');
     *
     * $fyFees = FeeCalculation::forDateRange($fyStart, $fyEnd)
     *     ->selectRaw('SUM(stt) as total_stt, SUM(total_fees) as total_fees')
     *     ->first();
     *
     * echo "FY 2024-25 STT: ₹" . number_format($fyFees->total_stt, 2);
     * ```
     */
    public function scopeForDateRange($query, \DateTime $from, \DateTime $to)
    {
        return $query->whereBetween('calculation_timestamp', [
            $from->format('Y-m-d H:i:s'),
            $to->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Scope query to fee calculations for a specific broker.
     *
     * Filters fee calculations by broker_id, useful for:
     * - Broker-specific fee analysis
     * - Multi-broker comparison
     * - Broker reconciliation
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query builder instance
     * @param  string  $brokerId  Broker identifier (e.g., 'DHAN', 'ZERODHA')
     * @return \Illuminate\Database\Eloquent\Builder Modified query
     *
     * @example Compare fees across brokers
     * ```php
     * $dhanFees = FeeCalculation::forBroker('DHAN')
     *     ->whereMonth('calculation_timestamp', date('m'))
     *     ->sum('total_fees');
     *
     * $zerodhaFees = FeeCalculation::forBroker('ZERODHA')
     *     ->whereMonth('calculation_timestamp', date('m'))
     *     ->sum('total_fees');
     *
     * echo "Dhan fees: ₹" . number_format($dhanFees, 2) . "\n";
     * echo "Zerodha fees: ₹" . number_format($zerodhaFees, 2) . "\n";
     * echo "Savings: ₹" . number_format(abs($dhanFees - $zerodhaFees), 2);
     * ```
     */
    public function scopeForBroker($query, string $brokerId)
    {
        return $query->where('broker_id', $brokerId);
    }
}
