<?php

namespace TradingPlatform\Infrastructure\Logger;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class CorrelationIdProcessor implements ProcessorInterface
{
    private static string $correlationId;

    public function __construct()
    {
        if (!isset(self::$correlationId)) {
            self::$correlationId = uniqid('corr_', true);
        }
    }

    public static function setCorrelationId(string $id): void
    {
        self::$correlationId = $id;
    }

    public static function getCorrelationId(): string
    {
        return self::$correlationId;
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['correlation_id'] = self::$correlationId;
        return $record;
    }
}
