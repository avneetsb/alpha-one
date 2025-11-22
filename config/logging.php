<?php

use Monolog\Handler\StreamHandler;
use Monolog\Level;

/**
 * Logging configuration for the trading platform application.
 * 
 * This configuration file defines all logging channels and handlers used by the
 * trading platform. It supports multiple log channels, different log levels,
 * and various output destinations including files, console, and external services.
 * 
 * @package Config
 * @author  Trading Platform Team
 * @version 1.0.0
 * 
 * @structure
 * - default: Default logging channel
 * - channels: Array of available log channels with their configurations
 * 
 * @environment_variables
 * - LOG_CHANNEL: Default logging channel (stack, single, console, etc.)
 *
 * @accepted_values
 * - LOG_CHANNEL: 'stack', 'single', 'console' (extendable: 'daily', 'slack', 'syslog')
 * 
 * @dependencies
 * - Monolog\Handler\StreamHandler: For file and console output
 * - Monolog\Level: For log level constants
 * 
 * @example
 * // Environment configuration:
 * LOG_CHANNEL=stack
 * 
 * // Usage in application:
 * use Illuminate\Support\Facades\Log;
 * 
 * Log::info('Order placed successfully', [
 *     'order_id' => 12345,
 *     'instrument' => 'RELIANCE',
 *     'quantity' => 100,
 *     'price' => 2500.50
 * ]);
 * 
 * Log::error('Order execution failed', [
 *     'order_id' => 12346,
 *     'error' => 'Insufficient margin',
 *     'available_margin' => 50000,
 *     'required_margin' => 75000
 * ]);
 * 
 * // Channel-specific logging:
 * Log::channel('console')->debug('Strategy calculation completed');
 * 
 * @log_levels
 * - DEBUG: Detailed debug information (Level::Debug)
 * - INFO: Interesting events (Level::Info)
 * - NOTICE: Normal but significant events (Level::Notice)
 * - WARNING: Exceptional occurrences that are not errors (Level::Warning)
 * - ERROR: Runtime errors not requiring immediate action (Level::Error)
 * - CRITICAL: Critical conditions (Level::Critical)
 * - ALERT: Action must be taken immediately (Level::Alert)
 * - EMERGENCY: System is unusable (Level::Emergency)
 * 
 * @important For trading applications, consider these logging practices:
 * - Log all order placements, modifications, and cancellations
 * - Log strategy signals and decisions with context
 * - Log market data updates for audit trails
 * - Log risk management events (margin calls, stop losses)
 * - Log system health and performance metrics
 * - Ensure logs don't contain sensitive data (passwords, tokens)
 * - Implement log rotation to manage disk space
 * - Consider centralized logging for distributed systems
 * 
 * @note The 'stack' channel allows multiple handlers to process the same log
 *       record, useful for both file and console output simultaneously.
 */

return [
    /**
     * Default logging channel.
     * 
     * Specifies which logging channel should be used by default when
     * no specific channel is requested. This value is typically set
     * via the LOG_CHANNEL environment variable.
     * 
     * @var string
     * @default 'stack'
     * @options 'stack', 'single', 'console', 'daily', 'slack', 'syslog'
     * 
     * @example Environment configuration:
     * // Development: Detailed logging to console
     * LOG_CHANNEL=console
     * 
     * // Production: File-based logging with rotation
     * LOG_CHANNEL=daily
     * 
     * // Debug: Maximum verbosity
     * LOG_CHANNEL=stack
     */
    'default' => env('LOG_CHANNEL', 'stack'),

    /**
     * Logging channel configurations.
     * 
     * Defines all available logging channels with their specific
     * handlers, formatters, and output destinations.
     */
    'channels' => [
        /**
         * Stack logging channel.
         * 
         * Allows multiple handlers to process the same log record simultaneously.
         * Useful for sending logs to both file and console at the same time.
         * 
         * @structure
         * - driver: 'stack' for multi-handler processing
         * - channels: Array of channel names to stack
         * - ignore_exceptions: Whether to ignore handler exceptions
         * 
         * @usage
         * // Logs to both 'single' and 'console' channels:
         * Log::channel('stack')->info('Order executed', ['id' => 12345]);
         * 
         * @note The stack channel is ideal for development environments where
         *       you want to see logs both in files and on screen.
         */
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        /**
         * Single file logging channel.
         * 
         * Logs all messages to a single file using Monolog's StreamHandler.
         * Suitable for applications with moderate log volume.
         * 
         * @structure
         * - driver: 'monolog' for Monolog integration
         * - handler: StreamHandler::class for file output
         * - with.stream: Log file path
         * - with.level: Minimum log level (Level::Debug)
         * 
         * @example File output:
         * // Logs are written to: storage/logs/app.log
         * [2024-01-15 14:30:45] trading-platform.INFO: Order placed successfully {"order_id": 12345}
         * [2024-01-15 14:30:46] trading-platform.ERROR: Order execution failed {"order_id": 12346, "error": "Insufficient funds"}
         * 
         * @important Consider log rotation for production use to prevent
         *          large log files from consuming disk space.
         * 
         * @note The Level::Debug includes all log levels from DEBUG to EMERGENCY.
         */
        'single' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'with' => [
                'stream' => __DIR__ . '/../storage/logs/app.log',
                'level' => Level::Debug,
            ],
        ],

        /**
         * Console logging channel.
         * 
         * Outputs log messages directly to the console/terminal.
         * Ideal for development environments and command-line tools.
         * 
         * @structure
         * - driver: 'monolog' for Monolog integration
         * - handler: StreamHandler::class for console output
         * - with.stream: 'php://stdout' for standard output
         * - with.level: Minimum log level (Level::Debug)
         * 
         * @usage
         * // Console output example:
         * php artisan strategy:test --verbose
         * // Output: [2024-01-15 14:30:45] Strategy backtest started
         * //         [2024-01-15 14:30:46] Processing 1000 historical candles
         * //         [2024-01-15 14:30:47] Backtest completed: 15.2% returns
         * 
         * @note Console logging is particularly useful for:
         *       - Long-running processes (backtests, optimizations)
         *       - Command-line tools and artisan commands
         *       - Development debugging
         *       - Real-time monitoring of trading operations
         * 
         * @important Console logs are not persisted and will be lost when the
         *          terminal session ends. Use file-based logging for audit trails.
         */
        'console' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'with' => [
                'stream' => 'php://stdout',
                'level' => Level::Debug,
            ],
        ],
    ],
];
