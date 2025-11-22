<?php

namespace TradingPlatform\Domain\Optimization\Services;

use TradingPlatform\Domain\Optimization\Models\OptimizationRun;
use TradingPlatform\Domain\Backtesting\Services\BacktestSimulator;
use TradingPlatform\Domain\Strategy\Services\StrategyFactory;

class OptimizationService
{
    private BacktestSimulator $backtester;
    private StrategyFactory $strategyFactory;

    public function __construct(BacktestSimulator $backtester, StrategyFactory $strategyFactory)
    {
        $this->backtester = $backtester;
        $this->strategyFactory = $strategyFactory;
    }

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

    private function evaluateParams(string $strategyName, array $params): float
    {
        // Mock evaluation
        // In real system:
        // 1. Create strategy instance with params
        // 2. Run backtest
        // 3. Return Sharpe Ratio or other metric
        return rand(0, 100) / 10.0; // Mock Sharpe Ratio 0.0 - 10.0
    }

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

    private function randomParams(array $space): array
    {
        $params = [];
        foreach ($space as $key => $values) {
            $params[$key] = $values[array_rand($values)];
        }
        return $params;
    }
}
