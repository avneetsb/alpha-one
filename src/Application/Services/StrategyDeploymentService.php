<?php

namespace TradingPlatform\Application\Services;

use TradingPlatform\Domain\Strategy\Strategy;
use TradingPlatform\Domain\Strategy\StrategyRunner;
use TradingPlatform\Infrastructure\Broker\BrokerAdapterInterface;
use TradingPlatform\Infrastructure\Logger\LoggerService;

/**
 * Strategy Deployment Service
 *
 * Manages deployment, monitoring, and lifecycle of trading strategies
 * across different execution modes (Paper, Sandbox, Live).
 * Acts as the orchestration layer for strategy execution.
 *
 * @version 1.0.0
 */
class StrategyDeploymentService
{
    private array $deployedStrategies = [];

    private $logger;

    private $database;

    /**
     * StrategyDeploymentService constructor.
     *
     * @param  mixed  $database  Database connection.
     */
    public function __construct($database)
    {
        $this->database = $database;
        $this->logger = LoggerService::getLogger();
    }

    /**
     * Deploy a strategy.
     *
     * Instantiates a strategy runner with the specified configuration and mode.
     * Validates dependencies (e.g., broker adapter for live mode) before deployment.
     *
     * @param  Strategy  $strategy  Strategy to deploy.
     * @param  string  $mode  Execution mode (paper, sandbox, live).
     * @param  array  $config  Deployment configuration (capital, risk limits, etc.).
     * @return string Deployment ID.
     *
     * @example Deploying a strategy
     * ```php
     * $config = ['capital' => 100000, 'risk_limits' => ['max_drawdown' => 10]];
     * $id = $service->deploy($myStrategy, 'paper', $config);
     * ```
     */
    public function deploy(Strategy $strategy, string $mode, array $config = []): string
    {
        $deploymentId = uniqid('deploy_');

        // Validate deployment
        $this->validateDeployment($strategy, $mode, $config);

        // Create strategy runner
        $runner = new StrategyRunner(
            $strategy,
            $mode,
            $config['capital'] ?? 100000,
            $config['broker_adapter'] ?? null,
            $config['risk_limits'] ?? []
        );

        // Store deployment record
        $this->storeDeployment($deploymentId, $strategy, $mode, $config);

        // Register deployment
        $this->deployedStrategies[$deploymentId] = [
            'runner' => $runner,
            'strategy' => $strategy,
            'mode' => $mode,
            'config' => $config,
            'deployed_at' => time(),
            'status' => 'deployed',
        ];

        $this->logger->info('Strategy deployed', [
            'deployment_id' => $deploymentId,
            'strategy' => $strategy->getName(),
            'mode' => $mode,
        ]);

        return $deploymentId;
    }

    /**
     * Start a deployed strategy.
     *
     * Activates the strategy runner, allowing it to process market data and generate signals.
     *
     * @param  string  $deploymentId  Deployment ID.
     *
     * @example Starting a strategy
     * ```php
     * $service->start($deploymentId);
     * ```
     */
    public function start(string $deploymentId): void
    {
        if (! isset($this->deployedStrategies[$deploymentId])) {
            throw new \RuntimeException("Deployment not found: $deploymentId");
        }

        $deployment = &$this->deployedStrategies[$deploymentId];
        $deployment['runner']->start();
        $deployment['status'] = 'running';
        $deployment['started_at'] = time();

        $this->updateDeploymentStatus($deploymentId, 'running');

        $this->logger->info('Strategy started', [
            'deployment_id' => $deploymentId,
            'strategy' => $deployment['strategy']->getName(),
        ]);
    }

    /**
     * Stop a running strategy.
     *
     * Pauses execution, preventing new orders from being generated.
     * Existing positions may remain open depending on strategy logic.
     *
     * @param  string  $deploymentId  Deployment ID.
     *
     * @example Stopping a strategy
     * ```php
     * $service->stop($deploymentId);
     * ```
     */
    public function stop(string $deploymentId): void
    {
        if (! isset($this->deployedStrategies[$deploymentId])) {
            throw new \RuntimeException("Deployment not found: $deploymentId");
        }

        $deployment = &$this->deployedStrategies[$deploymentId];
        $deployment['runner']->stop();
        $deployment['status'] = 'stopped';
        $deployment['stopped_at'] = time();

        $this->updateDeploymentStatus($deploymentId, 'stopped');

        $this->logger->info('Strategy stopped', [
            'deployment_id' => $deploymentId,
            'strategy' => $deployment['strategy']->getName(),
        ]);
    }

    /**
     * Undeploy a strategy.
     *
     * Stops the strategy if running and removes it from the active deployment list.
     * Archives performance data for historical analysis.
     *
     * @param  string  $deploymentId  Deployment ID.
     *
     * @example Undeploying
     * ```php
     * $service->undeploy($deploymentId);
     * ```
     */
    public function undeploy(string $deploymentId): void
    {
        if (! isset($this->deployedStrategies[$deploymentId])) {
            throw new \RuntimeException("Deployment not found: $deploymentId");
        }

        // Stop if running
        if ($this->deployedStrategies[$deploymentId]['status'] === 'running') {
            $this->stop($deploymentId);
        }

        // Archive deployment data
        $this->archiveDeployment($deploymentId);

        // Remove from active deployments
        unset($this->deployedStrategies[$deploymentId]);

        $this->logger->info('Strategy undeployed', [
            'deployment_id' => $deploymentId,
        ]);
    }

