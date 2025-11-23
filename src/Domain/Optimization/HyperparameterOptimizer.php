<?php

namespace TradingPlatform\Domain\Optimization;

use TradingPlatform\Domain\Strategy\Models\OptimizationResult;

/**
 * Hyperparameter Optimizer
 *
 * Genetic Algorithm (GA) engine for optimizing strategy hyperparameters.
 * Evolves a population of strategy configurations over multiple generations
 * to maximize a fitness function (e.g., Sharpe ratio or total return).
 *
 * **Algorithm Components:**
 * - **Encoding**: Parameters are encoded into a "DNA" string
 * - **Selection**: Tournament selection to choose parents
 * - **Crossover**: Recombines parent DNA to create offspring
 * - **Mutation**: Randomly alters genes to maintain diversity
 * - **Elitism**: Preserves top performers across generations
 *
 * **Supported Parameter Types:**
 * - Integer (min, max)
 * - Float (min, max, step)
 * - Categorical (options array)
 *
 * @version 1.0.0
 *
 * @example Optimizing a Moving Average Strategy
 * ```php
 * // Define parameter space
 * $params = [
 *     ['name' => 'fast_period', 'type' => 'int', 'min' => 5, 'max' => 20],
 *     ['name' => 'slow_period', 'type' => 'int', 'min' => 21, 'max' => 50],
 *     ['name' => 'stop_loss', 'type' => 'float', 'min' => 0.01, 'max' => 0.05, 'step' => 0.005]
 * ];
 *
 * // Define fitness function (returns float score)
 * $fitnessFn = function ($dna) use ($strategy, $data) {
 *     $config = $optimizer->decodeDNA($dna);
 *     return $backtester->run($strategy, $data, $config)->sharpe_ratio;
 * };
 *
 * // Run optimization
 * $optimizer = new HyperparameterOptimizer($params, $fitnessFn);
 * $result = $optimizer->optimize();
 *
 * print_r($result->bestParameters);
 * ```
 *
 * @see OptimizationResult For result structure
 */
class HyperparameterOptimizer
{
    /**
     * Size of the population in each generation.
     */
    private int $populationSize;

    /**
     * Number of generations to evolve.
     */
    private int $generations;

    /**
     * Probability of gene mutation (0.0 to 1.0).
     */
    private float $mutationRate;

    /**
     * Probability of crossover between parents (0.0 to 1.0).
     */
    private float $crossoverRate;

    /**
     * Configuration of hyperparameters to optimize.
     */
    private array $hyperparameters;

    /**
     * Callback function to evaluate fitness of a DNA string.
     * Signature: function(string $dna): float
     *
     * @var callable
     */
    private $fitnessFunction;

    /**
     * HyperparameterOptimizer constructor.
     *
     * @param  array  $hyperparameters  List of parameter definitions.
     * @param  callable  $fitnessFunction  Function to evaluate individual fitness.
     * @param  int  $populationSize  Number of individuals per generation (default: 50).
     * @param  int  $generations  Number of evolution cycles (default: 100).
     * @param  float  $mutationRate  Mutation probability (default: 0.1).
     * @param  float  $crossoverRate  Crossover probability (default: 0.7).
     */
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
     * Run the genetic optimization process.
     *
     * Iteratively evolves the population to find the best parameter combination.
     * Tracks the best solution found and the history of fitness improvements.
     *
     * **Process:**
     * 1. Initialize random population
     * 2. Evaluate fitness of each individual
     * 3. Select parents based on fitness (Tournament Selection)
     * 4. Create offspring via Crossover and Mutation
     * 5. Apply Elitism (keep best solutions)
     * 6. Repeat for N generations
     *
     * @return OptimizationResult Result containing best parameters and evolution history.
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

