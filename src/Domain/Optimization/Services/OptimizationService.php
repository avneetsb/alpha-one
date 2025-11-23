<?php

namespace TradingPlatform\Domain\Optimization\Services;

use TradingPlatform\Domain\Backtesting\Services\BacktestSimulator;
use TradingPlatform\Domain\Optimization\Models\OptimizationRun;
use TradingPlatform\Domain\Strategy\Services\StrategyFactory;

/**
 * Class OptimizationService
 *
 * Manages the execution of strategy optimization jobs.
 */
class OptimizationService
{
    /**
     * @var BacktestSimulator The backtest simulator.
     */
    private BacktestSimulator $backtester;

    /**
     * @var StrategyFactory The strategy factory.
     */
    private StrategyFactory $strategyFactory;

    /**
     * OptimizationService constructor.
     */
    public function __construct(BacktestSimulator $backtester, StrategyFactory $strategyFactory)
    {
        $this->backtester = $backtester;
        $this->strategyFactory = $strategyFactory;
    }

    /**
     * Start a new optimization job.
     *
     * @param  string  $strategyName  The name of the strategy to optimize.
     * @param  string  $algorithm  The optimization algorithm ('grid', 'random').
     * @param  array  $parameterSpace  The parameter space to search.
     * @return OptimizationRun The created optimization run model.
     */
    public function startOptimization(string $strategyName, string $algorithm, array $parameterSpace): OptimizationRun
    {
        $job = OptimizationRun::create([
            'strategy_name' => $strategyName,
            'algorithm' => $algorithm,
            'optimization_config' => ['parameter_space' => $parameterSpace], // Adapted to new schema
            'status' => 'pending',
            'started_at' => now(),
        ]);

        // dispatch(new RunOptimizationJob($job->id));

        return $job;
    }

    /**
     * Execute an optimization job.
     *
     * @param  OptimizationRun  $job  The optimization run model.
     */
    public function runJob(OptimizationRun $job): void
    {
        $job->update(['status' => 'running']);

        try {
            $results = match ($job->algorithm) {
                'grid' => $this->runGridSearch($job),
                'random' => $this->runRandomSearch($job),
                default => throw new \InvalidArgumentException("Unknown algorithm: {$job->algorithm}"),
            };

            $job->update([
                'status' => 'completed',
                'best_fitness' => $results['best_score'] ?? 0, // Adapted
                'completed_at' => now(),
            ]);
        } catch (\Exception $e) {
            $job->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Run a grid search optimization.
     *
     * @return array Best result found.
     */
    private function runGridSearch(OptimizationRun $job): array
    {
        $space = $job->optimization_config['parameter_space'];
        $combinations = $this->generateCombinations($space);
        $bestResult = null;
        $bestMetric = -INF;

        foreach ($combinations as $params) {
            $metric = $this->evaluateParams($job->strategy_name, $params);

            if ($metric > $bestMetric) {
                $bestMetric = $metric;
                $bestResult = ['params' => $params, 'metric' => $metric];
            }
        }

        return $bestResult;
    }

    /**
     * Run a random search optimization.
     *
     * @return array Best result found.
     */
    private function runRandomSearch(OptimizationRun $job): array
    {
        $space = $job->optimization_config['parameter_space'];
        $iterations = 100; // Configurable
        $bestResult = null;
        $bestMetric = -INF;

        for ($i = 0; $i < $iterations; $i++) {
            $params = $this->randomParams($space);
            $metric = $this->evaluateParams($job->strategy_name, $params);

            if ($metric > $bestMetric) {
                $bestMetric = $metric;
                $bestResult = ['params' => $params, 'metric' => $metric];
            }
        }

        return $bestResult;
    }

    /**
     * Evaluate a set of parameters for a strategy.
     *
     * @return float The performance metric (e.g., Sharpe Ratio).
     */
    private function evaluateParams(string $strategyName, array $params): float
    {
        // Mock evaluation
        // In real system:
        // 1. Create strategy instance with params
        // 2. Run backtest
        // 3. Return Sharpe Ratio or other metric
        return rand(0, 100) / 10.0; // Mock Sharpe Ratio 0.0 - 10.0
    }

    /**
     * Generate all combinations of parameters for grid search.
     */
    private function generateCombinations(array $arrays): array
    {
        $result = [[]];
        foreach ($arrays as $property => $property_values) {
            $tmp = [];
            foreach ($result as $result_item) {
                foreach ($property_values as $property_value) {
                    $tmp[] = array_merge($result_item, [$property => $property_value]);
                }
            }
            $result = $tmp;
        }

        return $result;
    }

    /**
     * Generate random parameters from the space.
     */
    private function randomParams(array $space): array
    {
        $params = [];
        foreach ($space as $key => $values) {
            $params[$key] = $values[array_rand($values)];
        }

        return $params;
    }
}
