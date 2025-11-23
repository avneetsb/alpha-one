<?php

namespace TradingPlatform\Application\Services;

use Carbon\Carbon;
use TradingPlatform\Domain\MarketData\Models\Candle;

/**
 * Class CandleAggregator
 *
 * Aggregates tick data into candles and fills gaps in candle series.
 * This service is essential for converting raw high-frequency market data
 * into structured OHLCV (Open, High, Low, Close, Volume) format suitable
 * for technical analysis and charting.
 *
 * @version 1.0.0
 */
class CandleAggregator
{
    /**
     * Aggregate a list of ticks into a single candle.
     *
     * Processes a raw array of tick data to compute the Open, High, Low, Close,
     * and Volume for a specific time interval.
     *
     * @param  array  $ticks  Array of tick data (associative arrays with 'price', 'volume', 'timestamp').
     * @param  string  $interval  The candle interval (e.g., '1m', '5m').
     * @return Candle|null The aggregated candle or null if ticks are empty.
     *
     * @example Aggregating ticks
     * ```php
     * $ticks = [
     *     ['price' => 100, 'volume' => 10, 'timestamp' => '2023-01-01 10:00:01'],
     *     ['price' => 105, 'volume' => 5, 'timestamp' => '2023-01-01 10:00:05'],
     *     ['price' => 98, 'volume' => 20, 'timestamp' => '2023-01-01 10:00:59'],
     * ];
     * $candle = $aggregator->aggregate($ticks, '1m');
     * // Result: Open=100, High=105, Low=98, Close=98, Vol=35
     * ```
     */
    public function aggregate(array $ticks, string $interval): ?Candle
    {
        if (empty($ticks)) {
            return null;
        }

        $open = $ticks[0]['price'];
        $close = $ticks[count($ticks) - 1]['price'];
        $high = $ticks[0]['price'];
        $low = $ticks[0]['price'];
        $volume = 0;

        foreach ($ticks as $tick) {
            $high = max($high, $tick['price']);
            $low = min($low, $tick['price']);
            $volume += $tick['volume'];
        }

        return new Candle([
            'open' => $open,
            'high' => $high,
            'low' => $low,
            'close' => $close,
            'volume' => $volume,
            'timestamp' => $this->normalizeTimestamp($ticks[0]['timestamp'], $interval),
            'interval' => $interval,
        ]);
    }

    /**
     * Fill gaps in a candle series with synthetic candles (DoJI).
     *
     * Ensures a continuous time series by inserting synthetic candles where data is missing.
     * Synthetic candles typically use the previous close price for Open, High, Low, and Close,
     * with zero volume, effectively representing a flat market during the gap.
     *
     * @param  array  $candles  Array of Candle objects.
     * @param  string  $interval  The candle interval.
     * @param  Carbon  $start  Start time of the range.
     * @param  Carbon  $end  End time of the range.
     * @return array The filled array of candles.
     *
     * @example Filling gaps
     * ```php
     * $candles = [...]; // Missing 10:01 candle
     * $filled = $aggregator->fillGaps($candles, '1m', $start, $end);
     * // Result includes a synthetic candle at 10:01
     * ```
     */
    public function fillGaps(array $candles, string $interval, Carbon $start, Carbon $end): array
    {
        $filled = [];
        $current = $start->copy();
        $candleMap = [];

        foreach ($candles as $candle) {
            $candleMap[$candle->timestamp->format('Y-m-d H:i:s')] = $candle;
        }

        while ($current <= $end) {
            $key = $current->format('Y-m-d H:i:s');

            if (isset($candleMap[$key])) {
                $filled[] = $candleMap[$key];
            } else {
                // Fill with previous close (DoJI)
                $prevCandle = end($filled);
                $price = $prevCandle ? $prevCandle->close : 0;

                $filled[] = new Candle([
                    'open' => $price,
                    'high' => $price,
                    'low' => $price,
                    'close' => $price,
                    'volume' => 0,
                    'timestamp' => $current->copy(),
                    'interval' => $interval,
                    'is_synthetic' => true,
                ]);
            }

            $current = $this->incrementTimestamp($current, $interval);
        }

        return $filled;
    }

    /**
     * Normalize a timestamp to the start of the interval.
     *
     * @param  Carbon  $timestamp  The timestamp to normalize.
     * @param  string  $interval  The candle interval.
     * @return Carbon Normalized timestamp.
     */
    private function normalizeTimestamp(Carbon $timestamp, string $interval): Carbon
    {
        // Logic to round down timestamp to nearest interval
        // e.g., 10:01:45 -> 10:01:00 for 1m interval
        return $timestamp->startOfMinute(); // Simplified
    }

    /**
     * Increment a timestamp by the given interval.
     *
     * @param  Carbon  $timestamp  The timestamp to increment.
     * @param  string  $interval  The interval string (e.g., '1m', '5m', '1h').
     * @return Carbon Incremented timestamp.
     */
    private function incrementTimestamp(Carbon $timestamp, string $interval): Carbon
    {
        // Parse interval string (e.g., '1m', '5m', '1h')
        if (str_ends_with($interval, 'm')) {
            return $timestamp->addMinutes((int) $interval);
        }
        if (str_ends_with($interval, 'h')) {
            return $timestamp->addHours((int) $interval);
        }
        if (str_ends_with($interval, 's')) {
            return $timestamp->addSeconds((int) $interval);
        }

        return $timestamp->addMinute();
    }
}
