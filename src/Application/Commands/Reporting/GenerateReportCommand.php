<?php

namespace TradingPlatform\Application\Commands\Reporting;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Domain\Order\Order;
use TradingPlatform\Domain\Portfolio\Position;

class GenerateReportCommand extends Command
{
    protected static $defaultName = 'cli:report:generate';

    protected function configure()
    {
        $this->setDescription('Generate trading report')
            ->setHelp(
                "Usage:\n" .
                "  php bin/console cli:report:generate\n\n" .
                "Behavior:\n" .
                "  Summarizes orders and positions to produce a simple report (mocked CSV).\n"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("Generating Trading Report...");
        
        // 1. Fetch all orders
        $orders = Order::all();
        $output->writeln("Total Orders: " . $orders->count());
        
        // 2. Fetch all positions
        $positions = Position::all();
        $output->writeln("Open Positions: " . $positions->count());
        
        // 3. Calculate P&L (Mock)
        $pnl = 0;
        foreach ($positions as $position) {
            $pnl += ($position->current_price - $position->buy_price) * $position->quantity;
        }
        
        $output->writeln("Unrealized P&L: " . number_format($pnl, 2));
        
        // 4. Export to CSV (Mock)
        $output->writeln("Report exported to report.csv");
        
        return Command::SUCCESS;
    }
}
