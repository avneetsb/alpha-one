<?php

namespace TradingPlatform\Application\Commands\System;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command
{
    protected static $defaultName = 'cli:migrate';

    protected function configure()
    {
        $this->setDescription('Run database migrations')
            ->setHelp(
                "Usage:\n" .
                "  php bin/console cli:migrate\n\n" .
                "Description:\n" .
                "  Scans 'database/migrations' and executes the 'up()' method on each migration class.\n\n" .
                "Notes:\n" .
                "  - Runs in filename order.\n" .
                "  - Continues on errors to allow partial progress; check output for failures.\n" .
                "  - Ensure DB settings in .env and config/database.php are correct.\n"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Running migrations...');

        $migrationFiles = glob(__DIR__ . '/../../../../database/migrations/*.php');

        foreach ($migrationFiles as $file) {
            require_once $file;
            $className = pathinfo($file, PATHINFO_FILENAME);
            
            if (class_exists($className)) {
                $output->writeln("Migrating: $className");
                $migration = new $className();
                
                try {
                    $migration->up();
                    $output->writeln("Migrated: $className");
                } catch (\Exception $e) {
                    $output->writeln("Error migrating $className: " . $e->getMessage());
                    // Continue or exit? For now, log and continue
                }
            }
        }

        $output->writeln('Migrations completed.');
        return Command::SUCCESS;
    }
}
