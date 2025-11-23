<?php

namespace TradingPlatform\Domain\Fees;

/**
 * Interface FeeCalculatorInterface
 *
 * Defines the contract for broker-specific fee calculation implementations.
 * Each broker has different fee structures, and this interface ensures
 * consistent fee calculation across all broker integrations.
 *
 * **Supported Fee Components:**
 * - Brokerage (flat or percentage-based)
 * - STT (Securities Transaction Tax)
 * - CTT (Commodity Transaction Tax)
 * - Exchange transaction charges
 * - GST (Goods and Services Tax)
 * - SEBI charges
 * - Stamp duty
 *
 * **Broker Implementations:**
 * - DhanFeeCalculator (Zerodha-style fees)
 * - Custom broker calculators can be added
 *
 * **Use Cases:**
 * - Pre-trade fee estimation
 * - Post-trade fee calculation
 * - Fee reconciliation
 * - Tax reporting
 * - P&L calculation
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @example Implementing a custom broker fee calculator
 * ```php
 * class CustomBrokerFeeCalculator implements FeeCalculatorInterface
 * {
 *     public function calculateFees(array $orderData): array
 *     {
 *         return ['brokerage' => 10.0, 'stt' => 5.0, 'total_fees' => 15.0];
 *     }
 *     public function estimateFees(...): array { return []; }
 *     public function getSupportedAssetClasses(): array { return ['equity']; }
 * }
 * ```
 *
 * @see DhanFeeCalculatorFor Zerodha-style fee implementation
 */
interface FeeCalculatorInterface
{
    /**
     * Calculate total fees for an order.
     *
     * Computes all applicable fees for a completed or pending order based on
     * broker-specific fee structures, asset class, segment, and order details.
     *
     * **Required Order Data:**
     * - asset_class: 'equity', 'currency', 'commodity'
     * - segment: 'intraday', 'delivery', 'futures', 'options'
     * - order_value: Total order value (quantity × price)
     * - side: 'buy' or 'sell'
     * - quantity: Number of shares/contracts
     *
     * **Optional Order Data:**
     * - commodity_type: 'processed' or 'non-agri' (for commodities)
     * - instrument_type: 'stock', 'index', etc.
     *
     * **Return Structure:**
     * Must return array with all fee components:
     * - brokerage: Broker commission
     * - stt: Securities Transaction Tax
     * - ctt: Commodity Transaction Tax
     * - exchange_transaction_charges: Exchange fees
     * - gst: GST on taxable components
     * - sebi_charges: SEBI regulatory charges
     * - stamp_duty: Government stamp duty
     * - total_fees: Sum of all fees
     *
     * @param  array  $orderData  Order details including asset_class, segment, order_value, side, quantity
     * @return array Fee breakdown with all components and total_fees
     *
     * @throws \InvalidArgumentException If asset class is unsupported or required data is missing
     *
     * @example Calculating fees for equity delivery order
     * ```php
     * $fees = $calculator->calculateFees([
     *     'asset_class' => 'equity',
     *     'segment' => 'delivery',
     *     'order_value' => 100000,  // ₹1 lakh
     *     'side' => 'buy',
     *     'quantity' => 100,
     * ]);
     *
     * echo "Total fees: ₹" . $fees['total_fees'];
     * echo "Brokerage: ₹" . $fees['brokerage'];
     * echo "STT: ₹" . $fees['stt'];
     * ```
     */
    public function calculateFees(array $orderData): array;

