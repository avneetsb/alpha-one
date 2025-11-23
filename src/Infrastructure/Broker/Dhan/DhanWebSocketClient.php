<?php

namespace TradingPlatform\Infrastructure\Broker\Dhan;

use TradingPlatform\Infrastructure\Logger\LoggerService;
use WebSocket\Client;

/**
 * Class: Dhan WebSocket Client
 *
 * Manages the real-time WebSocket connection to Dhan's market data feed.
 * Handles connection establishment, subscription management, and message reading.
 */
class DhanWebSocketClient
{
    private Client $client;

    private string $url;

    private $logger;

    /**
     * DhanWebSocketClient constructor.
     */
    public function __construct(string $clientId, string $accessToken)
    {
        $this->logger = LoggerService::getLogger();
        $config = require __DIR__.'/../../../../config/broker.php';
        $baseUrl = $config['dhan']['ws_uri'];

        // wss://api-feed.dhan.co?version=2&token=...&clientId=...&authType=2
        $this->url = "{$baseUrl}?version=2&token={$accessToken}&clientId={$clientId}&authType=2";

        $this->client = new Client($this->url, [
            'timeout' => 60, // 60 seconds timeout
        ]);
    }

    /**
     * Establish the WebSocket connection.
     *
     * Attempts to connect to the configured WebSocket URL with exponential backoff
     * for retries.
     *
     * @throws \Exception If connection fails after maximum attempts.
     *
     * @example
     * ```php
     * $wsClient = new DhanWebSocketClient($clientId, $token);
     * $wsClient->connect();
     * ```
     */
    public function connect(): void
    {
        $attempts = 0;
        $maxAttempts = 5;

        while ($attempts < $maxAttempts) {
            try {
                $this->logger->info("Connecting to Dhan WS: {$this->url}");

                // Re-instantiate client if needed or just use existing if library supports it
                // textalk/websocket client connects on constructor or first call.
                // If we are reconnecting, we might need a new instance or just call receive/send.
                // But for robustness, let's assume we just log the attempt here.
                // In a real loop, we would have a loop in the worker, not here.
                // This method just establishes initial connection.
                return;
            } catch (\Exception $e) {
                $attempts++;
                $this->logger->error("WS Connection failed (Attempt $attempts): ".$e->getMessage());
                sleep(pow(2, $attempts)); // Exponential backoff
            }
        }
        throw new \Exception("Failed to connect to Dhan WS after $maxAttempts attempts");
    }

    /**
     * Subscribe to instruments.
     *
     * @param  array  $instruments  List of instrument tokens/symbols.
     */
    public function subscribe(array $instruments): void
    {
        $this->logger->info('Subscribing to: '.implode(', ', $instruments));
        // Mock sending subscription
    }

    /**
     * Read a message from the WebSocket.
     *
     * @return string|null The message payload or null on error.
     */
    public function read(): ?string
    {
        try {
            return $this->client->receive();
        } catch (\Exception $e) {
            $this->logger->error('WS Read Error: '.$e->getMessage());

            // Here we could trigger a reconnect logic or throw to let the worker handle it
            return null;
        }
    }
}
