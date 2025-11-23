<?php

namespace TradingPlatform\Infrastructure\WebSocket;

use Psr\Log\LoggerInterface;

/**
 * Class ConnectionManager
 *
 * Manages multiple WebSocket connections, providing redundancy and failover capabilities.
 */
class ConnectionManager
{
    /**
     * @var array<string, WebSocketClient>
     */
    private array $clients = [];

    private LoggerInterface $logger;

    /**
     * ConnectionManager constructor.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Add a new WebSocket connection configuration.
     *
     * @param  string  $name  Unique identifier for the connection.
     * @param  string  $url  WebSocket URL.
     * @param  callable  $onMessage  Callback for incoming messages.
     */
    public function addConnection(string $name, string $url, callable $onMessage): void
    {
        $client = new WebSocketClient($url, $this->logger, $onMessage);
        $this->clients[$name] = $client;
    }

    /**
     * Start all registered connections.
     */
    public function startAll(): void
    {
        foreach ($this->clients as $name => $client) {
            $this->logger->info("Starting connection: {$name}");
            $client->connect();
        }

        // Assuming all share the same event loop singleton
        if (! empty($this->clients)) {
            reset($this->clients)->run();
        }
    }

    /**
     * Stop all active connections.
     */
    public function stopAll(): void
    {
        foreach ($this->clients as $client) {
            $client->disconnect();
        }
    }

    /**
     * Setup redundant connections (Active-Active).
     * If one connection fails, the other is already running.
     */
    public function setupRedundantConnections(string $primaryUrl, string $secondaryUrl, callable $handler): void
    {
        $this->addConnection('primary', $primaryUrl, $handler);
        $this->addConnection('secondary', $secondaryUrl, $handler);
    }
}
