<?php

namespace TradingPlatform\Application\Workers;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Infrastructure\Queue\QueueService;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;

class QueueWorkerCommand extends Command
{
    protected static $defaultName = 'cli:queue:work';

    protected function configure()
    {
        $this->setDescription('Start processing jobs on the queue as a daemon')
            ->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'The name of the connection', 'redis')
            ->addOption('queue', null, InputOption::VALUE_OPTIONAL, 'The queue to listen on', 'default')
            ->setHelp(
                "Usage:\n" .
                "  php bin/console cli:queue:work [--connection=redis] [--queue=default]\n\n" .
                "Options:\n" .
                "  --connection   Queue connection name. Default: redis.\n" .
                "  --queue        Queue name to process. Default: default.\n\n" .
                "Behavior:\n" .
                "  Boots queue capsule and runs the worker in daemon mode.\n"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connectionName = $input->getOption('connection');
        $queueName = $input->getOption('queue');

        $output->writeln("Starting queue worker for connection: $connectionName, queues: $queueName");

        // Boot Queue Service
        QueueService::boot();
        $queueManager = QueueService::getCapsule()->getQueueManager();

        // Define a simple exception handler
        $exceptionHandler = new class implements \Illuminate\Contracts\Debug\ExceptionHandler {
            public function report(\Throwable $e) { echo "Error: " . $e->getMessage() . "\n"; }
            public function render($request, \Throwable $e) {}
            public function renderForConsole($output, \Throwable $e) {}
            public function shouldReport(\Throwable $e) { return true; }
        };

        // Instantiate the Worker
        $worker = new Worker(
            $queueManager,
            new \Illuminate\Events\Dispatcher(),
            $exceptionHandler,
            function () { return false; } // isDownForMaintenance
        );

        $options = new WorkerOptions();
        
        // Run the worker (blocking)
        $worker->daemon($connectionName, $queueName, $options);

        return Command::SUCCESS;
    }
}
