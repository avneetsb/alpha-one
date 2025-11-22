<?php

namespace TradingPlatform\Application\Jobs;

use TradingPlatform\Infrastructure\Logger\LoggerService;

class LogMessageJob
{
    protected $level;
    protected $message;
    protected $context;

    public function __construct(string $level, string $message, array $context = [])
    {
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
    }

    public function handle()
    {
        // This job is processed by the worker.
        // We use the LoggerService to actually log the message.
        // Since this runs in a worker, we might want to ensure we don't create an infinite loop 
        // if the logger itself tries to push to queue.
        // For now, we assume the worker uses a different log channel or the same one.
        
        $logger = LoggerService::getLogger();
        $logger->log($this->level, "[ASYNC] " . $this->message, $this->context);
    }
}
