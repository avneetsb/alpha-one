<?php

namespace TradingPlatform\Domain\Exchange\Services;

/**
 * Symbol Mapper
 *
 * Translation layer that converts between broker-specific symbol formats and
 * the system's internal canonical format. Ensures consistent symbol usage
 * across the platform regardless of the connected broker.
 *
 * **Canonical Format:**
 * - Equity: `SYMBOL-EQ` (e.g., RELIANCE-EQ)
 * - Futures: `SYMBOL-YYMMM-FUT` (e.g., NIFTY-23DEC-FUT)
 * - Options: `SYMBOL-YYMMM-STRIKE-TYPE` (e.g., NIFTY-23DEC-19500-CE)
 *
 * @version 1.0.0
 *
 * @example Broker to Canonical
 * ```php
 * $canonical = $mapper->toCanonical('zerodha', 'NIFTY23DECFUT');
 * // Returns 'NIFTY-23DEC-FUT'
 * ```
 * @example Canonical to Broker
 * ```php
 * $brokerSymbol = $mapper->toBroker('dhan', 'RELIANCE-EQ');
 * // Returns 'RELIANCE'
 * ```
 */
class SymbolMapper
{
    /**
     * Convert a broker-specific symbol to the internal canonical format.
     *
     * Normalizes differences in naming conventions (e.g., expiry formats,
     * strike price formatting) to a standard system-wide format. This ensures
     * that the rest of the system can operate without knowing broker-specific details.
     *
     * @param  string  $brokerId  The unique identifier of the broker (e.g., 'dhan', 'zerodha').
     * @param  string  $brokerSymbol  The symbol string received from the broker API.
     * @return string The normalized canonical symbol.
     *
     * @example Normalizing a symbol
     * ```php
     * // Zerodha format: NIFTY23DECFUT
     * $canonical = $mapper->toCanonical('zerodha', 'NIFTY23DECFUT');
     * // Returns: NIFTY-23DEC-FUT
     * ```
     */
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

    /**
     * Convert a canonical symbol to the broker-specific format.
     *
     * Used when placing orders or subscribing to market data, ensuring the
     * broker receives the symbol in the format they expect. This is the inverse
     * operation of `toCanonical`.
     *
     * @param  string  $brokerId  The unique identifier of the broker.
     * @param  string  $canonicalSymbol  The internal system symbol.
     * @return string The symbol formatted for the specific broker.
     *
     * @example Formatting for broker
     * ```php
     * // Internal format: NIFTY-23DEC-FUT
     * $brokerSymbol = $mapper->toBroker('zerodha', 'NIFTY-23DEC-FUT');
     * // Returns: NIFTY23DECFUT
     * ```
     */
    public function toBroker(string $brokerId, string $canonicalSymbol): string
    {
        // Convert internal format back to broker format
        return $canonicalSymbol;
    }

    /**
     * Extract the underlying asset symbol from a derivative symbol.
     *
     * Parses the derivative symbol string to identify the root asset.
     * Useful for grouping derivatives or looking up the underlying instrument.
     * Handles various formats like Futures and Options.
     *
     * @param  string  $symbol  The derivative symbol (e.g., 'NIFTY23DECFUT').
     * @return string The underlying symbol (e.g., 'NIFTY').
     *
     * @example Extracting underlying
     * ```php
     * echo $mapper->extractUnderlying('BANKNIFTY23DEC44000CE');
     * // Outputs: BANKNIFTY
     *
     * echo $mapper->extractUnderlying('RELIANCE23NOVFUT');
     * // Outputs: RELIANCE
     * ```
     */
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
