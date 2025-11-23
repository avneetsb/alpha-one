<?php

namespace TradingPlatform\Application\Jobs;

use TradingPlatform\Infrastructure\Logger\LoggerService;

/**
 * Job: Process Tick
 *
 * Handles asynchronous processing of high-frequency market tick data.
 * Offloads tick processing (e.g., strategy updates, indicator calculation)
 * from the WebSocket consumer to the queue workers.
 */
class ProcessTickJob
{
    /**
     * @var array Tick data.
     */
    protected $tickData;

    /**
     * Create a new job instance.
     *
     * @param  array  $tickData  Associative array of tick data (price, volume, timestamp).
     *
     * @example Dispatch tick processing
     * ```php
     * dispatch(new ProcessTickJob([
     *     'symbol' => 'BTC/USD',
     *     'price' => 50000.00,
     *     'volume' => 1.5,
     *     'ts' => 1630000000
     * ]));
     * ```
     */
    public function __construct(array $tickData)
    {
        $this->tickData = $tickData;
    }

    /**
     * Execute the job.
     *
     * Logs the tick data (placeholder for actual processing logic like
     * updating indicators or triggering strategy signals).
     *
     * @return void
     */
    public function handle()
    {
        $logger = LoggerService::getLogger();
        // In a real app, this would save to DB, update cache, trigger strategy, etc.
        // For now, we just log it to verify async processing.
        $logger->info('Processing Tick Async: '.json_encode($this->tickData));
    }
}
