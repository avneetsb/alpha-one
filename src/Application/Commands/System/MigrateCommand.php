<?php

namespace TradingPlatform\Application\Commands\System;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command: Database Migration
 *
 * Scans the migrations directory and executes pending migrations to update
 * the database schema. Ensures the database structure matches the codebase requirements.
 */
class MigrateCommand extends Command
{
    protected static $defaultName = 'cli:migrate';

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
        $this->setDescription('Run database migrations')
            ->setHelp(
                "Usage:\n".
                "  php bin/console cli:migrate\n\n".
                "Description:\n".
                "  Scans 'database/migrations' and executes the 'up()' method on each migration class.\n\n".
                "Notes:\n".
                "  - Runs in filename order.\n".
                "  - Continues on errors to allow partial progress; check output for failures.\n".
                "  - Ensure DB settings in .env and config/database.php are correct.\n"
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
     * Execute the migrations.
     *
     * Iterates through migration files, instantiates the classes, and calls `up()`.
     * Handles exceptions to prevent a single failure from halting the entire process (optional behavior).
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface  $output  Command output.
     * @return int Command exit code.
     *
     * @example Run migrations
     * ```bash
     * php bin/console cli:migrate
     * ```
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Running migrations...');

        $migrationFiles = glob(__DIR__.'/../../../../database/migrations/*.php');

        foreach ($migrationFiles as $file) {
            require_once $file;
            $className = pathinfo($file, PATHINFO_FILENAME);

            if (class_exists($className)) {
                $output->writeln("Migrating: $className");
                $migration = new $className;

                try {
                    $migration->up();
                    $output->writeln("Migrated: $className");
                } catch (\Exception $e) {
                    $output->writeln("Error migrating $className: ".$e->getMessage());
                    // Continue or exit? For now, log and continue
                }
            }
        }

        $output->writeln('Migrations completed.');

        return Command::SUCCESS;
    }
}
