<?php

namespace TradingPlatform\Domain\Fees\Calculators;

use TradingPlatform\Domain\Fees\FeeCalculatorInterface;

/**
 * Class DhanFeeCalculator
 *
 * Implements Dhan broker fee calculations based on Zerodha's fee structure.
 * Supports equity, currency, and commodity trading with accurate tax calculations.
 *
 * **Fee Structure:**
 * - Brokerage: ₹20 flat or 0.03% (whichever is lower)
 * - STT: 0.025% (intraday sell), 0.1% (delivery both sides)
 * - Exchange charges: 0.00345% (NSE), varies by exchange
 * - GST: 18% on brokerage + exchange charges
 * - SEBI charges: ₹10 per crore turnover
 * - Stamp duty: 0.015% (buy side only, varies by state)
 *
 * **Supported Asset Classes:**
 * - Equity (intraday, delivery)
 * - Currency derivatives
 * - Commodity derivatives (agri, non-agri)
 *
 * **Reference:** https://zerodha.com/charges/
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Calculating equity delivery fees
 * ```php
 * $calc = new DhanFeeCalculator();
 *
 * $fees = $calc->calculateFees([
 *     'asset_class' => 'equity',
 *     'segment' => 'delivery',
 *     'order_value' => 100000,  // ₹1 lakh
 *     'side' => 'buy',
 * ]);
 *
 * echo "Total fees: ₹" . $fees['total_fees'];
 * // Output: Total fees: ₹180.00 (approx)
 * ```
 *
 * @see FeeCalculatorInterface
 */
class DhanFeeCalculator implements FeeCalculatorInterface
{
    private const BROKERAGE_FLAT = 20.0; // ₹20 flat

    private const BROKERAGE_PERCENTAGE = 0.0003; // 0.03%

    private const GST_RATE = 0.18; // 18%

    private const SEBI_CHARGES_PER_CRORE = 10.0; // ₹10 per crore

    /**
     * Calculate total fees for an order.
     *
     * Routes to appropriate calculation method based on asset class.
     * Supports equity, currency, and commodity fee calculations.
     *
     * @param  array  $orderData  Order details:
     *                            - asset_class: 'equity', 'currency', 'commodity'
     *                            - segment: 'intraday', 'delivery', 'futures', 'options'
     *                            - order_value: Total order value (quantity × price)
     *                            - side: 'buy' or 'sell'
     *                            - commodity_type: 'agri' or 'non-agri' (for commodities)
     * @return array Fee breakdown with all components
     *
     * @throws \InvalidArgumentException If asset class is unsupported
     *
     * @example Equity intraday order
     * ```php
     * $fees = $calc->calculateFees([
     *     'asset_class' => 'equity',
     *     'segment' => 'intraday',
     *     'order_value' => 50000,
     *     'side' => 'sell',
     * ]);
     * ```
     */
    public function calculateFees(array $orderData): array
    {
        $assetClass = $orderData['asset_class'] ?? 'equity';
        $segment = $orderData['segment'] ?? 'intraday';
        $orderValue = $orderData['order_value'];
        $side = $orderData['side']; // 'buy' or 'sell'

        switch ($assetClass) {
            case 'equity':
                return $this->calculateEquityFees($orderValue, $side, $segment);
            case 'currency':
                return $this->calculateCurrencyFees($orderValue, $side);
            case 'commodity':
                return $this->calculateCommodityFees($orderValue, $side, $orderData['commodity_type'] ?? 'non-agri');
            default:
                throw new \InvalidArgumentException("Unsupported asset class: {$assetClass}");
        }
    }

