<?php

namespace TradingPlatform\Domain\Exchange\Services;

use TradingPlatform\Domain\Exchange\Models\Instrument;

/**
 * Instrument Manager
 *
 * Central service for managing the lifecycle, synchronization, and lookup of
 * financial instruments. Handles the complexity of mapping broker symbols to
 * canonical system symbols and managing derivative relationships (Futures/Options).
 *
 * **Key Responsibilities:**
 * - **Synchronization**: Imports and updates instruments from broker APIs
 * - **Normalization**: Maps broker-specific symbols to internal canonical format
 * - **Derivative Linking**: Associates Futures/Options with their underlying assets
 * - **Option Chain Lookup**: Finds specific option contracts based on spot price (ATM/OTM)
 *
 * @version 1.0.0
 *
 * @example Sync Instruments
 * ```php
 * $manager->syncInstruments('dhan', [
 *     ['symbol' => 'RELIANCE-EQ', 'exchange' => 'NSE', 'type' => 'EQUITY', ...]
 * ]);
 * ```
 * @example Find ATM Call Option
 * ```php
 * $niftySpot = 19540.0;
 * $atmCall = $manager->getOptionBySpotPrice('NIFTY', $niftySpot, 'CE', 0);
 * // Returns NIFTY 19550 CE (nearest strike)
 * ```
 */
class InstrumentManager
{
    /**
     * @var SymbolMapper The symbol mapper service for normalization.
     */
    private SymbolMapper $mapper;

    /**
     * InstrumentManager constructor.
     *
     * @param  SymbolMapper  $mapper  Service to map broker symbols to canonical format.
     */
    public function __construct(SymbolMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * Sync instruments from a broker source.
     *
     * Updates existing instruments or creates new ones based on the broker's master list.
     * Handles normalization of symbols and mapping of instrument attributes. This method
     * is typically called by a scheduled job to keep the instrument database up-to-date.
     *
     * @param  string  $brokerId  The unique identifier of the broker (e.g., 'dhan').
     * @param  array  $brokerInstruments  List of instrument data arrays from the broker.
     *                                    Expected keys: 'symbol', 'exchange', 'type', 'name',
     *                                    'lot_size', 'tick_size', 'expiry', 'strike', 'option_type'.
     *
     * @example Syncing instruments
     * ```php
     * $instruments = [
     *     [
     *         'symbol' => 'RELIANCE-EQ',
     *         'exchange' => 'NSE',
     *         'type' => 'EQUITY',
     *         'name' => 'Reliance Industries',
     *         'lot_size' => 1,
     *         'tick_size' => 0.05
     *     ]
     * ];
     * $manager->syncInstruments('dhan', $instruments);
     * ```
     */
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

    /**
     * Link derivative instruments to their underlying assets.
     *
     * Scans for instruments with expiry dates (Derivatives) and attempts to find
     * their corresponding underlying asset (Equity/Index) to establish a relationship.
     * This relationship is crucial for calculating Greeks, implied volatility, and
     * managing risk across related instruments.
     *
     * @example Linking derivatives
     * ```php
     * // Assuming NIFTY 50 index exists
     * // And NIFTY23DECFUT exists but has no underlying_id
     * $manager->linkDerivatives();
     * // NIFTY23DECFUT now points to NIFTY 50
     * ```
     */
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
     * Find an option contract based on the underlying spot price.
     *
     * Automatically calculates the ATM strike and applies the requested offset
     * to find In-The-Money (ITM) or Out-Of-The-Money (OTM) contracts.
     *
     * @param  string  $underlyingSymbol  Canonical symbol of the underlying (e.g., 'NIFTY').
     * @param  float  $spotPrice  Current market price of the underlying.
     * @param  string  $optionType  'CE' (Call) or 'PE' (Put).
     * @param  int  $offset  Strike offset from ATM.
     *                       0 = ATM (At The Money)
     *                       +1 = 1 Strike OTM (Out of The Money)
     *                       -1 = 1 Strike ITM (In The Money)
     * @param  \DateTime|null  $targetExpiry  Specific expiry date. If null, finds nearest expiry.
     * @return Instrument|null The matching option instrument, or null if not found.
     *
     * @example Finding an OTM Call
     * ```php
     * // NIFTY at 19540, Strike Interval 50 -> ATM is 19550
     * // Offset +1 for CE -> 19600 Strike
     * $option = $manager->getOptionBySpotPrice('NIFTY', 19540, 'CE', 1);
     * ```
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
     * Find a continuous chain of options for backtesting.
     *
     * Retrieves a sequence of option contracts over a date range to simulate
     * rolling positions or long-term backtests on expiring assets.
     *
     * @param  string  $underlyingSymbol  The underlying symbol.
     * @param  float  $strike  Fixed strike price (simplified).
     * @param  string  $optionType  'CE' or 'PE'.
     * @param  \DateTime  $startDate  Start of the backtest period.
     * @param  \DateTime  $endDate  End of the backtest period.
     * @return array List of Instrument objects covering the period.
     *
     * @example Fetching option chain
     * ```php
     * $chain = $manager->findContinuousOptionChain(
     *     'NIFTY',
     *     19000,
     *     'CE',
     *     new DateTime('2023-01-01'),
     *     new DateTime('2023-03-31')
     * );
     * ```
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

    /**
     * Get the strike interval for a given symbol.
     *
     * Used to calculate ATM strikes.
     *
     * @param  string  $symbol  The symbol name (e.g., 'NIFTY').
     * @return float The strike interval (e.g., 50.0).
     */
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
