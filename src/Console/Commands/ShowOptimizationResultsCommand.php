<?php

namespace TradingPlatform\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Domain\Optimization\Models\OptimizationResult;
use TradingPlatform\Domain\Optimization\Models\OptimizationRun;

/**
 * Class ShowOptimizationResultsCommand
 *
 * Console command to show detailed results for a specific optimization run.
 */
class ShowOptimizationResultsCommand extends Command
{
    /**
     * @var string|null The default name of the command.
     */
    protected static $defaultName = 'optimization:show';

    /**
     * Configure the command options and arguments.
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Show optimization run results')
            ->setHelp('Display detailed results for a specific optimization run')
            ->addArgument('run-id', InputArgument::REQUIRED, 'Optimization run ID');
    }

    /**
     * Execute the command.
     *
     * @return int Command exit code.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $runId = $input->getArgument('run-id');

        $run = OptimizationRun::with(['strategy', 'bestConfig'])->find($runId);

        if (! $run) {
            $output->writeln("<error>Optimization run {$runId} not found.</error>");

            return Command::FAILURE;
        }

        // Display run info
        $output->writeln("\n<info>=== Optimization Run #{$run->id} ===</info>");
        $output->writeln("Strategy: {$run->strategy->name}");
        $output->writeln("Name: {$run->name}");
        $output->writeln('Status: '.$this->colorizeStatus($run->status));
        $output->writeln("Algorithm: {$run->algorithm}");
        $output->writeln("Data: {$run->data_source} ({$run->data_period_start} to {$run->data_period_end})");
        $output->writeln("Progress: {$run->current_generation}/{$run->total_generations} generations");
        $output->writeln("Total Evaluations: {$run->total_evaluations}");

        if ($run->isComplete()) {
            $output->writeln("\n<info>=== Best Result ===</info>");
            $output->writeln('Best Fitness: '.number_format($run->best_fitness, 6));

            if ($run->bestConfig) {
                $output->writeln("Best DNA: {$run->bestConfig->dna}");
                $output->writeln('Best Parameters:');
                $output->writeln(json_encode($run->bestConfig->hyperparameters, JSON_PRETTY_PRINT));
            }
        }

        // Show top results
        $output->writeln("\n<info>=== Top 10 Results ===</info>");

        $topResults = OptimizationResult::where('optimization_run_id', $runId)
            ->orderBy('fitness_score', 'desc')
            ->limit(10)
            ->get();

        if ($topResults->isEmpty()) {
            $output->writeln('<comment>No results found.</comment>');

            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['Rank', 'Generation', 'DNA', 'Fitness', 'Elite']);

        foreach ($topResults as $index => $result) {
            $table->addRow([
                $index + 1,
                $result->generation,
                substr($result->dna, 0, 30).'...',
                number_format($result->fitness_score, 6),
                $result->is_elite ? 'Yes' : 'No',
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }

    /**
     * Colorize the status string for display.
     */
    private function colorizeStatus(string $status): string
    {
        return match ($status) {
            'completed' => "<info>{$status}</info>",
            'running' => "<comment>{$status}</comment>",
            'failed' => "<error>{$status}</error>",
            default => $status,
        };
    }
}
