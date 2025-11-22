<?php

namespace TradingPlatform\Infrastructure\Broker\Dhan;

use GuzzleHttp\Client;
use TradingPlatform\Infrastructure\Logger\LoggerService;

class DhanAdapter
{
    private Client $client;
    private $logger;

    public function __construct(string $accessToken)
    {
        $this->logger = LoggerService::getLogger();
        $config = require __DIR__ . '/../../../../config/broker.php';
        
        $this->client = new Client([
            'base_uri' => $config['dhan']['base_uri'],
            'headers' => [
                'access-token' => $accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 10,
        ]);
    }

    public function getOrders(): array
    {
        try {
            // GET /orders
            $response = $this->client->get('orders');
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch orders from Dhan", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Dhan API Error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getPositions(): array
    {
        try {
            // GET /positions
            $response = $this->client->get('positions');
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch positions from Dhan", ['error' => $e->getMessage()]);
            throw new \Exception("Dhan API Error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getHoldings(): array
    {
        try {
            // GET /holdings
            $response = $this->client->get('holdings');
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch holdings from Dhan", ['error' => $e->getMessage()]);
            throw new \Exception("Dhan API Error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