    /**
     * Estimate pre-trade fees.
     *
     * Provides fee estimate before order placement for display in UI
     * and break-even price calculation.
     *
     * @param  string  $instrument  Instrument symbol
     * @param  float  $quantity  Number of shares/contracts
     * @param  float  $price  Expected execution price
     * @param  string  $side  'buy' or 'sell'
     * @param  string  $segment  'intraday', 'delivery', 'futures', 'options'
     * @return array Fee estimate with same structure as calculateFees()
     *
     * @example Pre-trade fee display
     * ```php
     * $estimate = $calc->estimateFees('TCS', 50, 3500, 'buy', 'delivery');
     * echo "Estimated fees: ₹" . $estimate['total_fees'];
     * ```
     */
    public function estimateFees(
        string $instrument,
        float $quantity,
        float $price,
        string $side,
        string $segment
    ): array {
        $orderValue = $quantity * $price;

        // Determine asset class from instrument (simplified - should use instrument lookup)
        $assetClass = 'equity'; // Default

        return $this->calculateFees([
            'asset_class' => $assetClass,
            'segment' => $segment,
            'order_value' => $orderValue,
            'side' => $side,
        ]);
    }

    /**
     * Calculate equity-specific fees.
     *
     * Handles both intraday and delivery equity trades with appropriate
     * STT rates and exchange charges.
     *
     * **Fee Components:**
     * - Brokerage: min(₹20, 0.03% of value)
     * - STT: 0.025% (intraday sell), 0.1% (delivery both sides)
     * - Exchange charges: 0.00345% (NSE)
     * - GST: 18% on brokerage + exchange charges
     * - SEBI: ₹10 per crore
     * - Stamp duty: 0.015% (buy side only)
     *
     * @param  float  $orderValue  Total order value
     * @param  string  $side  'buy' or 'sell'
     * @param  string  $segment  'intraday' or 'delivery'
     * @return array Complete fee breakdown
     *
     * @example Intraday vs delivery comparison
     * ```php
     * $intradayFees = $this->calculateEquityFees(100000, 'sell', 'intraday');
     * $deliveryFees = $this->calculateEquityFees(100000, 'buy', 'delivery');
     * echo "Intraday: ₹{$intradayFees['total_fees']}\n";
     * echo "Delivery: ₹{$deliveryFees['total_fees']}\n";
     * ```
     */
    private function calculateEquityFees(float $orderValue, string $side, string $segment): array
    {
        // Brokerage: min(₹20, 0.03% of order value)
        $brokerage = min(self::BROKERAGE_FLAT, $orderValue * self::BROKERAGE_PERCENTAGE);

        // STT (Securities Transaction Tax)
        if ($segment === 'intraday') {
            // Intraday: 0.025% on sell side only
            $stt = ($side === 'sell') ? $orderValue * 0.00025 : 0.0;
        } else {
            // Delivery: 0.1% on both buy and sell
            $stt = $orderValue * 0.001;
        }

        // Exchange transaction charges (NSE: 0.00345%)
        $exchangeTxnCharges = $orderValue * 0.0000345;

        // SEBI charges: ₹10 per crore
        $sebiCharges = ($orderValue / 10000000) * self::SEBI_CHARGES_PER_CRORE;

        // GST: 18% on (brokerage + transaction charges + SEBI charges)
        $taxableAmount = $brokerage + $exchangeTxnCharges + $sebiCharges;
        $gst = $taxableAmount * self::GST_RATE;

        // Stamp duty
        if ($segment === 'intraday') {
            // Intraday: 0.003% on buy side
            $stampDuty = ($side === 'buy') ? $orderValue * 0.00003 : 0.0;
        } else {
            // Delivery: 0.015% on buy side
            $stampDuty = ($side === 'buy') ? $orderValue * 0.00015 : 0.0;
        }

        $totalFees = $brokerage + $stt + $exchangeTxnCharges + $gst + $sebiCharges + $stampDuty;

        return [
            'brokerage' => round($brokerage, 2),
            'stt' => round($stt, 2),
            'ctt' => 0.0,
            'exchange_transaction_charges' => round($exchangeTxnCharges, 2),
            'gst' => round($gst, 2),
            'sebi_charges' => round($sebiCharges, 2),
            'stamp_duty' => round($stampDuty, 2),
            'total_fees' => round($totalFees, 2),
        ];
    }

