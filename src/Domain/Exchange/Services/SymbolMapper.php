<?php

namespace TradingPlatform\Domain\Exchange\Services;

class SymbolMapper
{
    public function toCanonical(string $brokerId, string $brokerSymbol): string
    {
        // Normalize to internal format: SYMBOL-EXPIRY-STRIKE-TYPE
        // Example: NIFTY23DEC21000CE
        
        // This would contain broker-specific regex or logic
        if ($brokerId === 'dhan') {
            return $brokerSymbol; // Dhan symbols are usually clean, but normalization logic goes here
        }

        return $brokerSymbol;
    }

    public function toBroker(string $brokerId, string $canonicalSymbol): string
    {
        // Convert internal format back to broker format
        return $canonicalSymbol;
    }

    public function extractUnderlying(string $symbol): string
    {
        // Extract underlying from derivative symbol
        // NIFTY23DECFUT -> NIFTY
        // BANKNIFTY23DEC44000CE -> BANKNIFTY
        
        if (preg_match('/^([A-Z]+)\d{2}[A-Z]{3}/', $symbol, $matches)) {
            return $matches[1];
        }
        
        return $symbol;
    }
}
