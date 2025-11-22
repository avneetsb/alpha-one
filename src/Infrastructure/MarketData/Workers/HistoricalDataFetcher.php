<?php

namespace TradingPlatform\Infrastructure\MarketData\Workers;

use Illuminate\Support\Facades\Redis;
use TradingPlatform\Domain\Exchange\Models\Instrument;
use TradingPlatform\Infrastructure\Logging\AsyncLogger;

class HistoricalDataFetcher
{
    private AsyncLogger $logger;
    private $brokerAdapter; // Injected broker adapter

    public function __construct(AsyncLogger $logger)
    {
        $this->logger = $logger;
        // $this->brokerAdapter = $brokerAdapter;
    }

    public function fetch(string $instrumentSymbol, \DateTime $from, \DateTime $to, string $interval): void
    {
        $jobId = uniqid('hist_fetch_');
        $this->logger->info("Starting historical fetch", ['job_id' => $jobId, 'symbol' => $instrumentSymbol]);

        $currentFrom = $from;
        
        // Check for existing checkpoint
        $checkpointKey = "hist:checkpoint:{$instrumentSymbol}:{$interval}";
        if ($lastCheckpoint = Redis::get($checkpointKey)) {
            $currentFrom = new \DateTime($lastCheckpoint);
            $this->logger->info("Resuming from checkpoint", ['checkpoint' => $lastCheckpoint]);
        }

        while ($currentFrom < $to) {
            // Fetch in chunks (e.g., 1 day or 1000 candles)
            $chunkTo = (clone $currentFrom)->modify('+1 day');
            if ($chunkTo > $to) $chunkTo = $to;

            try {
                // $candles = $this->brokerAdapter->fetchHistory($instrumentSymbol, $currentFrom, $chunkTo, $interval);
                // $this->persistCandles($candles);
                
                // Update checkpoint
                Redis::set($checkpointKey, $chunkTo->format('Y-m-d H:i:s'));
                
                $currentFrom = $chunkTo;
                
                // Rate limit pause
                usleep(200000); // 200ms
            } catch (\Exception $e) {
                $this->logger->error("Fetch failed", ['error' => $e->getMessage()]);
                // Exponential backoff logic here
                break;
            }
        }

        $this->logger->info("Historical fetch completed", ['job_id' => $jobId]);
    }
}
