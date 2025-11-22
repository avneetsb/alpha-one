<?php

namespace TradingPlatform\Application\Commands\Instrument;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Infrastructure\Broker\Dhan\DhanInstrumentLoader;

class RefreshInstrumentsCommand extends Command
{
    protected static $defaultName = 'cli:instruments:refresh';

    protected function configure()
    {
        $this->setDescription('Fetch and persist latest instruments')
            ->addOption('broker', null, InputOption::VALUE_REQUIRED, 'Broker name', 'dhan')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force refresh')
            ->setHelp(
                "Usage:\n" .
                "  php bin/console cli:instruments:refresh --broker=dhan [--force]\n\n" .
                "Options:\n" .
                "  --broker   Broker identifier (e.g. dhan). Default: dhan.\n" .
                "  --force    If set, forces full refresh even if cache is present.\n"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $broker = $input->getOption('broker');
        $output->writeln("Refreshing instruments for broker: $broker");

        if ($broker === 'dhan') {
            $loader = new DhanInstrumentLoader();
            $result = $loader->load($input->getOption('force'));
            $output->writeln("Updated {$result['updated']} instruments.");
        } else {
            $output->writeln("<error>Broker not supported: $broker</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