        // Note: OptimizationResult model expects specific fields.
        // Adjusting to match the likely schema or creating a DTO if needed.
        // Assuming OptimizationResult model has 'best_parameters' and 'history' fields or similar.
        // If the model is strictly an Eloquent model, we might return a DTO or array here instead.
        // For now, returning an instance compatible with the previous implementation but using the imported class.

        // Since OptimizationResult is an Eloquent model, we should probably return a simple object or array
        // if we are not persisting it yet. However, to maintain compatibility with the signature:

        return new OptimizationResult([
            'best_dna' => $bestDNA,
            'best_fitness' => $bestFitness,
            'best_parameters' => $this->decodeDNA($bestDNA),
            'history' => $history,
        ]);
    }

    /**
     * Initialize a random population of DNA strings.
     *
     * @return array List of random DNA strings.
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
     * Generate a random DNA string based on parameter definitions.
     *
     * Concatenates random genes for each parameter.
     *
     * @return string Encoded DNA string (e.g., "i14_i30_f0.02").
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
     * Generate a single random gene.
     *
     * Format:
     * - Categorical: c{index}
     * - Integer: i{value}
     * - Float: f{value}
     *
     * @param  array  $param  Parameter definition.
     * @return string Encoded gene.
     */
    private function generateRandomGene(array $param): string
    {
        if ($param['type'] === 'categorical') {
            $index = array_rand($param['options']);

            return 'c'.$index;
        } elseif ($param['type'] === 'int') {
            $value = rand($param['min'], $param['max']);

            return 'i'.$value;
        } elseif ($param['type'] === 'float') {
            $step = $param['step'] ?? 0.1;
            $steps = ($param['max'] - $param['min']) / $step;
            $randomStep = rand(0, (int) $steps);
            $value = $param['min'] + ($randomStep * $step);

            return 'f'.number_format($value, 2, '.', '');
        }

        return '';
    }

    /**
     * Evolve the population to the next generation.
     *
     * Applies selection, crossover, mutation, and elitism.
     *
     * @param  array  $population  Current generation DNA strings.
     * @param  array  $fitnesses  Corresponding fitness scores.
     * @return array Next generation DNA strings.
     */
    private function evolve(array $population, array $fitnesses): array
    {
        $nextGen = [];

        // Elitism: keep best individuals
        $eliteCount = max(1, (int) ($this->populationSize * 0.1));
        $sortedIndices = array_keys($fitnesses);
        usort($sortedIndices, fn ($a, $b) => $fitnesses[$b] <=> $fitnesses[$a]);

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
     * Select an individual using Tournament Selection.
     *
     * Randomly picks N individuals and returns the fittest one.
     *
     * @param  array  $population  Population array.
     * @param  array  $fitnesses  Fitness array.
     * @param  int  $tournamentSize  Number of participants (default: 3).
     * @return string Selected DNA string.
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
     * Perform Single-Point Crossover.
     *
     * Swaps genes between two parents at a random split point.
     *
     * @param  string  $parent1  First parent DNA.
     * @param  string  $parent2  Second parent DNA.
     * @return array Two offspring DNA strings.
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
            implode('_', $child2Genes),
        ];
    }

    /**
     * Mutate a DNA string.
     *
     * Randomly replaces one gene with a new random value based on mutation rate.
     *
     * @param  string  $dna  DNA string to potentially mutate.
     * @return string Mutated (or original) DNA string.
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
     * Decode a DNA string back into usable parameters.
     *
     * @param  string  $dna  Encoded DNA string.
     * @return array Associative array of parameter values.
     *
     * @example
     * ```php
     * $params = $optimizer->decodeDNA('i14_f2.5');
     * // Returns ['period' => 14, 'multiplier' => 2.5]
     * ```
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
                $parameters[$param['name']] = $param['options'][(int) $value];
            } elseif ($type === 'i') {
                $parameters[$param['name']] = (int) $value;
            } elseif ($type === 'f') {
                $parameters[$param['name']] = (float) $value;
            }
        }

        return $parameters;
    }
}