    /**
     * Calculate currency fees.
     *
     * @param  float  $orderValue  Total value of the order.
     * @param  string  $side  'buy' or 'sell'.
     * @return array Fee breakdown.
     */
    private function calculateCurrencyFees(float $orderValue, string $side): array
    {
        // Brokerage: min(₹20, 0.03% of order value)
        $brokerage = min(self::BROKERAGE_FLAT, $orderValue * self::BROKERAGE_PERCENTAGE);

        // STT: Not applicable for currency derivatives
        $stt = 0.0;

        // Exchange transaction charges (NSE: 0.0009%)
        $exchangeTxnCharges = $orderValue * 0.000009;

        // SEBI charges: ₹10 per crore
        $sebiCharges = ($orderValue / 10000000) * self::SEBI_CHARGES_PER_CRORE;

        // GST: 18% on (brokerage + transaction charges + SEBI charges)
        $taxableAmount = $brokerage + $exchangeTxnCharges + $sebiCharges;
        $gst = $taxableAmount * self::GST_RATE;

        // Stamp duty: 0.0001% on buy side
        $stampDuty = ($side === 'buy') ? $orderValue * 0.000001 : 0.0;

        $totalFees = $brokerage + $stt + $exchangeTxnCharges + $gst + $sebiCharges + $stampDuty;

        return [
            'brokerage' => round($brokerage, 2),
            'stt' => round($stt, 2),
            'ctt' => 0.0,
            'exchange_transaction_charges' => round($exchangeTxnCharges, 2),
            'gst' => round($gst, 2),
            'sebi_charges' => round($sebiCharges, 2),
            'stamp_duty' => round($stampDuty, 2),
            'total_fees' => round($totalFees, 2),
        ];
    }

    /**
     * Calculate commodity fees.
     *
     * @param  float  $orderValue  Total value of the order.
     * @param  string  $side  'buy' or 'sell'.
     * @param  string  $commodityType  'processed' or 'non-agri'.
     * @return array Fee breakdown.
     */
    private function calculateCommodityFees(float $orderValue, string $side, string $commodityType): array
    {
        // Brokerage: min(₹20, 0.03% of order value)
        $brokerage = min(self::BROKERAGE_FLAT, $orderValue * self::BROKERAGE_PERCENTAGE);

        // CTT (Commodity Transaction Tax) - sell side only
        if ($side === 'sell') {
            if ($commodityType === 'processed') {
                $ctt = $orderValue * 0.0005; // 0.05%
            } else {
                $ctt = $orderValue * 0.0001; // 0.01% for non-agri
            }
        } else {
            $ctt = 0.0;
        }

        // Exchange transaction charges (MCX: 0.0019%)
        $exchangeTxnCharges = $orderValue * 0.000019;

        // SEBI charges: ₹10 per crore
        $sebiCharges = ($orderValue / 10000000) * self::SEBI_CHARGES_PER_CRORE;

        // GST: 18% on (brokerage + transaction charges + SEBI charges)
        $taxableAmount = $brokerage + $exchangeTxnCharges + $sebiCharges;
        $gst = $taxableAmount * self::GST_RATE;

        // Stamp duty: 0.002% on buy side (MCX)
        $stampDuty = ($side === 'buy') ? $orderValue * 0.00002 : 0.0;

        $totalFees = $brokerage + $ctt + $exchangeTxnCharges + $gst + $sebiCharges + $stampDuty;

        return [
            'brokerage' => round($brokerage, 2),
            'stt' => 0.0,
            'ctt' => round($ctt, 2),
            'exchange_transaction_charges' => round($exchangeTxnCharges, 2),
            'gst' => round($gst, 2),
            'sebi_charges' => round($sebiCharges, 2),
            'stamp_duty' => round($stampDuty, 2),
            'total_fees' => round($totalFees, 2),
        ];
    }

    /**
     * Get supported asset classes.
     *
     * @return array List of supported asset classes.
     */
    public function getSupportedAssetClasses(): array
    {
        return ['equity', 'currency', 'commodity'];
    }
}
