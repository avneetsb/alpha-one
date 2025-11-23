<?php

namespace TradingPlatform\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Domain\Strategy\Models\StrategyConfiguration;

/**
 * Class CompareStrategyConfigsCommand
 *
 * Compares performance metrics of different strategy configurations.
 * Allows filtering by strategy, favorites, and sorting by key metrics
 * like Sharpe ratio, total return, or max drawdown.
 */
class CompareStrategyConfigsCommand extends Command
{
    /**
     * @var string The default name of the command.
     */
    protected static $defaultName = 'strategy:compare';

    /**
     * Configure the command options and description.
     *
     * Defines options for:
     * - Strategy ID filtering
     * - Favorites only filtering
     * - Result limit
     * - Sorting criteria
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Compare strategy configurations')
            ->setHelp('Compare performance metrics of different strategy configurations')
            ->addOption('strategy', 's', InputOption::VALUE_OPTIONAL, 'Filter by strategy ID')
            ->addOption('favorites', 'f', InputOption::VALUE_NONE, 'Show only favorites')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit number of results', 10)
            ->addOption('sort', null, InputOption::VALUE_OPTIONAL, 'Sort by metric (sharpe, return, drawdown)', 'sharpe');
    }

    /**
     * Execute the console command.
     *
     * Fetches strategy configurations, retrieves their best backtest results,
     * sorts them based on the specified metric, and displays a comparison table.
     *
     * @param  InputInterface  $input  The input interface.
     * @param  OutputInterface  $output  The output interface.
     * @return int Command exit code.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $strategyId = $input->getOption('strategy');
        $favorites = $input->getOption('favorites');
        $limit = (int) $input->getOption('limit');
        $sortBy = $input->getOption('sort');

        $query = StrategyConfiguration::with(['backtestResults', 'strategy']);

        if ($strategyId) {
            $query->where('strategy_id', $strategyId);
        }

        if ($favorites) {
            $query->favorites();
        }

        $configs = $query->get();

        if ($configs->isEmpty()) {
            $output->writeln('<comment>No configurations found.</comment>');

            return Command::SUCCESS;
        }

        // Get best backtest for each config and sort
        $results = [];
        foreach ($configs as $config) {
            $bestBacktest = $config->getBestBacktestResult();
            if ($bestBacktest) {
                $results[] = [
                    'config' => $config,
                    'backtest' => $bestBacktest,
                ];
            }
        }

        // Sort by selected metric
        usort($results, function ($a, $b) use ($sortBy) {
            $metricMap = [
                'sharpe' => 'sharpe_ratio',
                'return' => 'total_return',
                'drawdown' => 'max_drawdown',
            ];

            $metric = $metricMap[$sortBy] ?? 'sharpe_ratio';
            $reverse = $sortBy === 'drawdown'; // Lower drawdown is better

            $aVal = $a['backtest']->{$metric} ?? 0;
            $bVal = $b['backtest']->{$metric} ?? 0;

            return $reverse ? $aVal <=> $bVal : $bVal <=> $aVal;
        });

        $results = array_slice($results, 0, $limit);

        $table = new Table($output);
        $table->setHeaders([
            'ID',
            'Strategy',
            'Config Name',
            'Sharpe',
            'Return %',
            'Drawdown %',
            'Profit Factor',
            'Win Rate %',
            'Trades',
        ]);

        foreach ($results as $item) {
            $config = $item['config'];
            $bt = $item['backtest'];

            $table->addRow([
                $config->id,
                substr($config->strategy->name ?? 'Unknown', 0, 15),
                substr($config->name, 0, 20),
                number_format($bt->sharpe_ratio ?? 0, 2),
                number_format($bt->total_return ?? 0, 2),
                number_format($bt->max_drawdown ?? 0, 2),
                number_format($bt->profit_factor ?? 0, 2),
                number_format($bt->win_rate ?? 0, 1),
                $bt->total_trades,
            ]);
        }

        $output->writeln("\n<info>=== Strategy Configuration Comparison ===</info>");
        $output->writeln("Sorted by: {$sortBy}");
        $table->render();

        return Command::SUCCESS;
    }
}
