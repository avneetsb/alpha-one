<?php

namespace TradingPlatform\Application\Commands\System;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command: Format Code
 *
 * Runs Laravel Pint to automatically fix coding style issues across the project.
 * Ensures the codebase adheres to PSR-12 and other defined standards.
 */
class FormatCodeCommand extends Command
{
    protected static $defaultName = 'system:format';

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
        $this->setDescription('Format all project files using Laravel Pint')
            ->setHelp(
                "Usage:\n".
                "  php bin/console system:format\n\n".
                "Description:\n".
                "  Runs Laravel Pint across the repository to apply PSR-12 compliant formatting.\n\n".
                "Notes:\n".
                "  - Requires dev dependency 'laravel/pint'.\n".
                "  - Formats PHP files in-place.\n"
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
     * Execute the code formatting.
     *
     * Locates the `pint` binary and executes it on the project root.
     * Pipes stdout and stderr to the console output.
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface  $output  Command output.
     * @return int Command exit code.
     *
     * @example Format code
     * ```bash
     * php bin/console system:format
     * ```
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectRoot = realpath(__DIR__.'/../../../../');
        $pintPath = $projectRoot.'/vendor/bin/pint';

        if (! is_file($pintPath)) {
            $output->writeln('<error>Laravel Pint is not installed. Run: composer require laravel/pint --dev</error>');

            return Command::FAILURE;
        }

        $output->writeln('<info>Formatting code with Laravel Pint...</info>');

        $descriptorSpec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($pintPath, $descriptorSpec, $pipes, $projectRoot);
        if (! \is_resource($process)) {
            $output->writeln('<error>Failed to start Pint process</error>');

            return Command::FAILURE;
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($stdout) {
            $output->writeln($stdout);
        }
        if ($stderr) {
            $output->writeln('<comment>'.$stderr.'</comment>');
        }

        return $exitCode === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
