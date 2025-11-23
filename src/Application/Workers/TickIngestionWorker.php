<?php

namespace TradingPlatform\Application\Workers;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Infrastructure\Broker\Dhan\DhanWebSocketClient;
use TradingPlatform\Infrastructure\Cache\RedisAdapter;
use TradingPlatform\Infrastructure\Logger\LoggerService;

class TickIngestionWorker extends Command
{
    protected static $defaultName = 'cli:workers:tick-ingestion';

    protected function configure()
    {
        $this->setDescription('Start tick ingestion worker')
            ->addOption('broker', null, InputOption::VALUE_REQUIRED, 'Broker name', 'dhan')
            ->setHelp(
                "Usage:\n".
                "  php bin/console cli:workers:tick-ingestion [--broker=dhan]\n\n".
                "Options:\n".
                "  --broker   Broker identifier. Default: dhan.\n\n".
                "Behavior:\n".
                "  Connects to broker WS, subscribes to Redis-managed instrument set, and ingests ticks.\n"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $broker = $input->getOption('broker');
        $logger = LoggerService::getLogger();
        $redis = RedisAdapter::getInstance();

        $logger->info("Starting Tick Ingestion Worker for $broker");

        // In a real app, we would get credentials from config/env
        $clientId = env('DHAN_CLIENT_ID');
        $accessToken = env('DHAN_ACCESS_TOKEN');

        $client = new DhanWebSocketClient($clientId, $accessToken);
        $client->connect();

        // Initial subscription
        $key = "subscriptions:{$broker}";
        $instruments = $redis->smembers($key);
        if (! empty($instruments)) {
            $client->subscribe($instruments);
        }

        $output->writeln('Worker started. Press Ctrl+C to stop.');

        // Main loop
        while (true) {
            // Check for new subscriptions periodically or via a control channel
            // For now, just read
            $data = $client->read();

            if ($data) {
                // Parse binary data (Mocking parsing here)
                // $tick = $parser->parse($data);

                // Log that we received something
                $logger->debug('Received data length: '.strlen($data));

                // In real implementation:
                // 1. Parse tick
                // 2. Push to Redis Queue per instrument
                // 3. Update LTP cache
            }

            // Heartbeat / Sleep to avoid tight loop if read is non-blocking (textalk is blocking by default)
            // If blocking, we wait for data.
        }

        return Command::SUCCESS;
    }
}
