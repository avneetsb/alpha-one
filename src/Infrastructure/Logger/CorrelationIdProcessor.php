<?php

namespace TradingPlatform\Infrastructure\Logger;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Class: Correlation ID Processor
 *
 * Monolog processor that injects a unique correlation ID into every log record.
 * Enables distributed tracing of requests across different components and services.
 */
class CorrelationIdProcessor implements ProcessorInterface
{
    /**
     * @var string Static correlation ID shared across the request lifecycle.
     */
    private static string $correlationId;

    /**
     * CorrelationIdProcessor constructor.
     * Initializes a unique ID if not already set.
     */
    public function __construct()
    {
        if (! isset(self::$correlationId)) {
            self::$correlationId = uniqid('corr_', true);
        }
    }

    /**
     * Set the correlation ID for the current request context.
     *
     * Useful when propagating an ID received from an upstream service (e.g., via HTTP headers).
     *
     * @param  string  $id  The correlation ID to set.
     */
    public static function setCorrelationId(string $id): void
    {
        self::$correlationId = $id;
    }

    /**
     * Get the current correlation ID.
     */
    public static function getCorrelationId(): string
    {
        return self::$correlationId;
    }

    /**
     * Invoke the processor.
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['correlation_id'] = self::$correlationId;

        return $record;
    }
}
