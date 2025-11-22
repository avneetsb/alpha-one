<?php

namespace TradingPlatform\Application\Commands\Portfolio;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use TradingPlatform\Domain\Portfolio\Position;

class ListPositionsCommand extends Command
{
    protected static $defaultName = 'cli:portfolio:positions';

    protected function configure()
    {
        $this->setDescription('List open positions')
            ->setHelp(
                "Usage:\n" .
                "  php bin/console cli:portfolio:positions\n\n" .
                "Behavior:\n" .
                "  Lists positions from local DB with basic P&L aggregation.\n"
            );
    }

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
