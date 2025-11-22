<?php

namespace TradingPlatform\Infrastructure\WebSocket;

use Psr\Log\LoggerInterface;

class ConnectionManager
{
    private array $clients = [];
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function addConnection(string $name, string $url, callable $onMessage): void
    {
        $client = new WebSocketClient($url, $this->logger, $onMessage);
        $this->clients[$name] = $client;
    }

    public function startAll(): void
    {
        foreach ($this->clients as $name => $client) {
            $this->logger->info("Starting connection: {$name}");
            $client->connect();
        }
        
        // Assuming all share the same event loop singleton
        if (!empty($this->clients)) {
            reset($this->clients)->run();
        }
    }

    public function stopAll(): void
    {
        foreach ($this->clients as $client) {
            $client->disconnect();
        }
    }

    /**
     * Active-Active setup: If one connection fails, the other is already running.
     * This manager ensures both are configured and started.
     */
    public function setupRedundantConnections(string $primaryUrl, string $secondaryUrl, callable $handler): void
    {
        $this->addConnection('primary', $primaryUrl, $handler);
        $this->addConnection('secondary', $secondaryUrl, $handler);
    }
}
