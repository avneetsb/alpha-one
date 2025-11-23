<?php

namespace TradingPlatform\Application\Commands\Portfolio;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Domain\Portfolio\Position;

/**
 * Command: List Positions
 *
 * Displays a table of all currently open positions in the portfolio.
 * Shows net quantity, average prices, and aggregated P&L (Realized + Unrealized).
 */
class ListPositionsCommand extends Command
{
    protected static $defaultName = 'cli:portfolio:positions';

    /**
     * Configure the command.
     *
     * @return void
     */
    /**
     * Configure the command.
     *
     * Sets up the command signature and help text.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('List open positions')
            ->setHelp(
                "Usage:\n".
                "  php bin/console cli:portfolio:positions\n\n".
                "Behavior:\n".
                "  Lists positions from local DB with basic P&L aggregation.\n"
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
     * Execute the position listing.
     *
     * Fetches all positions from the database and renders them in a CLI table.
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface  $output  Command output.
     * @return int Command exit code.
     *
     * @example List positions
     * ```bash
     * php bin/console cli:portfolio:positions
     * ```
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // In a real app, we would fetch from Broker Adapter and sync to DB first
        // For demo, we list from DB

        $positions = Position::all();

        $table = new Table($output);
        $table->setHeaders(['ID', 'Instrument', 'Net Qty', 'Buy Avg', 'Sell Avg', 'P&L']);

        foreach ($positions as $pos) {
            $table->addRow([
                $pos->id,
                $pos->instrument_id,
                $pos->net_qty,
                $pos->buy_avg,
                $pos->sell_avg,
                $pos->realized_pnl + $pos->unrealized_pnl,
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}
