<?php

namespace TradingPlatform\Infrastructure\Logger;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

/**
 * Logger Service
 *
 * Centralized logger factory that builds a Monolog instance based on
 * application configuration. Adds processors for correlation IDs and
 * log sanitization to enhance observability and security.
 *
 * @package TradingPlatform\Infrastructure\Logger
 * @version 1.0.0
 *
 * @example Usage:
 * $logger = LoggerService::getLogger();
 * $logger->info('Backtest started', ['run_id' => $id]);
 */
class LoggerService
{
    private static ?Logger $logger = null;

    /**
     * Get or create the shared logger instance.
     */
    public static function getLogger(): Logger
    {
        if (self::$logger === null) {
            $config = require __DIR__ . '/../../../config/logging.php';
            $channelConfig = $config['channels'][$config['default']];
            
            // Simple implementation for now, handling 'single' and 'console' drivers
            // In a real app, we might want a more robust factory
            
            self::$logger = new Logger('app');
            self::$logger->pushProcessor(new CorrelationIdProcessor());
            self::$logger->pushProcessor(new LogSanitizerProcessor());

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
