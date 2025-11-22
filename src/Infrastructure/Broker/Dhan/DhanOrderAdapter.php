<?php

namespace TradingPlatform\Infrastructure\Broker\Dhan;

use GuzzleHttp\Client;
use TradingPlatform\Domain\Order\Order;
use TradingPlatform\Infrastructure\Logger\LoggerService;

class DhanOrderAdapter
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

    public function placeOrder(Order $order): array
    {
        // Map internal order to Dhan payload
        $payload = [
            'dhanClientId' => env('DHAN_CLIENT_ID'),
            'correlationId' => $order->client_order_id,
            'transactionType' => $order->side,
            'exchangeSegment' => 'NSE_EQ', // Simplified for demo
            'productType' => 'INTRADAY', // Simplified
            'orderType' => $order->type,
            'validity' => $order->validity,
            'securityId' => (string)$order->instrument_id, // Needs mapping to Broker Security ID
            'quantity' => $order->qty,
            'price' => $order->price,
        ];

        $this->logger->info("Placing order on Dhan", $payload);

        try {
            // Mock response for demo if no real creds
            // Mock response for demo if no real creds
            // In a real app, we might want a dedicated MockAdapter or use Guzzle MockHandler
            // For now, we rely on the API call or expect the user to provide valid creds.

            $response = $this->client->post('orders', ['json' => $payload]);
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $this->logger->error("Order Placement Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function cancelOrder(string $orderId): array
    {
        $this->logger->info("Cancelling order: $orderId");

        try {
             // Mock response
             // Mock response

            $response = $this->client->delete("orders/{$orderId}");
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $this->logger->error("Order Cancellation Error: " . $e->getMessage());
            throw $e;
        }
    }
}
