<?php

namespace TradingPlatform\Application\Commands\System;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Application\Jobs\LogMessageJob;
use TradingPlatform\Infrastructure\Queue\QueueService;

/**
 * Command: Test Queue Dispatch
 *
 * Dispatches a test job to the default queue to verify queue worker functionality.
 * Useful for ensuring the queue connection (Redis/Database) is operational.
 */
class TestQueueCommand extends Command
{
    protected static $defaultName = 'cli:queue:test';

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
        $this->setDescription('Dispatch a test job to the queue')
            ->setHelp(
                "Usage:\n".
                "  php bin/console cli:queue:test\n\n".
                "Behavior:\n".
                "  Boots queue capsule and enqueues a LogMessageJob on the default queue.\n"
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
     * Execute the job dispatch.
     *
     * Initializes the queue service and pushes a `LogMessageJob` onto the stack.
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface  $output  Command output.
     * @return int Command exit code.
     *
     * @example Dispatch test job
     * ```bash
     * php bin/console cli:queue:test
     * ```
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        QueueService::boot();
        $queue = QueueService::getConnection();

        $output->writeln('Dispatching LogMessageJob...');

        // Dispatch job
        // Since we are not using the global helper dispatch(), we push to queue manually.
        // Illuminate Queue expects the job class and data.

        $job = new LogMessageJob('info', 'Hello from the Queue!');

        // We need to serialize the job or use the push method which handles it.
        // The standard way in Laravel is dispatch($job), which uses the Bus.
        // Here we use the Queue connection directly.

        $queue->push($job);

        $output->writeln('Job dispatched.');

        return Command::SUCCESS;
    }
}
