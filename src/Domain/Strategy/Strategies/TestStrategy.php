<?php

namespace TradingPlatform\Domain\Strategy\Strategies;

use TradingPlatform\Domain\Strategy\Strategy;
use TradingPlatform\Domain\Strategy\Signal;
use TradingPlatform\Domain\MarketData\Tick;
use TradingPlatform\Domain\MarketData\Candle;

class TestStrategy extends Strategy
{
    public function onTick(Tick $tick): ?Signal
    {
        // Simple logic: if price > 100, buy
        if ($tick->price > 100) {
            return new Signal(
                $tick->instrument_id,
                Signal::BUY,
                $tick->price,
                1,
                $this->getName(),
                95.0,
                105.0
            );
        }
        return null;
    }

    public function onCandle(Candle $candle): ?Signal
    {
        return null;
    }
}
