<?php

/**
 * Queue configuration for the trading platform application.
 *
 * Defines all queue connections and routing used by background workers
 * (e.g., order processing, market data ingestion, reconciliation, reporting).
 * Uses Redis as the primary queue backend with separate queues for
 * prioritization.
 *
 * @package Config
 * @author  Trading Platform Team
 * @version 1.0.0
 *
 * @structure
 * - default: Default queue connection
 * - connections: Named queue connections with driver settings
 *   - redis: Default Redis-backed queue
 *   - high: High-priority jobs (risk checks, order placements)
 *   - low: Low-priority jobs (report generation, analytics)
 *
 * @environment_variables
 * - QUEUE_CONNECTION: Default queue connection name
 * - REDIS_QUEUE: Default Redis queue name
 *
 * @accepted_values
 * - default connection: 'redis', 'high', 'low' (by connection names defined below)
 * - queues: 'default', 'high', 'low'
 *
 * @example
 * // Dispatch a job to the high-priority queue:
 * // Queue::connection('high')->push(new ProcessOrderJob($payload));
 *
 * @important Use separate queues to prevent low-priority jobs from delaying
 *           critical trading operations.
 */

return [
    'default' => env('QUEUE_CONNECTION', 'redis'),

    'connections' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
        ],
        'high' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'high',
            'retry_after' => 90,
            'block_for' => null,
        ],
        'low' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'low',
            'retry_after' => 90,
            'block_for' => null,
        ],
    ],
];
