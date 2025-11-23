<?php

namespace TradingPlatform\Infrastructure\WebSocket;

use Psr\Log\LoggerInterface;
use Ratchet\Client\Connector as RatchetConnector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Socket\Connector;

/**
 * Class WebSocketClient
 *
 * Async WebSocket client using ReactPHP and Ratchet.
 * Handles connection, reconnection logic, and message dispatching.
 */
class WebSocketClient
{
    private string $url;

    private LoggerInterface $logger;

    private ?WebSocket $connection = null;

    private LoopInterface $loop;

    private bool $shouldReconnect = true;

    private int $reconnectAttempts = 0;

    private $onMessageCallback;

    /**
     * WebSocketClient constructor.
     *
     * @param  string  $url  WebSocket URL.
     * @param  LoggerInterface  $logger  Logger instance.
     * @param  callable  $onMessageCallback  Callback for incoming messages.
     */
    public function __construct(string $url, LoggerInterface $logger, callable $onMessageCallback)
    {
        $this->url = $url;
        $this->logger = $logger;
        $this->onMessageCallback = $onMessageCallback;
        $this->loop = Loop::get();
    }

    /**
     * Initiate connection to the WebSocket server.
     */
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

    /**
     * Disconnect and stop reconnection attempts.
     */
    public function disconnect(): void
    {
        $this->shouldReconnect = false;
        if ($this->connection) {
            $this->connection->close();
        }
    }

    /**
     * Send data over the WebSocket connection.
     */
    public function send(string $data): void
    {
        if ($this->connection) {
            $this->connection->send($data);
        } else {
            $this->logger->error('Cannot send message, WebSocket not connected');
        }
    }

    /**
     * Schedule a reconnection attempt with exponential backoff.
     */
    private function scheduleReconnect(): void
    {
        $delay = min(30, pow(2, $this->reconnectAttempts)); // Exponential backoff max 30s
        $this->reconnectAttempts++;

        $this->logger->info("Reconnecting in {$delay} seconds...");

        $this->loop->addTimer($delay, function () {
            $this->connect();
        });
    }

    /**
     * Run the event loop.
     */
    public function run(): void
    {
        $this->loop->run();
    }
}
