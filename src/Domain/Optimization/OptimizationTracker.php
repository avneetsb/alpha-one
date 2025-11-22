<?php

namespace TradingPlatform\Domain\Optimization;

use TradingPlatform\Domain\Strategy\Models\{
    Strategy,
    StrategyConfiguration,
    OptimizationRun,
    OptimizationResult as OptimizationResultModel,
    BacktestResult
};

/**
 * Optimization Tracking Service - Persists optimization runs and results
 */
class OptimizationTracker
{
    private ?OptimizationRun $currentRun = null;
    private ?Strategy $strategy = null;

    /**
     * Start a new optimization run
     */
    public function startRun(
        Strategy $strategy,
        string $name,
        array $optimizationConfig,
        array $fitnessObjectives,
        array $backtestConfig,
        string $dataSource,
        string $periodStart,
        string $periodEnd
    ): OptimizationRun {
        $this->strategy = $strategy;
        
        $this->currentRun = OptimizationRun::create([
            'strategy_id' => $strategy->id,
            'name' => $name,
            'status' => 'pending',
            'algorithm' => 'genetic',
            'optimization_config' => $optimizationConfig,
            'fitness_objectives' => $fitnessObjectives,
            'backtest_config' => $backtestConfig,
            'data_source' => $dataSource,
            'data_period_start' => $periodStart,
            'data_period_end' => $periodEnd,
            'total_generations' => $optimizationConfig['generations'] ?? 100,
        ]);

        $this->currentRun->markAsStarted();

        return $this->currentRun;
    }

    /**
     * Save a generation's results
     */
    public function saveGenerationResults(
        int $generation,
        array $population,
        array $fitnesses,
        array $backtestResults,
        callable $decodeDNA
    ): void {
        if (!$this->currentRun) {
            throw new \Exception('No active optimization run');
        }

        // Rank individuals
        $rankedIndices = array_keys($fitnesses);
        usort($rankedIndices, fn($a, $b) => $fitnesses[$b] <=> $fitnesses[$a]);

        $eliteCount = max(1, (int)(count($population) * 0.1));

        foreach ($population as $index => $dna) {
            // Create or find configuration
            $hyperparameters = $decodeDNA($dna);
            
            $config = StrategyConfiguration::create([
                'strategy_id' => $this->strategy->id,
                'name' => "Gen{$generation}_Ind{$index}",
                'hyperparameters' => $hyperparameters,
                'dna' => $dna,
                'source' => 'optimization',
                'optimization_run_id' => $this->currentRun->id,
            ]);

            // Save backtest result if available
            $backtestResultId = null;
            if (isset($backtestResults[$index])) {
                $backtest = BacktestResult::create([
                    'strategy_config_id' => $config->id,
                    ...$backtestResults[$index]
                ]);
                $backtestResultId = $backtest->id;
            }

            // Get rank
            $rank = array_search($index, $rankedIndices) + 1;
            $isElite = $rank <= $eliteCount;

            // Save optimization result
            OptimizationResultModel::create([
                'optimization_run_id' => $this->currentRun->id,
                'strategy_config_id' => $config->id,
                'generation' => $generation,
                'individual_index' => $index,
                'dna' => $dna,
                'hyperparameters' => $hyperparameters,
                'fitness_score' => $fitnesses[$index],
                'fitness_components' => ['raw_fitness' => $fitnesses[$index]],
                'backtest_result_id' => $backtestResultId,
                'rank_in_generation' => $rank,
                'is_elite' => $isElite,
                'evaluated_at' => now(),
            ]);
        }

        // Update run progress
        $this->currentRun->updateProgress($generation, count($population) * ($generation + 1));
    }

    /**
     * Complete the optimization run
     */
    public function completeRun(string $bestDNA, float $bestFitness): void
    {
        if (!$this->currentRun) {
            throw new \Exception('No active optimization run');
        }

        // Find best configuration
        $bestConfig = StrategyConfiguration::where('dna', $bestDNA)
            ->where('optimization_run_id', $this->currentRun->id)
            ->first();

        $this->currentRun->markAsCompleted($bestConfig?->id, $bestFitness);
        $this->currentRun = null;
    }

    /**
     * Mark run as failed
     */
    public function failRun(string $errorMessage): void
    {
        if ($this->currentRun) {
            $this->currentRun->markAsFailed($errorMessage);
            $this->currentRun = null;
        }
    }

    /**
     * Get current run
     */
    public function getCurrentRun(): ?OptimizationRun
    {
        return $this->currentRun;
    }
}
