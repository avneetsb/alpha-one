<?php

namespace TradingPlatform\Domain\Risk;

use TradingPlatform\Domain\Order\Order;

interface RiskCheck
{
    public function getName(): string;
    
    /**
     * Check if the order passes the risk check.
     * @throws \Exception if risk check fails
     */
    public function check(Order $order): void;
}
