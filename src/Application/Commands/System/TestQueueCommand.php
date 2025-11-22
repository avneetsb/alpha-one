<?php

namespace TradingPlatform\Application\Commands\System;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Infrastructure\Queue\QueueService;
use TradingPlatform\Application\Jobs\LogMessageJob;

class TestQueueCommand extends Command
{
    protected static $defaultName = 'cli:queue:test';

    protected function configure()
    {
        $this->setDescription('Dispatch a test job to the queue')
            ->setHelp(
                "Usage:\n" .
                "  php bin/console cli:queue:test\n\n" .
                "Behavior:\n" .
                "  Boots queue capsule and enqueues a LogMessageJob on the default queue.\n"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        QueueService::boot();
        $queue = QueueService::getConnection();

        $output->writeln("Dispatching LogMessageJob...");
        
        // Dispatch job
        // Since we are not using the global helper dispatch(), we push to queue manually.
        // Illuminate Queue expects the job class and data.
        
        $job = new LogMessageJob('info', 'Hello from the Queue!');
        
        // We need to serialize the job or use the push method which handles it.
        // The standard way in Laravel is dispatch($job), which uses the Bus.
        // Here we use the Queue connection directly.
        
        $queue->push($job);

        $output->writeln("Job dispatched.");
        return Command::SUCCESS;
    }
}
