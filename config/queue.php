<?php

/**
 * Queue Configuration
 *
 * This configuration file defines the queue infrastructure for asynchronous job processing.
 * The trading platform relies heavily on queues to offload time-consuming or blocking
 * operations from the main execution path, ensuring low latency for critical path
 * activities like order placement.
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 */

return [
    /**
     * Default Queue Connection
     *
     * The name of the default connection to use when dispatching jobs.
     *
     * @var string
     */
    'default' => env('QUEUE_CONNECTION', 'redis'),

    /**
     * Queue Connections
     *
     * Defines the available queue connections and their configurations.
     * The platform uses a priority-based queue system:
     *
     * 1. High Priority ('high'):
     *    - Critical trading operations (e.g., Order Placement, Risk Checks).
     *    - Must be processed immediately with minimal latency.
     *
     * 2. Default Priority ('default'):
     *    - Standard background tasks (e.g., Trade Reconciliation, Position Updates).
     *
     * 3. Low Priority ('low'):
     *    - Non-critical, deferrable tasks (e.g., End-of-day Reports, Historical Data Sync).
     */
    'connections' => [
        /**
         * Default Redis Queue
         *
         * Standard queue for general-purpose jobs.
         */
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
        ],

        /**
         * High Priority Queue
         *
         * Reserved for latency-sensitive operations. Workers listening to this
         * queue should be scaled to ensure near-zero wait times.
         */
        'high' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'high',
            'retry_after' => 90,
            'block_for' => null,
        ],

        /**
         * Low Priority Queue
         *
         * Used for bulk processing and reporting tasks that can tolerate delays.
         */
        'low' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'low',
            'retry_after' => 90,
            'block_for' => null,
        ],
    ],
];
