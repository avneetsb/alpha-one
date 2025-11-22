<?php

namespace TradingPlatform\Domain\Fees;

/**
 * Fee Calculator Interface
 * 
 * Defines contract for broker-specific fee calculations
 */
interface FeeCalculatorInterface
{
    /**
     * Calculate total fees for an order
     * 
     * @param array $orderData Order details (instrument, quantity, price, side, segment, etc.)
     * @return array Fee breakdown
     */
    public function calculateFees(array $orderData): array;

    /**
     * Estimate pre-trade fees
     * 
     * @param string $instrument
     * @param float $quantity
     * @param float $price
     * @param string $side 'buy' or 'sell'
     * @param string $segment 'intraday', 'delivery', 'futures', 'options'
     * @return array Fee estimate
     */
    public function estimateFees(
        string $instrument,
        float $quantity,
        float $price,
        string $side,
        string $segment
    ): array;

    /**
     * Get supported asset classes
     * 
     * @return array
     */
    public function getSupportedAssetClasses(): array;
}
