<?php

namespace TradingPlatform\Infrastructure\Logger;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Class LoggerService
 *
 * Centralized factory for creating and configuring the application logger.
 * Sets up Monolog with necessary handlers (Stream, Async) and processors
 * (Correlation ID, Sanitizer) to ensure consistent, secure, and traceable logging.
 *
 * @version 1.0.0
 *
 * @example Usage:
 * $logger = LoggerService::getLogger();
 * $logger->info('Backtest started', ['run_id' => $id]);
 */
class LoggerService
{
    /**
     * @var Logger|null Singleton logger instance.
     */
    private static ?Logger $logger = null;

    /**
     * Retrieve the singleton logger instance.
     *
     * Initializes the logger on first access, loading configuration and
     * attaching configured handlers and processors.
     *
     * @return Logger The configured Monolog instance.
     *
     * @example
     * ```php
     * $logger = LoggerService::getLogger();
     * $logger->info('System started');
     * ```
     */
    public static function getLogger(): Logger
    {
        if (self::$logger === null) {
            $config = require __DIR__.'/../../../config/logging.php';
            $channelConfig = $config['channels'][$config['default']];

            // Simple implementation for now, handling 'single' and 'console' drivers
            // In a real app, we might want a more robust factory

            self::$logger = new Logger('app');
            self::$logger->pushProcessor(new CorrelationIdProcessor);
            self::$logger->pushProcessor(new LogSanitizerProcessor);

            if ($channelConfig['driver'] === 'stack') {
                foreach ($channelConfig['channels'] as $channelName) {
                    self::addHandler($config['channels'][$channelName]);
                }
            } else {
                self::addHandler($channelConfig);
            }
        }

        return self::$logger;
    }

    /**
     * Add a handler to the logger based on channel configuration.
     *
     * @param  array  $config  Channel configuration.
     */
    private static function addHandler(array $config): void
    {
        if ($config['driver'] === 'monolog' && $config['handler'] === StreamHandler::class) {
            self::$logger->pushHandler(new StreamHandler(
                $config['with']['stream'],
                $config['with']['level']
            ));
        }
    }
}
