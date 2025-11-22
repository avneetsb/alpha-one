<?php

/**
 * Broker API configuration for the trading platform.
 * 
 * This configuration file defines all broker integrations supported by the
 * trading platform. Currently includes Dhan broker configuration with
 * placeholders for future broker integrations. Each broker configuration
 * includes API endpoints, authentication credentials, and service URLs.
 * 
 * @package Config
 * @author  Trading Platform Team
 * @version 1.0.0
 * 
 * @structure
 * - dhan: Dhan broker configuration
 *   - base_uri: REST API base URL
 *   - ws_uri: WebSocket connection URL for real-time data
 *   - client_id: Broker client ID for authentication
 *   - access_token: Access token for API authentication
 *   - csv_url: URL for instrument master data CSV file
 * 
 * @environment_variables
 * - DHAN_BASE_URI: Dhan REST API base URL
 * - DHAN_WS_URI: Dhan WebSocket URL for real-time feeds
 * - DHAN_CLIENT_ID: Your Dhan client ID
 * - DHAN_ACCESS_TOKEN: Your Dhan API access token
 * - DHAN_CSV_URL: URL for Dhan's instrument master CSV
 *
 * @accepted_values
 * - base_uri protocol: 'https://'
 * - ws_uri protocol: 'wss://'
 * 
 * @example
 * // Environment configuration for Dhan broker:
 * DHAN_BASE_URI=https://api.dhan.co/v2/
 * DHAN_WS_URI=wss://api-feed.dhan.co
 * DHAN_CLIENT_ID=12345678
 * DHAN_ACCESS_TOKEN=your_secure_access_token_here
 * DHAN_CSV_URL=https://images.dhan.co/api-data/api-scrip-master.csv
 * 
 * // Usage in application:
 * $brokerConfig = config('broker.dhan');
 * $baseUri = $brokerConfig['base_uri'];
 * $clientId = $brokerConfig['client_id'];
 * 
 * @note Future broker integrations should follow the same structure:
 *       'broker_name' => [
 *           'base_uri' => 'https://api.broker.com/',
 *           'ws_uri' => 'wss://ws.broker.com',
 *           'client_id' => env('BROKER_CLIENT_ID'),
 *           'access_token' => env('BROKER_ACCESS_TOKEN'),
 *           'csv_url' => 'https://broker.com/instruments.csv',
 *       ]
 * 
 * @important Keep access tokens secure and never commit them to version control.
 *           Always use environment variables for sensitive credentials.
 * 
 * @security Ensure all broker API communications use HTTPS/WSS protocols
 *          to protect sensitive trading data and credentials.
 */

return [
    /**
     * Dhan broker API configuration.
     * 
     * Complete configuration for integrating with Dhan's trading API.
     * Includes REST API endpoints, WebSocket connections for real-time data,
     * authentication credentials, and data feed URLs.
     * 
     * @structure
     * - base_uri: REST API base URL for all HTTP requests
     * - ws_uri: WebSocket URL for real-time market data feeds
     * - client_id: Your unique Dhan client identifier
     * - access_token: Secure access token for API authentication
     * - csv_url: URL for downloading instrument master data
     * 
     * @environment_variables
     * - DHAN_BASE_URI: Override default REST API base URL
     * - DHAN_WS_URI: Override default WebSocket URL
     * - DHAN_CLIENT_ID: Your Dhan client ID (required)
     * - DHAN_ACCESS_TOKEN: Your secure access token (required)
     * - DHAN_CSV_URL: Override instrument CSV download URL
     * 
     * @example Production configuration:
     * // .env file for production trading:
     * DHAN_CLIENT_ID=87654321
     * DHAN_ACCESS_TOKEN=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
     * 
     * // Usage in code:
     * $dhanConfig = config('broker.dhan');
     * $client = new \GuzzleHttp\Client([
     *     'base_uri' => $dhanConfig['base_uri'],
     *     'headers' => [
     *         'Access-Token' => $dhanConfig['access_token'],
     *         'Client-ID' => $dhanConfig['client_id']
     *     ]
     * ]);
     * 
     * @important Security considerations:
     * - Never commit access tokens to version control
     * - Use environment variables for all sensitive data
     * - Rotate access tokens regularly
     * - Monitor API usage for unauthorized access
     * 
     * @note The CSV URL provides instrument master data including:
     *       - Trading symbols and codes
     *       - Instrument types (EQ, FUT, OPT)
     *       - Exchange information
     *       - Lot sizes and tick sizes
     *       - Trading hours and holidays
     * 
     * @rate_limiting Be aware of Dhan's API rate limits:
     *                - Market data: 100 requests per second
     *                - Order placement: 10 requests per second
     *                - Historical data: 50 requests per minute
     * 
     * @see https://dhan.co/developer/api/ for complete API documentation
     */
    'dhan' => [
        'base_uri' => env('DHAN_BASE_URI', 'https://api.dhan.co/v2/'),
        'ws_uri' => env('DHAN_WS_URI', 'wss://api-feed.dhan.co'),
        'client_id' => env('DHAN_CLIENT_ID'),
        'access_token' => env('DHAN_ACCESS_TOKEN'),
        'csv_url' => env('DHAN_CSV_URL', 'https://images.dhan.co/api-data/api-scrip-master.csv'),
    ],
    
    /**
     * Placeholder for future broker integrations.
     * 
     * This section is reserved for additional broker configurations.
     * When adding new brokers, follow the same structure as the Dhan configuration
     * to maintain consistency across the platform.
     * 
     * @todo Implement support for additional brokers:
     *       - Zerodha (Kite Connect API)
     *       - Upstox (Upstox API)
     *       - Angel One (Smart API)
     *       - ICICI Direct (Breeze API)
     *       - 5Paisa (5Paisa API)
     * 
     * @example Future broker configuration:
     * 'zerodha' => [
     *     'base_uri' => env('ZERODHA_BASE_URI', 'https://api.kite.trade/'),
     *     'ws_uri' => env('ZERODHA_WS_URI', 'wss://ws.kite.trade'),
     *     'api_key' => env('ZERODHA_API_KEY'),
     *     'api_secret' => env('ZERODHA_API_SECRET'),
     *     'access_token' => env('ZERODHA_ACCESS_TOKEN'),
     *     'csv_url' => env('ZERODHA_CSV_URL', 'https://api.kite.trade/instruments'),
     * ],
     * 
     * @note Each broker may have different authentication mechanisms:
     *       - API Key + Secret (Zerodha, Upstox)
     *       - Client ID + Access Token (Dhan)
     *       - OAuth 2.0 (Some international brokers)
     *       - Certificate-based (Institutional brokers)
     * 
     * @important When adding new brokers:
     * - Research their specific API requirements
     * - Implement proper error handling for their response formats
     * - Consider rate limiting and throttling requirements
     * - Implement proper authentication token management
     * - Add comprehensive logging for debugging
     * - Test thoroughly with paper trading before live deployment
     */
    // Future brokers...
];