    /**
     * Get deployment status.
     *
     * Retrieves real-time status, including current state (running/stopped),
     * performance metrics, and active positions.
     *
     * @param  string  $deploymentId  Deployment ID.
     * @return array Deployment status details.
     *
     * @example Checking status
     * ```php
     * $status = $service->getStatus($id);
     * echo "Status: " . $status['status'];
     * echo "PnL: " . $status['performance']['total_pnl'];
     * ```
     */
    public function getStatus(string $deploymentId): array
    {
        if (! isset($this->deployedStrategies[$deploymentId])) {
            throw new \RuntimeException("Deployment not found: $deploymentId");
        }

        $deployment = $this->deployedStrategies[$deploymentId];

        return [
            'deployment_id' => $deploymentId,
            'strategy' => $deployment['strategy']->getName(),
            'mode' => $deployment['mode'],
            'status' => $deployment['status'],
            'deployed_at' => $deployment['deployed_at'],
            'started_at' => $deployment['started_at'] ?? null,
            'stopped_at' => $deployment['stopped_at'] ?? null,
            'performance' => $deployment['runner']->getPerformanceMetrics(),
            'positions' => $deployment['runner']->getPositions(),
        ];
    }

    /**
     * List all deployments.
     *
     * Returns a summary list of all currently deployed strategies.
     *
     * @return array List of deployments.
     */
    public function listDeployments(): array
    {
        $deployments = [];

        foreach ($this->deployedStrategies as $id => $deployment) {
            $deployments[] = [
                'deployment_id' => $id,
                'strategy' => $deployment['strategy']->getName(),
                'mode' => $deployment['mode'],
                'status' => $deployment['status'],
                'deployed_at' => $deployment['deployed_at'],
            ];
        }

        return $deployments;
    }

    /**
     * Get performance summary for all deployments.
     *
     * Aggregates performance metrics across all deployed strategies to provide
     * a global view of system performance.
     *
     * @return array Performance summary (total PnL, trade counts, etc.).
     *
     * @example Global summary
     * ```php
     * $summary = $service->getPerformanceSummary();
     * echo "Total PnL across all strategies: " . $summary['total_pnl'];
     * ```
     */
    public function getPerformanceSummary(): array
    {
        $summary = [
            'total_deployments' => count($this->deployedStrategies),
            'running' => 0,
            'stopped' => 0,
            'total_trades' => 0,
            'total_pnl' => 0,
        ];

        foreach ($this->deployedStrategies as $deployment) {
            if ($deployment['status'] === 'running') {
                $summary['running']++;
            } elseif ($deployment['status'] === 'stopped') {
                $summary['stopped']++;
            }

            $metrics = $deployment['runner']->getPerformanceMetrics();
            $summary['total_trades'] += $metrics['total_trades'] ?? 0;
            $summary['total_pnl'] += $metrics['total_pnl'] ?? 0;
        }

        return $summary;
    }

    /**
     * Validate deployment configuration.
     *
     * @param  Strategy  $strategy  Strategy to deploy.
     * @param  string  $mode  Execution mode.
     * @param  array  $config  Deployment configuration.
     */
    private function validateDeployment(Strategy $strategy, string $mode, array $config): void
    {
        // Validate mode
        $validModes = [StrategyRunner::MODE_PAPER, StrategyRunner::MODE_SANDBOX, StrategyRunner::MODE_LIVE];
        if (! in_array($mode, $validModes)) {
            throw new \InvalidArgumentException("Invalid mode: $mode");
        }

        // Validate broker adapter for sandbox/live
        if (in_array($mode, [StrategyRunner::MODE_SANDBOX, StrategyRunner::MODE_LIVE])) {
            if (! isset($config['broker_adapter']) || ! ($config['broker_adapter'] instanceof BrokerAdapterInterface)) {
                throw new \InvalidArgumentException("Broker adapter required for $mode mode");
            }
        }

        // Validate capital
        if (isset($config['capital']) && $config['capital'] <= 0) {
            throw new \InvalidArgumentException('Capital must be positive');
        }
    }

    /**
     * Store deployment record in database.
     *
     * @param  string  $deploymentId  Deployment ID.
     * @param  Strategy  $strategy  Strategy instance.
     * @param  string  $mode  Execution mode.
     * @param  array  $config  Deployment configuration.
     */
    private function storeDeployment(string $deploymentId, Strategy $strategy, string $mode, array $config): void
    {
        $this->database->query(
            'INSERT INTO strategy_deployments (deployment_id, strategy_name, mode, config, status, deployed_at) 
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $deploymentId,
                $strategy->getName(),
                $mode,
                json_encode($config),
                'deployed',
                date('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * Update deployment status in database.
     *
     * @param  string  $deploymentId  Deployment ID.
     * @param  string  $status  New status.
     */
    private function updateDeploymentStatus(string $deploymentId, string $status): void
    {
        $this->database->query(
            'UPDATE strategy_deployments SET status = ?, updated_at = ? WHERE deployment_id = ?',
            [$status, date('Y-m-d H:i:s'), $deploymentId]
        );
    }

    /**
     * Archive deployment data.
     *
     * @param  string  $deploymentId  Deployment ID.
     */
    private function archiveDeployment(string $deploymentId): void
    {
        $deployment = $this->deployedStrategies[$deploymentId];

        // Store final performance metrics
        $this->database->query(
            'UPDATE strategy_deployments 
             SET status = ?, performance_data = ?, archived_at = ? 
             WHERE deployment_id = ?',
            [
                'archived',
                json_encode($deployment['runner']->getPerformanceMetrics()),
                date('Y-m-d H:i:s'),
                $deploymentId,
            ]
        );
    }
}
