<?php

namespace TradingPlatform\Application\Commands\MarketData;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Infrastructure\Cache\RedisAdapter;

/**
 * Command: Subscribe Ticks
 *
 * Manages real-time market data subscriptions. Registers instruments for
 * tick updates via the broker's WebSocket connection.
 */
class SubscribeTicksCommand extends Command
{
    protected static $defaultName = 'cli:ticks:subscribe';

    /**
     * Configure the command.
     *
     * @return void
     */
    /**
     * Configure the command options.
     *
     * Accepts one or more instrument symbols to subscribe to.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Subscribe to ticks for instruments')
            ->addOption('instrument', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Instrument symbol')
            ->addOption('broker', null, InputOption::VALUE_REQUIRED, 'Broker name', 'dhan')
            ->setHelp(
                "Usage:\n".
                "  php bin/console cli:ticks:subscribe --instrument=RELIANCE --instrument=TCS [--broker=dhan]\n\n".
                "Options:\n".
                "  --instrument   Required, repeatable. Instrument symbol(s) to subscribe.\n".
                "  --broker       Broker identifier. Default: dhan.\n\n".
                "Behavior:\n".
                "  Stores subscriptions in Redis set 'subscriptions:{broker}' for WS worker consumption.\n"
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
     * Execute the subscription request.
     *
     * Pushes the requested instruments to a Redis set monitored by the WebSocket worker.
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface  $output  Command output.
     * @return int Command exit code.
     *
     * @example Subscribe to multiple symbols
     * ```bash
     * php bin/console cli:ticks:subscribe --instrument=INFY --instrument=WIPRO
     * ```
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $instruments = $input->getOption('instrument');
        $broker = $input->getOption('broker');

        if (empty($instruments)) {
            $output->writeln('<error>No instruments specified.</error>');

            return Command::FAILURE;
        }

        // In a real implementation, we would push these to a Redis set that the WS worker monitors
        // or send a command to the running worker.
        // For now, let's store them in a Redis set `subscriptions:{broker}`

        try {
            $redis = RedisAdapter::getInstance();
            $key = "subscriptions:{$broker}";
            $redis->sadd($key, $instruments);

            $output->writeln('Subscribed to: '.implode(', ', $instruments));
        } catch (\Exception $e) {
            $output->writeln('<error>Redis error: '.$e->getMessage().'</error>');

            // If Redis fails, we might want to fail, but for now let's just log
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
