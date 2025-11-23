<?php

namespace TradingPlatform\Application\Commands\Instrument;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Infrastructure\Broker\Dhan\DhanInstrumentLoader;

/**
 * Command: Refresh Instruments
 *
 * Synchronizes the local instrument database with the broker's master list.
 * This is typically run daily before market open to ensure all symbols,
 * lot sizes, and expiry dates are up-to-date.
 */
class RefreshInstrumentsCommand extends Command
{
    protected static $defaultName = 'cli:instruments:refresh';

    /**
     * Configure the command.
     *
     * @return void
     */
    /**
     * Configure the command options.
     *
     * Defines the broker option and a force flag to bypass cache/checks.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Fetch and persist latest instruments')
            ->addOption('broker', null, InputOption::VALUE_REQUIRED, 'Broker name', 'dhan')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force refresh')
            ->setHelp(
                "Usage:\n".
                "  php bin/console cli:instruments:refresh --broker=dhan [--force]\n\n".
                "Options:\n".
                "  --broker   Broker identifier (e.g. dhan). Default: dhan.\n".
                "  --force    If set, forces full refresh even if cache is present.\n"
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
     * Execute the instrument refresh process.
     *
     * Instantiates the appropriate broker loader and triggers the synchronization.
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface  $output  Command output.
     * @return int Command exit code.
     *
     * @example Force refresh for Dhan
     * ```bash
     * php bin/console cli:instruments:refresh --broker=dhan --force
     * ```
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $broker = $input->getOption('broker');
        $output->writeln("Refreshing instruments for broker: $broker");

        if ($broker === 'dhan') {
            $loader = new DhanInstrumentLoader;
            $result = $loader->load($input->getOption('force'));
            $output->writeln("Updated {$result['updated']} instruments.");
        } else {
            $output->writeln("<error>Broker not supported: $broker</error>");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