    /**
     * Estimate pre-trade fees before order placement.
     *
     * Provides a fee estimate to help traders understand costs before
     * placing an order. Useful for:
     * - Displaying fees in order entry UI
     * - Calculating break-even prices
     * - Comparing broker fees
     * - P&L projections
     *
     * **Estimation Accuracy:**
     * - Brokerage: Exact (known in advance)
     * - STT/CTT: Exact (regulatory rates)
     * - Exchange charges: Exact (published rates)
     * - GST: Exact (18% on taxable components)
     * - SEBI charges: Exact (₹10 per crore)
     * - Stamp duty: Exact (state-specific rates)
     *
     * **Note:** Estimates should match actual fees within ₹0.01 tolerance.
     *
     * @param  string  $instrument  Instrument symbol (e.g., 'AAPL', 'NIFTY50', 'GOLD')
     * @param  float  $quantity  Number of shares/contracts to trade
     * @param  float  $price  Expected execution price
     * @param  string  $side  Order side: 'buy' or 'sell'
     * @param  string  $segment  Trading segment:
     *                           - 'intraday': Intraday equity
     *                           - 'delivery': Delivery equity
     *                           - 'futures': Futures contracts
     *                           - 'options': Options contracts
     * @return array Fee estimate with same structure as calculateFees()
     *
     * @example Pre-trade fee estimation
     * ```php
     * // User wants to buy 50 shares of TCS at ₹3500
     * $estimate = $calculator->estimateFees(
     *     instrument: 'TCS',
     *     quantity: 50,
     *     price: 3500,
     *     side: 'buy',
     *     segment: 'delivery'
     * );
     *
     * echo "Estimated total fees: ₹" . $estimate['total_fees'];
     * echo "Break-even price: ₹" . ($price + ($estimate['total_fees'] / $quantity));
     * ```
     * @example Comparing intraday vs delivery fees
     * ```php
     * $intradayFees = $calculator->estimateFees('RELIANCE', 100, 2500, 'buy', 'intraday');
     * $deliveryFees = $calculator->estimateFees('RELIANCE', 100, 2500, 'buy', 'delivery');
     *
     * echo "Intraday fees: ₹" . $intradayFees['total_fees'];
     * echo "Delivery fees: ₹" . $deliveryFees['total_fees'];
     * echo "Savings: ₹" . ($deliveryFees['total_fees'] - $intradayFees['total_fees']);
     * ```
     */
    public function estimateFees(
        string $instrument,
        float $quantity,
        float $price,
        string $side,
        string $segment
    ): array;

    /**
     * Get list of asset classes supported by this fee calculator.
     *
     * Returns array of asset class identifiers that this calculator
     * can handle. Used for:
     * - Validating order data before fee calculation
     * - Routing fee calculations to appropriate calculator
     * - Displaying supported instruments in UI
     *
     * **Standard Asset Classes:**
     * - 'equity': Stocks, ETFs
     * - 'currency': Currency derivatives (USDINR, EURINR, etc.)
     * - 'commodity': Commodity derivatives (GOLD, SILVER, CRUDE, etc.)
     * - 'futures': Index/stock futures
     * - 'options': Index/stock options
     *
     * **Note:** Some brokers may support only a subset of asset classes.
     *
     * @return array List of supported asset class identifiers
     *
     * @example Checking if calculator supports commodity trading
     * ```php
     * $supported = $calculator->getSupportedAssetClasses();
     *
     * if (in_array('commodity', $supported)) {
     *     echo "Commodity trading is supported";
     *     $fees = $calculator->calculateFees([
     *         'asset_class' => 'commodity',
     *         'segment' => 'futures',
     *         // ...
     *     ]);
     * } else {
     *     echo "Commodity trading not supported by this broker";
     * }
     * ```
     * @example Dynamic fee calculator selection
     * ```php
     * function getFeeCalculator(string $assetClass): FeeCalculatorInterface
     * {
     *     $calculators = [
     *         new DhanFeeCalculator(),
     *         new ZerodhaFeeCalculator(),
     *     ];
     *
     *     foreach ($calculators as $calc) {
     *         if (in_array($assetClass, $calc->getSupportedAssetClasses())) {
     *             return $calc;
     *         }
     *     }
     *
     *     throw new Exception("No calculator supports {$assetClass}");
     * }
     * ```
     */
    public function getSupportedAssetClasses(): array;
}
