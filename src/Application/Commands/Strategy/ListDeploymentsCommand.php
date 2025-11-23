<?php

namespace TradingPlatform\Application\Commands\Strategy;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Application\Services\StrategyDeploymentService;
use TradingPlatform\Infrastructure\Database\DatabaseConnection;

/**
 * Command: List Deployments
 *
 * Lists all deployed trading strategies and their current status (running, stopped, archived).
 * Supports filtering by execution mode (paper/live) and status.
 */
class ListDeploymentsCommand extends Command
{
    protected static $defaultName = 'cli:strategy:deployments';

    /**
     * Configure the command options.
     *
     * Defines filters for mode and status, and a flag for detailed output.
     */
    protected function configure(): void
    {
        $this
            ->setDescription('List all deployed strategies')
            ->addOption('mode', 'm', InputOption::VALUE_OPTIONAL, 'Filter by mode (paper|sandbox|live)')
            ->addOption('status', 's', InputOption::VALUE_OPTIONAL, 'Filter by status (running|stopped|deployed)')
            ->addOption('detailed', 'd', InputOption::VALUE_NONE, 'Show detailed information')
            ->setHelp(
                "Usage:\n".
                "  List all deployments:\n".
                "    php bin/console cli:strategy:deployments\n\n".
                "  Filter by mode:\n".
                "    php bin/console cli:strategy:deployments --mode=live\n\n".
                "  Show detailed information:\n".
                "    php bin/console cli:strategy:deployments --detailed\n\n".
                "Options:\n".
                "  --mode, -m       Filter by execution mode.\n".
                "  --status, -s     Filter by status.\n".
                "  --detailed, -d   Show detailed performance metrics.\n"
            );
    }

    /**
     * Execute the deployment listing.
     *
     * Retrieves deployments from the service, applies filters, and renders
     * a summary table. Optionally fetches and displays detailed metrics.
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface  $output  Command output.
     * @return int Command exit code.
     *
     * @example List live running strategies
     * ```bash
     * php bin/console cli:strategy:deployments --mode=live --status=running
     * ```
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $modeFilter = $input->getOption('mode');
        $statusFilter = $input->getOption('status');
        $detailed = $input->getOption('detailed');

        // Initialize database
        DatabaseConnection::boot();
        $db = \Illuminate\Database\Capsule\Manager::connection()->getPdo();

        $deploymentService = new StrategyDeploymentService($db);

        // Get deployments
        $deployments = $deploymentService->listDeployments();

        // Apply filters
        if ($modeFilter) {
            $deployments = array_filter($deployments, fn ($d) => $d['mode'] === $modeFilter);
        }

        if ($statusFilter) {
            $deployments = array_filter($deployments, fn ($d) => $d['status'] === $statusFilter);
        }

        if (empty($deployments)) {
            $output->writeln('<info>No deployments found.</info>');

            return Command::SUCCESS;
        }

        // Display summary
        $output->writeln('<info>=== Strategy Deployments ===</info>');
        $output->writeln('');

        // Create table
        $table = new Table($output);
        $table->setHeaders(['ID', 'Strategy', 'Mode', 'Status', 'Deployed At']);

        foreach ($deployments as $deployment) {
            $table->addRow([
                substr($deployment['deployment_id'], 0, 12).'...',
                $deployment['strategy'],
                $this->formatMode($deployment['mode']),
                $this->formatStatus($deployment['status']),
                date('Y-m-d H:i:s', $deployment['deployed_at']),
            ]);
        }

        $table->render();

        // Show detailed information if requested
        if ($detailed) {
            $output->writeln('');
            $output->writeln('<info>=== Detailed Performance ===</info>');
            $output->writeln('');

            foreach ($deployments as $deployment) {
                try {
                    $status = $deploymentService->getStatus($deployment['deployment_id']);
                    $this->displayDetailedStatus($output, $status);
                } catch (\Exception $e) {
                    $output->writeln("<error>Error fetching details for {$deployment['deployment_id']}: {$e->getMessage()}</error>");
                }
            }
        }

        // Show performance summary
        $summary = $deploymentService->getPerformanceSummary();
        $output->writeln('');
        $output->writeln('<info>=== Summary ===</info>');
        $output->writeln("Total Deployments: {$summary['total_deployments']}");
        $output->writeln("Running: {$summary['running']}");
        $output->writeln("Stopped: {$summary['stopped']}");
        $output->writeln("Total Trades: {$summary['total_trades']}");
        if (isset($summary['total_pnl'])) {
            $output->writeln('Total P&L: ₹'.number_format($summary['total_pnl'], 2));
        }

        return Command::SUCCESS;
    }

    /**
     * Format mode for display.
     *
     * @param  string  $mode  Execution mode.
     * @return string Formatted mode.
     */
    private function formatMode(string $mode): string
    {
        return match ($mode) {
            'paper' => '<comment>PAPER</comment>',
            'sandbox' => '<info>SANDBOX</info>',
            'live' => '<error>LIVE</error>',
            default => $mode,
        };
    }

    /**
     * Format status for display.
     *
     * @param  string  $status  Deployment status.
     * @return string Formatted status.
     */
    private function formatStatus(string $status): string
    {
        return match ($status) {
            'running' => '<info>RUNNING</info>',
            'stopped' => '<comment>STOPPED</comment>',
            'deployed' => 'DEPLOYED',
            'archived' => 'ARCHIVED',
            default => $status,
        };
    }

    /**
     * Display detailed status information.
     *
     * @param  OutputInterface  $output  Command output.
     * @param  array  $status  Status information.
     */
    private function displayDetailedStatus(OutputInterface $output, array $status): void
    {
        $output->writeln("Deployment: {$status['deployment_id']}");
        $output->writeln("  Strategy: {$status['strategy']}");
        $output->writeln("  Mode: {$status['mode']}");
        $output->writeln("  Status: {$status['status']}");

        if (isset($status['performance'])) {
            $perf = $status['performance'];
            $output->writeln('  Performance:');
            $output->writeln("    Total Trades: {$perf['total_trades']}");

            if (isset($perf['total_pnl'])) {
                $output->writeln('    Total P&L: ₹'.number_format($perf['total_pnl'], 2));
                $output->writeln('    Return: '.round($perf['return_pct'], 2).'%');
                $output->writeln('    Current Value: ₹'.number_format($perf['current_value'], 2));
            }
        }

        if (isset($status['positions']) && ! empty($status['positions'])) {
            $output->writeln('  Open Positions: '.count($status['positions']));
        }

        $output->writeln('');
    }
}
