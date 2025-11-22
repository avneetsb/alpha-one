<?php

namespace TradingPlatform\Application\Workers;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Infrastructure\Logger\LoggerService;
use TradingPlatform\Infrastructure\Cache\RedisAdapter;

class LoggingWorkerCommand extends Command
{
    protected static $defaultName = 'cli:workers:logging';
    private const BATCH_INTERVAL_MS = 1000; // 1 second as per requirements
    private const MAX_BATCH_SIZE = 100;
    private const LOG_QUEUE_KEY = 'queue:logs:pending';
    
    private \Monolog\Logger $logger;
    private RedisAdapter $redis;

    public function __construct()
    {
        parent::__construct();
        $this->logger = LoggerService::getLogger();
        $this->redis = RedisAdapter::getInstance();
    }

    protected function configure(): void
    {
        $this->setDescription('Background worker for async log processing and batching')
            ->setHelp(
                "Usage:\n" .
                "  php bin/console cli:workers:logging\n\n" .
                "Behavior:\n" .
                "  Reads from Redis queue '" . self::LOG_QUEUE_KEY . "' and flushes batched logs to DB.\n" .
                "  Batch interval: " . self::BATCH_INTERVAL_MS . "ms; Max batch size: " . self::MAX_BATCH_SIZE . ".\n"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting async logging worker...');
        $output->writeln('Batch interval: ' . self::BATCH_INTERVAL_MS . 'ms');
        $output->writeln('Max batch size: ' . self::MAX_BATCH_SIZE);

        $batchStartTime = microtime(true);
        $logBatch = [];

        while (true) {
            try {
                // Check if batch interval elapsed or batch is full
                $elapsed = (microtime(true) - $batchStartTime) * 1000;
                $shouldFlush = $elapsed >= self::BATCH_INTERVAL_MS || count($logBatch) >= self::MAX_BATCH_SIZE;

                if ($shouldFlush && !empty($logBatch)) {
                    $this->flushBatch($logBatch);
                    $logBatch = [];
                    $batchStartTime = microtime(true);
                }

                // Fetch log from queue (non-blocking with timeout)
                $logData = $this->redis->getClient()->brpop([self::LOG_QUEUE_KEY], 0.1);
                
                if ($logData) {
                    $logEntry = json_decode($logData[1], true);
                    if ($logEntry) {
                        $logBatch[] = $logEntry;
                    }
                }

                // Small sleep to prevent CPU spinning
                usleep(10000); // 10ms

            } catch (\Exception $e) {
                $output->writeln('<error>Error in logging worker: ' . $e->getMessage() . '</error>');
                // Continue processing
            }
        }

        return Command::SUCCESS;
    }

    private function flushBatch(array $logBatch): void
    {
        $startTime = microtime(true);
        
        try {
            // Write to database in single transaction
            \Illuminate\Database\Capsule\Manager::transaction(function () use ($logBatch) {
                foreach ($logBatch as $logEntry) {
                    \Illuminate\Database\Capsule\Manager::table('logs')->insert([
                        'level' => $logEntry['level'] ?? 'INFO',
                        'message' => $logEntry['message'] ?? '',
                        'context' => json_encode($logEntry['context'] ?? []),
                        'trace_id' => $logEntry['trace_id'] ?? null,
                        'created_at' => $logEntry['timestamp'] ?? date('Y-m-d H:i:s'),
                    ]);
                }
            });

            // Also write to console for errors
            foreach ($logBatch as $logEntry) {
                if (in_array($logEntry['level'] ?? '', ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'])) {
                    $this->logger->error($logEntry['message'] ?? '', $logEntry['context'] ?? []);
                }
            }

            $duration = (microtime(true) - $startTime) * 1000;
            
            // SLO check: p95 should be ≤ 500ms, p99 ≤ 1s
            if ($duration > 500) {
                $this->logger->warning('Slow log batch flush', [
                    'duration_ms' => $duration,
                    'batch_size' => count($logBatch),
                    'slo_breach' => true
                ]);
            }

        } catch (\Exception $e) {
            // Fallback to console logging
            $this->logger->error('Failed to flush log batch to database', [
                'error' => $e->getMessage(),
                'batch_size' => count($logBatch)
            ]);
            
            // Write critical logs to console
            foreach ($logBatch as $logEntry) {
                if ($logEntry['level'] === 'ERROR' || $logEntry['level'] === 'CRITICAL') {
                    error_log(json_encode($logEntry));
                }
            }
        }
    }
}
