<?php

namespace TradingPlatform\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use TradingPlatform\Domain\Optimization\Models\OptimizationRun;
use TradingPlatform\Domain\Strategy\Models\StrategyConfiguration;
use TradingPlatform\Domain\Backtesting\BacktestResult;

/**
 * List optimization runs and results
 */
class ListOptimizationRunsCommand extends Command
{
    protected static $defaultName = 'optimization:list';

    protected function configure(): void
    {
        $this
            ->setDescription('List optimization runs')
            ->setHelp('Display all optimization runs with their status and results')
            ->addOption('status', 's', InputOption::VALUE_OPTIONAL, 'Filter by status (completed, running, failed)')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit number of results', 10);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $status = $input->getOption('status');
        $limit = (int)$input->getOption('limit');

        $query = OptimizationRun::with('strategy');

        if ($status) {
            $query->where('status', $status);
        }

        $runs = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        if ($runs->isEmpty()) {
            $output->writeln('<comment>No optimization runs found.</comment>');
            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['ID', 'Strategy', 'Name', 'Status', 'Best Fitness', 'Progress', 'Started', 'Duration']);

        foreach ($runs as $run) {
            $duration = $run->getDurationSeconds();
            $durationStr = $duration ? gmdate('H:i:s', $duration) : 'N/A';

            $table->addRow([
                $run->id,
                $run->strategy->name ?? 'Unknown',
                $run->name,
                $this->colorizeStatus($run->status),
                $run->best_fitness ? number_format($run->best_fitness, 4) : 'N/A',
                number_format($run->getProgressPercentage(), 1) . '%',
                $run->started_at ? $run->started_at->format('Y-m-d H:i') : 'Not started',
                $durationStr,
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }

    private function colorizeStatus(string $status): string
    {
        return match($status) {
            'completed' => "<info>{$status}</info>",
            'running' => "<comment>{$status}</comment>",
            'failed' => "<error>{$status}</error>",
            default => $status,
        };
    }
}
