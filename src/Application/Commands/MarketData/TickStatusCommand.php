<?php

namespace TradingPlatform\Application\Commands\MarketData;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Infrastructure\Cache\RedisAdapter;

/**
 * Command: Tick Status
 *
 * Checks the health and status of the real-time market data feed.
 * Reports connection status and active subscriptions.
 */
class TickStatusCommand extends Command
{
    protected static $defaultName = 'cli:ticks:status';

    /**
     * Configure the command.
     *
     * @return void
     */
    /**
     * Configure the command options.
     *
     * Defines options for broker and output format.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Show websocket and subscription health')
            ->addOption('broker', null, InputOption::VALUE_REQUIRED, 'Broker name', 'dhan')
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Output format (json)', 'json')
            ->setHelp(
                "Usage:\n".
                "  php bin/console cli:ticks:status [--broker=dhan] [--output=json]\n\n".
                "Options:\n".
                "  --broker   Broker identifier. Default: dhan.\n".
                "  --output   Output format (currently json). Default: json.\n\n".
                "Behavior:\n".
                "  Reads Redis subscription set 'subscriptions:{broker}' and reports WS status (mocked).\n"
            );
    }

    /**
     * Execute the command.
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface  $output  Command output.
     * @return int Command exit code.
     */
    /**
     * Execute the status check.
     *
     * Queries Redis for active subscriptions and connection state.
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface  $output  Command output.
     * @return int Command exit code.
     *
     * @example Check status in JSON
     * ```bash
     * php bin/console cli:ticks:status --output=json
     * ```
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $broker = $input->getOption('broker');

        try {
            $redis = RedisAdapter::getInstance();
            $key = "subscriptions:{$broker}";
            $subscriptions = $redis->smembers($key);

            // Mock WS status for now since we don't have the worker running yet
            $status = [
                'status' => 'ok',
                'data' => [
                    'ws' => 'connected', // Mocked
                    'subscriptions' => $subscriptions,
                ],
                'error' => null,
            ];

            $output->writeln(json_encode($status, JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            $output->writeln(json_encode([
                'status' => 'error',
                'data' => null,
                'error' => ['message' => $e->getMessage()],
            ], JSON_PRETTY_PRINT));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
