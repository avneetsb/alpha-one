<?php

namespace TradingPlatform\Domain\Optimization;

/**
 * Genetic Algorithm-based Hyperparameter Optimization Engine
 */
class HyperparameterOptimizer
{
    private int $populationSize;
    private int $generations;
    private float $mutationRate;
    private float $crossoverRate;
    private array $hyperparameters;
    private $fitnessFunction;

    public function __construct(
        array $hyperparameters,
        callable $fitnessFunction,
        int $populationSize = 50,
        int $generations = 100,
        float $mutationRate = 0.1,
        float $crossoverRate = 0.7
    ) {
        $this->hyperparameters = $hyperparameters;
        $this->fitnessFunction = $fitnessFunction;
        $this->populationSize = $populationSize;
        $this->generations = $generations;
        $this->mutationRate = $mutationRate;
        $this->crossoverRate = $crossoverRate;
    }

    /**
     * Run optimization
     */
    public function optimize(): OptimizationResult
    {
        // Initialize population
        $population = $this->initializePopulation();
        $bestDNA = null;
        $bestFitness = -INF;
        $history = [];

        for ($gen = 0; $gen < $this->generations; $gen++) {
            // Evaluate fitness for all individuals
            $fitnesses = [];
            foreach ($population as $individual) {
                $fitness = ($this->fitnessFunction)($individual);
                $fitnesses[] = $fitness;

                if ($fitness > $bestFitness) {
                    $bestFitness = $fitness;
                    $bestDNA = $individual;
                }
            }

            $history[] = [
                'generation' => $gen,
                'best_fitness' => $bestFitness,
                'avg_fitness' => array_sum($fitnesses) / count($fitnesses),
            ];

            // Create next generation
            $population = $this->evolve($population, $fitnesses);
        }

        return new OptimizationResult([
            'best_dna' => $bestDNA,
            'best_fitness' => $bestFitness,
            'best_parameters' => $this->decodeDNA($bestDNA),
            'history' => $history,
        ]);
    }

    /**
     * Initialize random population
     */
    private function initializePopulation(): array
    {
        $population = [];

        for ($i = 0; $i < $this->populationSize; $i++) {
            $population[] = $this->generateRandomDNA();
        }

        return $population;
    }

    /**
     * Generate random DNA string
     */
    private function generateRandomDNA(): string
    {
        $genes = [];

        foreach ($this->hyperparameters as $param) {
            $genes[] = $this->generateRandomGene($param);
        }

        return implode('_', $genes);
    }

    /**
     * Generate random gene for a parameter
     */
    private function generateRandomGene(array $param): string
    {
        if ($param['type'] === 'categorical') {
            $index = array_rand($param['options']);
            return 'c' . $index;
        } elseif ($param['type'] === 'int') {
            $value = rand($param['min'], $param['max']);
            return 'i' . $value;
        } elseif ($param['type'] === 'float') {
            $step = $param['step'] ?? 0.1;
            $steps = ($param['max'] - $param['min']) / $step;
            $randomStep = rand(0, (int)$steps);
            $value = $param['min'] + ($randomStep * $step);
            return 'f' . number_format($value, 2, '.', '');
        }

        return '';
    }

    /**
     * Evolve population to next generation
     */
    private function evolve(array $population, array $fitnesses): array
    {
        $nextGen = [];

        // Elitism: keep best individuals
        $eliteCount = max(1, (int)($this->populationSize * 0.1));
        $sortedIndices = array_keys($fitnesses);
        usort($sortedIndices, fn($a, $b) => $fitnesses[$b] <=> $fitnesses[$a]);

        for ($i = 0; $i < $eliteCount; $i++) {
            $nextGen[] = $population[$sortedIndices[$i]];
        }

        // Generate rest through selection, crossover, and mutation
        while (count($nextGen) < $this->populationSize) {
            // Tournament selection
            $parent1 = $this->tournamentSelection($population, $fitnesses);
            $parent2 = $this->tournamentSelection($population, $fitnesses);

            // Crossover
            if (rand() / getrandmax() < $this->crossoverRate) {
                [$child1, $child2] = $this->crossover($parent1, $parent2);
            } else {
                $child1 = $parent1;
                $child2 = $parent2;
            }

            // Mutation
            $child1 = $this->mutate($child1);
            $child2 = $this->mutate($child2);

            $nextGen[] = $child1;
            if (count($nextGen) < $this->populationSize) {
                $nextGen[] = $child2;
            }
        }

        return array_slice($nextGen, 0, $this->populationSize);
    }

    /**
     * Tournament selection
     */
    private function tournamentSelection(array $population, array $fitnesses, int $tournamentSize = 3): string
    {
        $best = null;
        $bestFitness = -INF;

        for ($i = 0; $i < $tournamentSize; $i++) {
            $index = array_rand($population);
            if ($fitnesses[$index] > $bestFitness) {
                $bestFitness = $fitnesses[$index];
                $best = $population[$index];
            }
        }

        return $best;
    }

    /**
     * Crossover two DNA strings
     */
    private function crossover(string $parent1, string $parent2): array
    {
        $genes1 = explode('_', $parent1);
        $genes2 = explode('_', $parent2);

        $crossoverPoint = rand(1, count($genes1) - 1);

        $child1Genes = array_merge(
            array_slice($genes1, 0, $crossoverPoint),
            array_slice($genes2, $crossoverPoint)
        );

        $child2Genes = array_merge(
            array_slice($genes2, 0, $crossoverPoint),
            array_slice($genes1, $crossoverPoint)
        );

        return [
            implode('_', $child1Genes),
            implode('_', $child2Genes)
        ];
    }

    /**
     * Mutate DNA string
     */
    private function mutate(string $dna): string
    {
        if (rand() / getrandmax() > $this->mutationRate) {
            return $dna;
        }

        $genes = explode('_', $dna);
        $mutationIndex = array_rand($genes);

        $genes[$mutationIndex] = $this->generateRandomGene($this->hyperparameters[$mutationIndex]);

        return implode('_', $genes);
    }

    /**
     * Decode DNA string to parameters
     */
    public function decodeDNA(string $dna): array
    {
        $genes = explode('_', $dna);
        $parameters = [];

        foreach ($this->hyperparameters as $index => $param) {
            $gene = $genes[$index] ?? '';
            $type = substr($gene, 0, 1);
            $value = substr($gene, 1);

            if ($type === 'c') {
                $parameters[$param['name']] = $param['options'][(int)$value];
            } elseif ($type === 'i') {
                $parameters[$param['name']] = (int)$value;
            } elseif ($type === 'f') {
                $parameters[$param['name']] = (float)$value;
            }
        }

        return $parameters;
    }
}

/**
 * Optimization result
 */
class OptimizationResult
{
    public string $bestDNA;
    public float $bestFitness;
    public array $bestParameters;
    public array $history;

    public function __construct(array $data)
    {
        $this->bestDNA = $data['best_dna'];
        $this->bestFitness = $data['best_fitness'];
        $this->bestParameters = $data['best_parameters'];
        $this->history = $data['history'];
    }

    public function printSummary(): string
    {
        return sprintf(
            "Best DNA: %s\nBest Fitness: %.4f\nParameters: %s\nGenerations: %d",
            $this->bestDNA,
            $this->bestFitness,
            json_encode($this->bestParameters, JSON_PRETTY_PRINT),
            count($this->history)
        );
    }
}
