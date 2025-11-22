<?php

namespace TradingPlatform\Application\Services;

use TradingPlatform\Domain\MarketData\Models\Candle;
use Carbon\Carbon;

class CandleAggregator
{
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

    private function normalizeTimestamp(Carbon $timestamp, string $interval): Carbon
    {
        // Logic to round down timestamp to nearest interval
        // e.g., 10:01:45 -> 10:01:00 for 1m interval
        return $timestamp->startOfMinute(); // Simplified
    }

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
