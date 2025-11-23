<?php

namespace TradingPlatform\Infrastructure\Broker\Dhan;

use GuzzleHttp\Client;
use TradingPlatform\Infrastructure\Logger\LoggerService;

/**
 * Class: Dhan API Adapter
 *
 * Primary adapter for interacting with the Dhan REST API.
 * Handles general data fetching operations such as retrieving orders,
 * positions, and holdings.
 */
class DhanAdapter
{
    /**
     * @var Client HTTP Client.
     */
    private Client $client;

    /**
     * @var mixed Logger instance.
     */
    private $logger;

    /**
     * Create a new Dhan adapter instance.
     *
     * @param  string  $accessToken  The API access token.
     * @param  Client|null  $client  Optional Guzzle client for testing.
     * @param  \Psr\Log\LoggerInterface|null  $logger  Optional logger.
     */
    public function __construct(string $accessToken, ?Client $client = null, ?\Psr\Log\LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? LoggerService::getLogger();
        $config = require __DIR__.'/../../../../config/broker.php';

        $this->client = $client ?? new Client([
            'base_uri' => $config['dhan']['base_uri'],
            'headers' => [
                'access-token' => $accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 10,
        ]);
    }

    /**
     * Fetch all orders from the broker.
     *
     * Retrieves the list of orders placed today.
     *
     * @return array List of order objects from the API.
     *
     * @throws \Exception If the API request fails.
     *
     * @example
     * ```php
     * $orders = $dhanAdapter->getOrders();
     * foreach ($orders as $order) {
     *     echo $order['orderId'] . ': ' . $order['orderStatus'];
     * }
     * ```
     */
    public function getOrders(): array
    {
        try {
            // GET /orders
            $response = $this->client->get('orders');

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch orders from Dhan', [
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Dhan API Error: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Fetch positions from Dhan.
     *
     * @return array List of positions.
     *
     * @throws \Exception If API call fails.
     */
    public function getPositions(): array
    {
        try {
            // GET /positions
            $response = $this->client->get('positions');

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch positions from Dhan', ['error' => $e->getMessage()]);
            throw new \Exception('Dhan API Error: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Fetch holdings from Dhan.
     *
     * @return array List of holdings.
     *
     * @throws \Exception If API call fails.
     */
    public function getHoldings(): array
    {
        try {
            // GET /holdings
            $response = $this->client->get('holdings');

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch holdings from Dhan', ['error' => $e->getMessage()]);
            throw new \Exception('Dhan API Error: '.$e->getMessage(), $e->getCode(), $e);
        }
    }
}
