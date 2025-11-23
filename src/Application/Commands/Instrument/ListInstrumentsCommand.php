<?php

namespace TradingPlatform\Application\Commands\Instrument;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Domain\Instrument\Instrument;

/**
 * Command: List Instruments
 *
 * Displays a list of available trading instruments, optionally filtered by
 * type (equity/derivative) or tradable status. Supports JSON output for
 * programmatic consumption.
 */
class ListInstrumentsCommand extends Command
{
    protected static $defaultName = 'cli:instruments:list';

    /**
     * Configure the command.
     *
     * @return void
     */
    /**
     * Configure the command options and arguments.
     *
     * Defines filters for tradable status, instrument type, and broker.
     * Also allows specifying the output format (table or JSON).
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('List instruments')
            ->addOption('tradable', null, InputOption::VALUE_NONE, 'Filter tradable')
            ->addOption('derivatives', null, InputOption::VALUE_NONE, 'Filter derivatives')
            ->addOption('broker', null, InputOption::VALUE_REQUIRED, 'Broker name', 'dhan')
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Output format (table|json)', 'table')
            ->setHelp(
                "Usage:\n".
                "  php bin/console cli:instruments:list [--tradable] [--derivatives] [--broker=dhan] [--output=table|json]\n\n".
                "Options:\n".
                "  --tradable     Filter instruments that are currently tradable.\n".
                "  --derivatives  Show only derivative instruments (FUT/OPT).\n".
                "  --broker       Broker identifier. Default: dhan.\n".
                "  --output       Output format. 'table' or 'json'. Default: table.\n"
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
     * Execute the instrument listing.
     *
     * Queries the database for instruments matching the criteria and displays
     * them in the requested format.
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface  $output  Command output.
     * @return int Command exit code.
     *
     * @example List all derivatives in JSON format
     * ```bash
     * php bin/console cli:instruments:list --derivatives --output=json
     * ```
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $query = Instrument::query();

        if ($input->getOption('derivatives')) {
            $query->whereIn('instrument_type', ['FUTIDX', 'FUTSTK', 'OPTIDX', 'OPTSTK']);
        }

        $instruments = $query->limit(50)->get();

        if ($input->getOption('output') === 'json') {
            $output->writeln($instruments->toJson(JSON_PRETTY_PRINT));
        } else {
            $table = new Table($output);
            $table->setHeaders(['ID', 'Exchange', 'Symbol', 'Type', 'Lot Size', 'Expiry']);

            foreach ($instruments as $instrument) {
                $table->addRow([
                    $instrument->id,
                    $instrument->exchange,
                    $instrument->symbol,
                    $instrument->instrument_type,
                    $instrument->lot_size,
                    $instrument->expiry_date ? $instrument->expiry_date->format('Y-m-d') : '-',
                ]);
            }

            $table->render();
        }

        return Command::SUCCESS;
    }
}
