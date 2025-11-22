<?php

namespace TradingPlatform\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Run database migrations
 */
class MigrateCommand extends Command
{
    protected static $defaultName = 'migrate';

    protected function configure(): void
    {
        $this
            ->setDescription('Run database migrations')
            ->setHelp('Execute all pending database migrations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Running database migrations...</info>');

        $migrationsPath = __DIR__ . '/../../../database/migrations';
        $files = glob($migrationsPath . '/*.php');

        if (empty($files)) {
            $output->writeln('<comment>No migration files found.</comment>');
            return Command::SUCCESS;
        }

        $executedCount = 0;

        foreach ($files as $file) {
            $migrationName = basename($file, '.php');
            
            try {
                require_once $file;
                
                // Get the migration class instance
                $migration = new class { };
                
                // Execute up() method if exists
                if (method_exists($migration, 'up')) {
                    $output->writeln("Migrating: <comment>{$migrationName}</comment>");
                    $migration->up();
                    $output->writeln("<info>Migrated:  {$migrationName}</info>");
                    $executedCount++;
                }
            } catch (\Exception $e) {
                $output->writeln("<error>Failed to migrate {$migrationName}: {$e->getMessage()}</error>");
                return Command::FAILURE;
            }
        }

        $output->writeln("\n<info>Migration completed successfully! Executed {$executedCount} migrations.</info>");

        return Command::SUCCESS;
    }
}
