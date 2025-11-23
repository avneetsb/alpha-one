<?php

use Monolog\Handler\StreamHandler;
use Monolog\Level;

/**
 * Logging Configuration
 *
 * This configuration file defines the logging infrastructure for the trading platform.
 * Effective logging is crucial for:
 * 1. Audit Trails: Recording every trade decision and execution for compliance.
 * 2. Debugging: Tracing complex strategy logic and order routing paths.
 * 3. Monitoring: Detecting system anomalies, latency spikes, and error rates.
 *
 * The platform uses Monolog, allowing for sophisticated log routing. Logs can be
 * sent to files, the console, or external monitoring services (e.g., ELK stack, Datadog)
 * depending on the environment.
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 */

return [
    /**
     * Default Log Channel
     *
     * The default channel used when `Log::info()` or similar methods are called
     * without specifying a channel.
     *
     * @var string
     *
     * @see config/logging.php['channels']
     */
    'default' => env('LOG_CHANNEL', 'stack'),

    /**
     * Log Channels
     *
     * Defines the available logging channels.
     */
    'channels' => [
        /**
         * Stack Channel
         *
         * A meta-channel that broadcasts log records to multiple other channels.
         * This is the default in most environments to ensure logs are both
         * persisted (e.g., to a file) and visible (e.g., in the console).
         *
         * @driver stack
         *
         * @channels ['single'] (Can be extended to include 'daily', 'slack', etc.)
         */
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        /**
         * Single File Channel
         *
         * Writes all logs to a single file (`storage/logs/app.log`).
         * Useful for local development and simple deployments.
         *
         * @driver monolog
         *
         * @handler StreamHandler
         *
         * @level debug (Captures everything from debug info to emergencies)
         */
        'single' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'with' => [
                'stream' => __DIR__.'/../storage/logs/app.log',
                'level' => Level::Debug,
            ],
        ],

        /**
         * Console Channel
         *
         * Writes logs directly to `php://stdout`.
         * Essential for containerized environments (Docker/Kubernetes) where
         * logs are collected from stdout by the container runtime.
         *
         * @driver monolog
         *
         * @handler StreamHandler
         *
         * @stream php://stdout
         *
         * @level debug
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
