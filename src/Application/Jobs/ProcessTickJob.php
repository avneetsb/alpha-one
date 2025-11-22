<?php

namespace TradingPlatform\Application\Jobs;

use TradingPlatform\Infrastructure\Logger\LoggerService;

class ProcessTickJob
{
    protected $tickData;

    public function __construct(array $tickData)
    {
        $this->tickData = $tickData;
    }

    public function handle()
    {
        $logger = LoggerService::getLogger();
        // In a real app, this would save to DB, update cache, trigger strategy, etc.
        // For now, we just log it to verify async processing.
        $logger->info("Processing Tick Async: " . json_encode($this->tickData));
    }
}
