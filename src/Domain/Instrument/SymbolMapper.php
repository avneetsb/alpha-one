<?php

namespace TradingPlatform\Domain\Instrument;

class SymbolMapper
{
    // In a real app, this would be backed by a DB table or cache.
    // For demo, we use a simple array map.
    
    private array $map = [
        // Internal Symbol => [Broker => Broker Symbol]
        'NSE:RELIANCE-EQ' => [
            'dhan' => 'RELIANCE',
            'zerodha' => 'RELIANCE',
        ],
        'NSE:TCS-EQ' => [
            'dhan' => 'TCS',
            'zerodha' => 'TCS',
        ],
    ];

    public function getBrokerSymbol(string $internalSymbol, string $broker): ?string
    {
        return $this->map[$internalSymbol][$broker] ?? null;
    }

    public function getInternalSymbol(string $brokerSymbol, string $broker): ?string
    {
        foreach ($this->map as $internal => $brokers) {
            if (($brokers[$broker] ?? '') === $brokerSymbol) {
                return $internal;
            }
        }
        return null;
    }
}
