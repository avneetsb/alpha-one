<?php

namespace TradingPlatform\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Application\Services\DashboardService;

/**
 * Class DashboardCommand
 *
 * Renders a real-time CLI dashboard displaying trading metrics, system status,
 * worker activity, and queue depths.
 */
class DashboardCommand extends Command
{
    /**
     * @var string The default name of the command.
     */
    protected static $defaultName = 'dashboard:start';

    /**
     * @var DashboardService Service for retrieving dashboard metrics.
     */
    private DashboardService $service;

    /**
     * DashboardCommand constructor.
     *
     * @param  DashboardService  $service  The dashboard service instance.
     */
    public function __construct(DashboardService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Configure the command options and description.
     */
    protected function configure(): void
    {
        $this->setDescription('Start real-time CLI dashboard');
    }

    /**
     * Execute the console command.
     *
     * Runs an infinite loop that clears the screen and renders the dashboard
     * every second until interrupted.
     *
     * @param  InputInterface  $input  The input interface.
     * @param  OutputInterface  $output  The output interface.
     * @return int Command exit code.
     */
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

    /**
     * Render the dashboard header.
     *
     * @param  OutputInterface  $output  The output interface.
     */
    private function renderHeader(OutputInterface $output): void
    {
        $output->writeln('<bg=blue;fg=white;options=bold> TRADING PLATFORM DASHBOARD </>'.date(' Y-m-d H:i:s'));
        $output->writeln(str_repeat('-', 80));
    }

    /**
     * Render the trading metrics panel.
     *
     * Displays open positions, P&L, fees, and net profit.
     *
     * @param  OutputInterface  $output  The output interface.
     * @param  array  $metrics  Array of trading metrics.
     */
    private function renderTradingPanel(OutputInterface $output, array $metrics): void
    {
        $output->writeln('<info>TRADING METRICS</info>');

        $pnlColor = $metrics['total_pnl'] >= 0 ? 'green' : 'red';
        $netPnlColor = $metrics['net_pnl'] >= 0 ? 'green' : 'red';

        $table = new Table($output);
        $table->setHeaders(['Metric', 'Value']);
        $table->addRow(['Open Positions', $metrics['open_positions']]);
        $table->addRow(['Unrealized P&L', "<fg=$pnlColor>".number_format($metrics['unrealized_pnl'], 2).'</>']);
        $table->addRow(['Realized P&L', number_format($metrics['realized_pnl'], 2)]);
        $table->addRow(['Total Fees', number_format($metrics['total_fees'], 2)]);
        $table->addRow(['Net P&L', "<fg=$netPnlColor>".number_format($metrics['net_pnl'], 2).'</>']);
        $table->render();

        $output->writeln('');
    }

    /**
     * Render the system status panel.
     *
     * Displays CPU/Memory usage, worker counts, and queue depths.
     *
     * @param  OutputInterface  $output  The output interface.
     * @param  array  $system  System metrics (CPU, Memory).
     * @param  array  $workers  Worker metrics.
     * @param  array  $queues  Queue metrics.
     */
    private function renderSystemPanel(OutputInterface $output, array $system, array $workers, array $queues): void
    {
        $output->writeln('<info>SYSTEM STATUS</info>');

        $table = new Table($output);
        $table->setHeaders(['Component', 'Status', 'Metric']);
        $table->addRow(['CPU Load', 'OK', $system['cpu_load']]);
        $table->addRow(['Memory', 'OK', round($system['memory_usage'] / 1024 / 1024, 2).' MB']);
        $table->addRow(['Workers', 'Active', $workers['active_workers'].' active']);
        $table->addRow(['Queues', 'Processing', $queues['pending_jobs'].' pending']);
        $table->render();
    }
}
