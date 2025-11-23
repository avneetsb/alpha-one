<?php

/**
 * Broker API Configuration
 *
 * This configuration file serves as the central registry for all broker integrations
 * within the trading platform. It defines the connection parameters, authentication
 * credentials, and service endpoints required to communicate with external brokerage
 * APIs.
 *
 * The platform is designed to be multi-broker capable, allowing strategies to
 * execute across different brokerage accounts seamlessly. Currently, the Dhan
 * broker integration is fully implemented, with a standardized structure in place
 * for adding future integrations (e.g., Zerodha, Upstox).
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 *
 * @see \TradingPlatform\Infrastructure\Broker\Dhan\DhanAdapter
 * @see \TradingPlatform\Infrastructure\Broker\Dhan\DhanClient
 */

return [
    /**
     * Dhan Broker Configuration
     *
     * Configuration settings for the Dhan trading API integration. Dhan provides
     * a comprehensive REST API for order management and a WebSocket feed for
     * real-time market data.
     *
     * @link https://dhan.co/developer/api/ Official Dhan API Documentation
     *
     * @var array
     *
     * @configuration_options
     * - base_uri: The root URL for all REST API requests.
     *   - Production: https://api.dhan.co/v2/
     *   - Sandbox: (If available, otherwise use production with test credentials)
     *
     * - ws_uri: The WebSocket endpoint for streaming market data.
     *   - Production: wss://api-feed.dhan.co
     *
     * - client_id: The unique client identifier provided by Dhan.
     *   - Format: 10-digit numeric string (e.g., '1000000001')
     *
     * - access_token: The JWT access token for authentication.
     *   - Security: Must be kept secret. Rotate regularly via the Dhan portal.
     *   - Scope: Ensure the token has 'Orders' and 'MarketData' scopes enabled.
     *
     * - csv_url: URL to download the daily instrument master file.
     *   - Usage: Used by InstrumentLoader to sync the local instrument database.
     *   - Frequency: Updated daily by Dhan before market open (approx 08:30 IST).
     *
     * @example Environment Configuration (.env)
     * ```dotenv
     * DHAN_BASE_URI=https://api.dhan.co/v2/
     * DHAN_WS_URI=wss://api-feed.dhan.co
     * DHAN_CLIENT_ID=1100055555
     * DHAN_ACCESS_TOKEN=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
     * DHAN_CSV_URL=https://images.dhan.co/api-data/api-scrip-master.csv
     * ```
     */
    'dhan' => [
        'base_uri' => env('DHAN_BASE_URI', 'https://api.dhan.co/v2/'),
        'ws_uri' => env('DHAN_WS_URI', 'wss://api-feed.dhan.co'),
        'client_id' => env('DHAN_CLIENT_ID'),
        'access_token' => env('DHAN_ACCESS_TOKEN'),
        'csv_url' => env('DHAN_CSV_URL', 'https://images.dhan.co/api-data/api-scrip-master.csv'),
    ],

/**
 * Future Broker Integrations
 *
 * This section is reserved for adding support for additional brokers.
 * To add a new broker, follow the established pattern:
 *
 * 1. Define a new key for the broker (e.g., 'zerodha').
 * 2. Add configuration keys for base_uri, ws_uri, and credentials.
 * 3. Map these keys to corresponding environment variables.
 *
 * @example Zerodha Configuration Template
 * ```php
 * 'zerodha' => [
 *     'base_uri' => env('ZERODHA_BASE_URI', 'https://api.kite.trade'),
 *     'api_key' => env('ZERODHA_API_KEY'),
 *     'api_secret' => env('ZERODHA_API_SECRET'),
 *     'access_token' => env('ZERODHA_ACCESS_TOKEN'),
 * ],
 * ```
 */
    // 'zerodha' => [...],
];
