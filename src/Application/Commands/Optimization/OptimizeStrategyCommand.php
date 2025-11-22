<?php

namespace TradingPlatform\Application\Commands\Optimization;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputArgument, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Domain\Optimization\HyperparameterOptimizer;
use TradingPlatform\Domain\Backtesting\BacktestEngine;
use TradingPlatform\Domain\Strategy\MultiIndicatorStrategy;

/**
 * Hyperparameter optimization command
 */
class OptimizeStrategyCommand extends Command
{
    protected static $defaultName = 'cli:strategy:optimize';

    protected function configure(): void
    {
        $this
            ->setDescription('Optimize strategy hyperparameters using genetic algorithms')
            ->addArgument('strategyClass', InputArgument::REQUIRED, 'Strategy class name')
            ->addOption('data-file', 'd', InputOption::VALUE_REQUIRED, 'Historical data CSV file')
            ->addOption('population', 'p', InputOption::VALUE_OPTIONAL, 'Population size', 50)
            ->addOption('generations', 'g', InputOption::VALUE_OPTIONAL, 'Number of generations', 100)
            ->addOption('initial-capital', 'c', InputOption::VALUE_OPTIONAL, 'Initial capital', 100000)
            ->setHelp(
                "Usage:\n" .
                "  php bin/console cli:strategy:optimize App\\Domain\\Strategy\\Strategies\\TestStrategy \\ \n" .
                "      --data-file=./data/NIFTY_5m.csv -p 50 -g 100 -c 100000\n\n" .
                "Arguments:\n" .
                "  strategyClass     Required. Fully-qualified strategy class name.\n\n" .
                "Options:\n" .
                "  -d, --data-file       Path to historical candles CSV.\n" .
                "  -p, --population      Population size (default 50).\n" .
                "  -g, --generations     Number of generations (default 100).\n" .
                "  -c, --initial-capital Initial capital (default 100000).\n"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $strategyClass = $input->getArgument('strategyClass');
        $dataFile = $input->getOption('data-file');
        $population = (int)$input->getOption('population');
        $generations = (int)$input->getOption('generations');
        $initialCapital = (float)$input->getOption('initial-capital');

        $output->writeln('<info>Starting Hyperparameter Optimization</info>');
        $output->writeln('Strategy: ' . $strategyClass);
        $output->writeln('Population: ' . $population);
        $output->writeln('Generations: ' . $generations);
        $output->writeln('');

        // Load historical data
        $historicalData = $this->loadHistoricalData($dataFile);
        $output->writeln('Loaded ' . count($historicalData) . ' candles');

        // Create strategy instance
        $strategy = new $strategyClass();

        // Get hyperparameters
        $hyperparameters = $strategy->hyperparameters();
        $output->writeln('Optimizing ' . count($hyperparameters) . ' parameters');
        $output->writeln('');

        // Define fitness function
        $fitnessFunction = function($dna) use ($strategy, $historicalData, $initialCapital, $output) {
            // Decode DNA and create temp strategy
            $optimizer = new HyperparameterOptimizer($strategy->hyperparameters(), fn($x) => 0);
            $params = $optimizer->decodeDNA($dna);
            
            // Create strategy with these parameters
            $testStrategy = clone $strategy;
            foreach ($params as $key => $value) {
                $testStrategy->hp[$key] = $value;
            }

            // Run backtest
            $backtester = new BacktestEngine($initialCapital);
            $result = $backtester->run($testStrategy, $historicalData);

            // Multi-objective fitness:
            // - Maximize total return
            // - Minimize number of trades (penalty for overtrading)
            // - Minimize maximum drawdown
            
            $totalReturn = $result->metrics['total_return_percent'];
            $tradePenalty = $result->metrics['total_trades'] > 100 ? -10 : 0; // Penalize > 100 trades
            $drawdownPenalty = $result->metrics['max_drawdown_percent'] * -0.5; // Penalize high drawdown
            
            $fitness = $totalReturn + $tradePenalty + $drawdownPenalty;

            return $fitness;
        };

        // Run optimization
        $optimizer = new HyperparameterOptimizer(
            $hyperparameters,
            $fitnessFunction,
            $population,
            $generations
        );

        $output->writeln('<comment>Running optimization...</comment>');
        $startTime = microtime(true);

        $result = $optimizer->optimize();

        $duration = microtime(true) - $startTime;

        // Display results
        $output->writeln('');
        $output->writeln('<info>Optimization Complete!</info>');
        $output->writeln('Duration: ' . round($duration, 2) . ' seconds');
        $output->writeln('');
        $output->writeln('<comment>Best DNA: ' . $result->bestDNA . '</comment>');
        $output->writeln('Best Fitness: ' . round($result->bestFitness, 4));
        $output->writeln('');
        $output->writeln('Optimized Parameters:');
        foreach ($result->bestParameters as $name => $value) {
            $output->writeln('  ' . $name . ': ' . $value);
        }

        $output->writeln('');
        $output->writeln('<info>To use these parameters, add this to your strategy:</info>');
        $output->writeln('');
        $output->writeln('public function dna(): ?string');
        $output->writeln('{');
        $output->writeln('    return \'' . $result->bestDNA . '\';');
        $output->writeln('}');

        return Command::SUCCESS;
    }

    private function loadHistoricalData(?string $file): array
    {
        // If file provided, load from CSV
        if ($file && file_exists($file)) {
            // CSV parsing logic here
            return [];
        }

        // Generate sample data for demo
        $data = [];
        $price = 100;

        for ($i = 0; $i < 1000; $i++) {
            $change = (rand(-100, 100) / 100);
            $price += $change;
            
            $high = $price + rand(0, 200) / 100;
            $low = $price - rand(0, 200) / 100;
            
            $data[] = [
                'timestamp' => time() + ($i * 3600),
                'open' => $price,
                'high' => $high,
                'low' => $low,
                'close' => $price,
                'volume' => rand(1000, 10000),
            ];
        }

        return $data;
    }
}
