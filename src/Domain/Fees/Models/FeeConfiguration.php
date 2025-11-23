<?php

namespace TradingPlatform\Domain\Fees\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FeeConfiguration
 *
 * Stores broker-specific fee rules and rate structures for different
 * asset classes and trading segments. Supports versioning and time-based
 * activation for handling fee structure changes.
 *
 * **Configuration Structure:**
 * - Broker-specific rules (Dhan, Zerodha, etc.)
 * - Asset class specific (equity, commodity, currency)
 * - Segment specific (intraday, delivery, futures, options)
 * - Time-bound activation (effective_from, effective_to)
 * - Version tracking for audit trail
 *
 * **Fee Rules Format (JSON):**
 * ```json
 * {
 *   "brokerage": {"type": "flat", "value": 20},
 *   "stt": {"rate": 0.001, "applicable_on": "sell"},
 *   "exchange_charges": {"rate": 0.0000345},
 *   "gst_rate": 0.18,
 *   "sebi_charges": {"rate": 0.0000001},
 *   "stamp_duty": {"rate": 0.00015}
 * }
 * ```
 *
 * **Use Cases:**
 * - Dynamic fee calculation based on active rules
 * - Historical fee structure tracking
 * - A/B testing different fee structures
 * - Broker fee comparison
 * - Regulatory compliance (fee disclosure)
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Creating a fee configuration
 * ```php
 * FeeConfiguration::create([
 *     'broker_id' => 'DHAN',
 *     'asset_class' => 'equity',
 *     'segment' => 'delivery',
 *     'fee_rules' => [
 *         'brokerage' => ['type' => 'flat', 'value' => 20],
 *         'stt' => ['rate' => 0.001, 'applicable_on' => 'sell'],
 *         'exchange_charges' => ['rate' => 0.0000345],
 *     ],
 *     'effective_from' => now(),
 *     'version' => 1,
 * ]);
 * ```
 */
class FeeConfiguration extends Model
{
    protected $fillable = [
        'broker_id',
        'asset_class',
        'segment',
        'fee_rules',
        'effective_from',
        'effective_to',
        'version',
    ];

    protected $casts = [
        'fee_rules' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    /**
     * Get active fee configuration for given parameters.
     *
     * Retrieves the currently active fee configuration based on broker,
     * asset class, segment, and optional date. Handles overlapping
     * configurations by selecting the most recent effective_from date.
     *
     * **Selection Logic:**
     * 1. Match broker_id, asset_class, segment
     * 2. effective_from <= query date
     * 3. effective_to >= query date OR is NULL (no end date)
     * 4. Order by effective_from DESC (most recent first)
     * 5. Return first match
     *
     * @param  string  $brokerId  Broker identifier (e.g., 'DHAN', 'ZERODHA')
     * @param  string  $assetClass  Asset class: 'equity', 'currency', 'commodity'
     * @param  string  $segment  Trading segment: 'intraday', 'delivery', 'futures', 'options'
     * @param  \DateTime|null  $date  Date to check (defaults to current date/time)
     * @return self|null Active configuration or null if none found
     *
     * @example Get current fee configuration
     * ```php
     * $config = FeeConfiguration::getActiveConfiguration(
     *     'DHAN',
     *     'equity',
     *     'delivery'
     * );
     *
     * if ($config) {
     *     $brokerageRule = $config->getFeeRule('brokerage');
     *     echo "Brokerage: ₹" . $brokerageRule['value'];
     * }
     * ```
     * @example Get historical fee configuration
     * ```php
     * $historicalDate = new \DateTime('2023-01-01');
     * $oldConfig = FeeConfiguration::getActiveConfiguration(
     *     'DHAN',
     *     'equity',
     *     'intraday',
     *     $historicalDate
     * );
     *
     * // Compare with current
     * $currentConfig = FeeConfiguration::getActiveConfiguration(
     *     'DHAN',
     *     'equity',
     *     'intraday'
     * );
     * ```
     */
    public static function getActiveConfiguration(
        string $brokerId,
        string $assetClass,
        string $segment,
        ?\DateTime $date = null
    ): ?self {
        $date = $date ?? new \DateTime;

        return static::where('broker_id', $brokerId)
            ->where('asset_class', $assetClass)
            ->where('segment', $segment)
            ->where('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            })
            ->orderBy('effective_from', 'desc')
            ->first();
    }

    /**
     * Get fee rule for a specific component.
     *
     * Extracts a specific fee rule from the fee_rules JSON structure.
     * Returns null if the component doesn't exist in the configuration.
     *
     * **Common Components:**
     * - 'brokerage': Broker commission rules
     * - 'stt': Securities Transaction Tax rules
     * - 'ctt': Commodity Transaction Tax rules
     * - 'exchange_charges': Exchange transaction charges
     * - 'gst_rate': GST percentage
     * - 'sebi_charges': SEBI regulatory charges
     * - 'stamp_duty': Stamp duty rules
     *
     * @param  string  $component  Fee component name
     * @return array|null Fee rule configuration or null if not found
     *
     * @example Get brokerage rule
     * ```php
     * $config = FeeConfiguration::getActiveConfiguration('DHAN', 'equity', 'delivery');
     * $brokerageRule = $config->getFeeRule('brokerage');
     *
     * if ($brokerageRule['type'] === 'flat') {
     *     $brokerage = $brokerageRule['value'];  // Fixed amount
     * } else {
     *     $brokerage = $orderValue * $brokerageRule['rate'];  // Percentage
     * }
     * ```
     * @example Get STT rule with applicability
     * ```php
     * $sttRule = $config->getFeeRule('stt');
     *
     * if ($sttRule && $orderSide === $sttRule['applicable_on']) {
     *     $stt = $orderValue * $sttRule['rate'];
     * } else {
     *     $stt = 0;  // STT not applicable
     * }
     * ```
     */
    public function getFeeRule(string $component): ?array
    {
        return $this->fee_rules[$component] ?? null;
    }
}
