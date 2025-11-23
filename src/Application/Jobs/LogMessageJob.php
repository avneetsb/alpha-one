<?php

namespace TradingPlatform\Application\Jobs;

use TradingPlatform\Infrastructure\Logger\LoggerService;

/**
 * Job: Log Message
 *
 * Handles asynchronous logging to prevent blocking the main execution flow.
 * Dispatches log entries to the queue for background processing.
 */
class LogMessageJob
{
    /**
     * @var string Log level (e.g., 'info', 'error').
     */
    protected $level;

    /**
     * @var string Log message.
     */
    protected $message;

    /**
     * @var array Context data.
     */
    protected $context;

    /**
     * Create a new job instance.
     *
     * @param  string  $level  Log level (e.g., 'info', 'error', 'warning').
     * @param  string  $message  The log message content.
     * @param  array  $context  Additional context data (e.g., user_id, order_id).
     *
     * @example Dispatch a log job
     * ```php
     * dispatch(new LogMessageJob('error', 'Order failed', ['order_id' => 123]));
     * ```
     */
    public function __construct(string $level, string $message, array $context = [])
    {
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
    }

    /**
     * Execute the job.
     *
     * Retrieves the logger instance and writes the message to the log channel.
     * Prefixes the message with `[ASYNC]` to distinguish it from synchronous logs.
     *
     * @return void
     */
    public function handle()
    {
        // This job is processed by the worker.
        // We use the LoggerService to actually log the message.
        // Since this runs in a worker, we might want to ensure we don't create an infinite loop
        // if the logger itself tries to push to queue.
        // For now, we assume the worker uses a different log channel or the same one.

        $logger = LoggerService::getLogger();
        $logger->log($this->level, '[ASYNC] '.$this->message, $this->context);
    }
}
