<?php

namespace TradingPlatform\Domain\Risk\Checks;

use TradingPlatform\Domain\Risk\RiskCheck;
use TradingPlatform\Domain\Order\Order;

class MaxOrderValueCheck implements RiskCheck
{
    private float $limit;

    public function __construct(float $limit)
    {
        $this->limit = $limit;
    }

    public function getName(): string
    {
        return "Max Order Value Check";
    }

    public function check(Order $order): void
    {
        $value = $order->qty * $order->price;
        if ($value > $this->limit) {
            throw new \Exception("Order value {$value} exceeds limit {$this->limit}");
        }
    }
}
