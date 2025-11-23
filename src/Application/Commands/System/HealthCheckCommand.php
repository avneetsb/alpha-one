<?php

namespace TradingPlatform\Application\Commands\System;

use Illuminate\Database\Capsule\Manager as Capsule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Infrastructure\Cache\RedisAdapter;
use TradingPlatform\Infrastructure\Database\DatabaseConnection;

/**
 * Command: System Health Check
 *
 * Verifies the connectivity and operational status of critical infrastructure components:
 * - Database (MySQL/PostgreSQL)
 * - Cache (Redis)
 * - Broker API (Mocked/Real)
 */
class HealthCheckCommand extends Command
{
    protected static $defaultName = 'cli:system:health';

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
        $this->setDescription('Check system health (DB, Redis, Broker)')
            ->setHelp(
                "Usage:\n".
                "  php bin/console cli:system:health\n\n".
                "Behavior:\n".
                "  Verifies DB and Redis connectivity; broker check is mocked.\n"
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
     * Execute the health checks.
     *
     * Attempts to connect to each service and reports PASS/FAIL status.
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface  $output  Command output.
     * @return int Command exit code.
     *
     * @example Run health check
     * ```bash
     * php bin/console cli:system:health
     * ```
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Running System Health Checks...');

        // 1. Database Check
        try {
            DatabaseConnection::boot();
            Capsule::connection()->getPdo();
            $output->writeln('<info>[PASS]</info> Database Connection');
        } catch (\Exception $e) {
            $output->writeln('<error>[FAIL]</error> Database Connection: '.$e->getMessage());
        }

        // 2. Redis Check
        try {
            $redis = RedisAdapter::getInstance();
            $redis->set('health_check', 'ok', 10);
            $val = $redis->get('health_check');
            if ($val === 'ok') {
                $output->writeln('<info>[PASS]</info> Redis Connection');
            } else {
                throw new \Exception('Redis read/write failed');
            }
        } catch (\Exception $e) {
            $output->writeln('<error>[FAIL]</error> Redis Connection: '.$e->getMessage());
        }

        // 3. Broker Check (Mock)
        // In a real scenario, we would ping the broker API
        $output->writeln('<info>[PASS]</info> Broker Connection (Mocked)');

        return Command::SUCCESS;
    }
}
