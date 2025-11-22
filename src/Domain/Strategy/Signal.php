<?php

namespace TradingPlatform\Domain\Strategy;

/**
 * Trading Signal
 *
 * Encapsulates an actionable recommendation produced by a strategy.
 * Includes instrument, action (BUY/SELL/HOLD), price, quantity, optional
 * risk controls (stop loss, take profit), and metadata for traceability.
 *
 * @package TradingPlatform\Domain\Strategy
 * @version 1.0.0
 *
 * @example Create signal:
 * $signal = new Signal('RELIANCE', Signal::BUY, 2510.5, 100, 'RSI_Momentum', 2450.0, 2600.0);
 */
class Signal
{
    public const BUY = 'BUY';
    public const SELL = 'SELL';
    public const HOLD = 'HOLD';

    public string $instrumentId;
    public string $action;
    public float $price;
    public int $quantity;
    public ?float $stopLoss;
    public ?float $takeProfit;
    public string $strategyName;
    public array $metadata;

    public function __construct(
        string $instrumentId,
        string $action,
        float $price,
        int $quantity,
        string $strategyName,
        ?float $stopLoss = null,
        ?float $takeProfit = null,
        array $metadata = []
    ) {
        $this->instrumentId = $instrumentId;
        $this->action = $action;
        $this->price = $price;
        $this->quantity = $quantity;
        $this->strategyName = $strategyName;
        $this->stopLoss = $stopLoss;
        $this->takeProfit = $takeProfit;
        $this->metadata = $metadata;
    }
}
