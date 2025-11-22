<?php

namespace TradingPlatform\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use TradingPlatform\Application\Services\DashboardService;

class DashboardCommand extends Command
{
    protected static $defaultName = 'dashboard:start';
    private DashboardService $service;

    public function __construct(DashboardService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    protected function configure(): void
    {
        $this->setDescription('Start real-time CLI dashboard');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Clear screen
        $output->write("\033[2J\033[;H");

        while (true) {
            $metrics = $this->service->getMetrics();
            
            // Move cursor to top
            $output->write("\033[;H");
            
            $this->renderHeader($output);
            $this->renderTradingPanel($output, $metrics['trading']);
            $this->renderSystemPanel($output, $metrics['system'], $metrics['workers'], $metrics['queues']);
            
            $output->writeln("\nPress Ctrl+C to exit...");
            
            sleep(1);
        }

        return Command::SUCCESS;
    }

    private function renderHeader(OutputInterface $output): void
    {
        $output->writeln("<bg=blue;fg=white;options=bold> TRADING PLATFORM DASHBOARD </>" . date(' Y-m-d H:i:s'));
        $output->writeln(str_repeat('-', 80));
    }

    private function renderTradingPanel(OutputInterface $output, array $metrics): void
    {
        $output->writeln("<info>TRADING METRICS</info>");
        
        $pnlColor = $metrics['total_pnl'] >= 0 ? 'green' : 'red';
        $netPnlColor = $metrics['net_pnl'] >= 0 ? 'green' : 'red';

        $table = new Table($output);
        $table->setHeaders(['Metric', 'Value']);
        $table->addRow(['Open Positions', $metrics['open_positions']]);
        $table->addRow(['Unrealized P&L', "<fg=$pnlColor>" . number_format($metrics['unrealized_pnl'], 2) . "</>"]);
        $table->addRow(['Realized P&L', number_format($metrics['realized_pnl'], 2)]);
        $table->addRow(['Total Fees', number_format($metrics['total_fees'], 2)]);
        $table->addRow(['Net P&L', "<fg=$netPnlColor>" . number_format($metrics['net_pnl'], 2) . "</>"]);
        $table->render();
        
        $output->writeln("");
    }

    private function renderSystemPanel(OutputInterface $output, array $system, array $workers, array $queues): void
    {
        $output->writeln("<info>SYSTEM STATUS</info>");
        
        $table = new Table($output);
        $table->setHeaders(['Component', 'Status', 'Metric']);
        $table->addRow(['CPU Load', 'OK', $system['cpu_load']]);
        $table->addRow(['Memory', 'OK', round($system['memory_usage'] / 1024 / 1024, 2) . ' MB']);
        $table->addRow(['Workers', 'Active', $workers['active_workers'] . ' active']);
        $table->addRow(['Queues', 'Processing', $queues['pending_jobs'] . ' pending']);
        $table->render();
    }
}
