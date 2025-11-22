<?php

namespace TradingPlatform\Domain\Exchange\Services;

use TradingPlatform\Domain\Exchange\Models\Instrument;

class InstrumentManager
{
    private SymbolMapper $mapper;

    public function __construct(SymbolMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public function syncInstruments(string $brokerId, array $brokerInstruments): void
    {
        foreach ($brokerInstruments as $data) {
            $canonicalSymbol = $this->mapper->toCanonical($brokerId, $data['symbol']);
            
            Instrument::updateOrCreate(
                [
                    'broker_id' => $brokerId,
                    'broker_symbol' => $data['symbol'],
                ],
                [
                    'symbol' => $canonicalSymbol,
                    'name' => $data['name'] ?? $canonicalSymbol,
                    'exchange' => $data['exchange'],
                    'type' => $data['type'], // EQUITY, FUTURE, OPTION
                    'lot_size' => $data['lot_size'] ?? 1,
                    'tick_size' => $data['tick_size'] ?? 0.05,
                    'expiry' => $data['expiry'] ?? null,
                    'strike' => $data['strike'] ?? null,
                    'option_type' => $data['option_type'] ?? null, // CE/PE
                    'is_tradable' => $data['is_tradable'] ?? true,
                    'status' => 'active',
                    'last_synced_at' => now(),
                ]
            );
        }
    }

    public function linkDerivatives(): void
    {
        // Link futures/options to underlying
        $derivatives = Instrument::whereNotNull('expiry')->get();
        
        foreach ($derivatives as $derivative) {
            // Logic to find underlying based on symbol convention
            // e.g., NIFTY23DECFUT -> NIFTY 50
            $underlyingSymbol = $this->mapper->extractUnderlying($derivative->symbol);
            
            $underlying = Instrument::where('symbol', $underlyingSymbol)
                ->whereNull('expiry')
                ->first();

            if ($underlying) {
                $derivative->underlying_id = $underlying->id;
                $derivative->save();
            }
        }
    }

    /**
     * Get option instrument based on spot price (ATM/OTM/ITM)
     * 
     * @param string $underlyingSymbol e.g., NIFTY
     * @param float $spotPrice Current spot price
     * @param string $optionType 'CE' or 'PE'
     * @param int $offset Strike offset (0=ATM, 1=Next OTM, -1=Next ITM for CE)
     * @param \DateTime|null $targetExpiry Target expiry date (optional, defaults to nearest)
     */
    public function getOptionBySpotPrice(
        string $underlyingSymbol,
        float $spotPrice,
        string $optionType,
        int $offset = 0,
        ?\DateTime $targetExpiry = null
    ): ?Instrument {
        $strikeInterval = $this->getStrikeInterval($underlyingSymbol);
        $atmStrike = round($spotPrice / $strikeInterval) * $strikeInterval;
        
        // Calculate target strike based on offset
        // For CE: +offset is OTM (higher strike), -offset is ITM (lower strike)
        // For PE: +offset is OTM (lower strike), -offset is ITM (higher strike)
        if ($optionType === 'CE') {
            $targetStrike = $atmStrike + ($offset * $strikeInterval);
        } else {
            $targetStrike = $atmStrike - ($offset * $strikeInterval);
        }

        $query = Instrument::where('type', 'OPTION')
            ->where('option_type', $optionType)
            ->where('strike', $targetStrike)
            ->where('symbol', 'LIKE', "{$underlyingSymbol}%") // Simplified lookup
            ->where('expiry', '>=', now()->format('Y-m-d'));

        if ($targetExpiry) {
            $query->where('expiry', $targetExpiry->format('Y-m-d'));
        } else {
            $query->orderBy('expiry', 'asc');
        }

        return $query->first();
    }

    /**
     * Find continuous chain of options for backtesting
     * 
     * @param string $underlyingSymbol
     * @param float $strike Fixed strike price (or use dynamic logic in loop)
     * @param string $optionType 'CE' or 'PE'
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     */
    public function findContinuousOptionChain(
        string $underlyingSymbol,
        float $strike,
        string $optionType,
        \DateTime $startDate,
        \DateTime $endDate
    ): array {
        // Fetch all relevant options in the date range
        $options = Instrument::where('type', 'OPTION')
            ->where('symbol', 'LIKE', "{$underlyingSymbol}%")
            ->where('option_type', $optionType)
            ->where('strike', $strike)
            ->where('expiry', '>=', $startDate->format('Y-m-d'))
            ->orderBy('expiry', 'asc')
            ->get();

        $chain = [];
        $currentExpiry = null;

        foreach ($options as $option) {
            // Logic to stitch contracts:
            // Use contract until X days before expiry, then switch to next
            // Simplified: just add all sequential contracts
            
            if ($option->expiry > $endDate) {
                break;
            }
            
            $chain[] = $option;
        }

        return $chain;
    }

    private function getStrikeInterval(string $symbol): float
    {
        // Hardcoded for major indices, could be config-driven
        return match ($symbol) {
            'NIFTY' => 50.0,
            'BANKNIFTY' => 100.0,
            'FINNIFTY' => 50.0,
            default => 1.0, // Default for stocks
        };
    }
}
