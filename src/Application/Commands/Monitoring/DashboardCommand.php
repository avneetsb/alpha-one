<?php

namespace TradingPlatform\Application\Commands\Monitoring;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Application\Services\{QueueMetricsService, LogAnalyticsService};
use TradingPlatform\Infrastructure\Cache\RedisAdapter;

/**
 * Real-time monitoring dashboard
 */
class DashboardCommand extends Command
{
    protected static $defaultName = 'cli:monitor:dashboard';

    protected function configure(): void
    {
        $this->setDescription('Real-time monitoring dashboard')
            ->setHelp(
                "Usage:\n" .
                "  php bin/console cli:monitor:dashboard\n\n" .
                "Behavior:\n" .
                "  Displays queue metrics, system health, performance KPIs, and alerts.\n"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueMetrics = new QueueMetricsService();
        $logAnalytics = new LogAnalyticsService();
        $redis = RedisAdapter::getInstance();

        $output->writeln('<info>=== Trading Platform Dashboard ===</info>');
        $output->writeln('');

        // Queue metrics
        $output->writeln('<comment>Queue Metrics:</comment>');
        $queues = ['orders', 'ticks', 'candles', 'logs'];
        
        foreach ($queues as $queue) {
            $metrics = $queueMetrics->getQueueMetrics($queue);
            $output->writeln(sprintf(
                "  %s: Depth Avg=%.0f, Latency p95=%.2fms",
                $queue,
                $metrics['depth']['avg'],
                $metrics['latency_ms']['p95']
            ));
        }

        $output->writeln('');

        // System health
        $output->writeln('<comment>System Health:</comment>');
        $output->writeln('  Redis: ' . ($redis->getClient()->ping() ? '<info>OK</info>' : '<error>DOWN</error>'));
        
        try {
            \Illuminate\Database\Capsule\Manager::connection()->getPdo();
            $output->writeln('  Database: <info>OK</info>');
        } catch (\Exception $e) {
            $output->writeln('  Database: <error>DOWN</error>');
        }

        $output->writeln('');

        // Performance metrics
        $output->writeln('<comment>Performance Metrics (last hour):</comment>');
        $perfMetrics = $logAnalytics->getPerformanceMetrics('order_placement', 1);
        
        if (!empty($perfMetrics)) {
            $output->writeln(sprintf(
                "  Order Placement: p50=%.2fms, p95=%.2fms, p99=%.2fms",
                $perfMetrics['p50'],
                $perfMetrics['p95'],
                $perfMetrics['p99']
            ));
        }

        $output->writeln('');

        // Alerts
        $output->writeln('<comment>Active Alerts:</comment>');
        $alerts = $this->checkAlerts($queueMetrics);
        
        if (empty($alerts)) {
            $output->writeln('  <info>No active alerts</info>');
        } else {
            foreach ($alerts as $alert) {
                $output->writeln('  <error>' . $alert . '</error>');
            }
        }

        return Command::SUCCESS;
    }

    private function checkAlerts(QueueMetricsService $queueMetrics): array
    {
        $alerts = [];
        $queues = ['orders', 'ticks', 'candles', 'logs'];

        foreach ($queues as $queue) {
            $action = $queueMetrics->shouldAutoScale($queue);
            if ($action) {
                $alerts[] = "Queue '$queue' requires $action";
            }
        }

        return $alerts;
    }
}
