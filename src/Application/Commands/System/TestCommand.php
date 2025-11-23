<?php

namespace TradingPlatform\Application\Commands\System;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command: Run Tests
 *
 * Executes the project's test suite using Pest PHP.
 * Supports filtering, coverage reporting, and colorized output.
 * Acts as a wrapper around the Pest binary for easier CLI usage.
 */
class TestCommand extends Command
{
    protected static $defaultName = 'system:test';

    /**
     * Configure the command options.
     *
     * Defines options for colors, specific paths, coverage, and test filtering.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Run Pest tests with coverage and summary')
            ->addOption('colors', null, InputOption::VALUE_NONE, 'Force ANSI colors')
            ->addOption('no-colors', null, InputOption::VALUE_NONE, 'Disable ANSI colors')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Run a specific test file or directory')
            ->addOption('no-coverage', null, InputOption::VALUE_NONE, 'Run without coverage')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED, 'Filter tests by name');
    }

    /**
     * Execute the test runner.
     *
     * Constructs the Pest command line arguments based on input options
     * and executes the test process. Optionally generates a coverage summary.
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface  $output  Command output.
     * @return int Command exit code.
     *
     * @example Run all tests
     * ```bash
     * php bin/console system:test
     * ```
     * @example Run specific test file
     * ```bash
     * php bin/console system:test --path=tests/Unit/OrderTest.php
     * ```
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $root = realpath(__DIR__.'/../../../../');
        $pest = $root.'/vendor/bin/pest';
        $summary = $root.'/scripts/coverage-summary.php';

        if (! is_file($pest)) {
            $output->writeln('<error>Pest is not installed. Run: composer require pestphp/pest --dev</error>');

            return Command::FAILURE;
        }

        $args = [];
        if ($input->getOption('no-colors')) {
            $args[] = '--colors=never';
        } elseif ($input->getOption('colors')) {
            $args[] = '--colors=always';
        }
        if ($input->getOption('filter')) {
            $args[] = '--filter='.escapeshellarg($input->getOption('filter'));
        }
        if ($input->getOption('path')) {
            $args[] = escapeshellarg($input->getOption('path'));
        }

        $withCoverage = ! $input->getOption('no-coverage');
        if ($withCoverage) {
            $args[] = '--coverage';
        }

        $cmd = $pest.' '.implode(' ', $args);

        $output->writeln('<info>Running tests...</info>');

        $spec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $env = $withCoverage ? ['XDEBUG_MODE' => 'coverage'] : null;
        $proc = proc_open($cmd, $spec, $pipes, $root, $env);
        if (! is_resource($proc)) {
            $output->writeln('<error>Failed to start test process</error>');

            return Command::FAILURE;
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);

        if ($stdout) {
            $output->writeln($stdout);
        }
        if ($stderr) {
            $output->writeln('<comment>'.$stderr.'</comment>');
        }

        if ($withCoverage && is_file($summary)) {
            $output->writeln('<info>Coverage summary:</info>');
            $sumSpec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $sumProc = proc_open('php '.escapeshellarg($summary), $sumSpec, $sumPipes, $root);
            if (is_resource($sumProc)) {
                $sumOut = stream_get_contents($sumPipes[1]);
                $sumErr = stream_get_contents($sumPipes[2]);
                fclose($sumPipes[1]);
                fclose($sumPipes[2]);
                proc_close($sumProc);
                if ($sumOut) {
                    $output->writeln($sumOut);
                }
                if ($sumErr) {
                    $output->writeln('<comment>'.$sumErr.'</comment>');
                }
            }
        }

        return $exit === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
