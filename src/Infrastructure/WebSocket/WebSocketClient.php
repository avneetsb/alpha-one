<?php

namespace TradingPlatform\Infrastructure\WebSocket;

use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Socket\Connector;
use Ratchet\Client\Connector as RatchetConnector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Psr\Log\LoggerInterface;

class WebSocketClient
{
    private string $url;
    private LoggerInterface $logger;
    private ?WebSocket $connection = null;
    private LoopInterface $loop;
    private bool $shouldReconnect = true;
    private int $reconnectAttempts = 0;
    private $onMessageCallback;

    public function __construct(string $url, LoggerInterface $logger, callable $onMessageCallback)
    {
        $this->url = $url;
        $this->logger = $logger;
        $this->onMessageCallback = $onMessageCallback;
        $this->loop = Loop::get();
    }

    public function connect(): void
    {
        $connector = new RatchetConnector($this->loop, new Connector($this->loop));

        $connector($this->url)->then(
            function (WebSocket $conn) {
                $this->connection = $conn;
                $this->reconnectAttempts = 0;
                $this->logger->info("WebSocket connected to {$this->url}");

                $conn->on('message', function (MessageInterface $msg) {
                    call_user_func($this->onMessageCallback, $msg->getPayload());
                });

                $conn->on('close', function ($code = null, $reason = null) {
                    $this->logger->warning("WebSocket closed: {$code} - {$reason}");
                    $this->connection = null;
                    if ($this->shouldReconnect) {
                        $this->scheduleReconnect();
                    }
                });
            },
            function (\Exception $e) {
                $this->logger->error("Could not connect: {$e->getMessage()}");
                if ($this->shouldReconnect) {
                    $this->scheduleReconnect();
                }
            }
        );
    }

    public function disconnect(): void
    {
        $this->shouldReconnect = false;
        if ($this->connection) {
            $this->connection->close();
        }
    }

    public function send(string $data): void
    {
        if ($this->connection) {
            $this->connection->send($data);
        } else {
            $this->logger->error("Cannot send message, WebSocket not connected");
        }
    }

    private function scheduleReconnect(): void
    {
        $delay = min(30, pow(2, $this->reconnectAttempts)); // Exponential backoff max 30s
        $this->reconnectAttempts++;
        
        $this->logger->info("Reconnecting in {$delay} seconds...");
        
        $this->loop->addTimer($delay, function () {
            $this->connect();
        });
    }

    public function run(): void
    {
        $this->loop->run();
    }
}
