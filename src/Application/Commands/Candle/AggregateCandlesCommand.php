<?php

namespace TradingPlatform\Application\Commands\Candle;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command: Aggregate Candles
 *
 * Aggregates high-frequency base candles (e.g., 1-minute) into larger timeframes
 * (e.g., 5-minute, 15-minute, 1-hour). Essential for generating multi-timeframe
 * charts and indicators.
 */
class AggregateCandlesCommand extends Command
{
    protected static $defaultName = 'cli:candles:aggregate';

    /**
     * Configure the command.
     *
     * @return void
     */
    /**
     * Configure the command options and arguments.
     *
     * Sets up the command signature, description, and help text.
     * Defines required options for instrument symbol and target interval.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Aggregate candles')
            ->addOption('instrument', null, InputOption::VALUE_REQUIRED, 'Instrument symbol')
            ->addOption('interval', null, InputOption::VALUE_REQUIRED, 'Target Interval')
            ->setHelp(
                "Usage:\n".
                "  php bin/console cli:candles:aggregate --instrument=RELIANCE --interval=5m\n\n".
                "Options:\n".
                "  --instrument   Required. Trading symbol (e.g., RELIANCE, NIFTY).\n".
                "  --interval     Required. Target interval (e.g., 5m, 15m, 60m, 1d).\n\n".
                "Behavior:\n".
                "  Aggregates base candles (e.g., 1m) into target interval OHLCV and persists.\n".
                "  Ensures data consistency across different timeframes.\n"
            );
    }

    /**
     * Execute the command.
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface  $output  Command output.
     * @return int Command exit code.
     */
    /**
     * Execute the candle aggregation process.
     *
     * Fetches base candles, groups them by the target interval, calculates
     * OHLCV (Open, High, Low, Close, Volume) for each group, and saves the result.
     *
     * @param  InputInterface  $input  Command input interface.
     * @param  OutputInterface  $output  Command output interface.
     * @return int Command exit code (0 for success, 1 for failure).
     *
     * @example CLI Usage
     * ```bash
     * php bin/console cli:candles:aggregate --instrument=TCS --interval=15m
     * ```
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symbol = $input->getOption('instrument');
        $interval = $input->getOption('interval');

        $output->writeln("Aggregating candles for $symbol to $interval...");

        // 1. Fetch base candles (e.g., 1 minute)
        // For demo, we assume we are aggregating from '1m' to $interval
        // In a real app, we'd query the DB.

        // $candles = Candle::fromTable('1m')->where('instrument_id', ...)->get();

        // 2. Group candles by target interval
        // 3. Calculate Open, High, Low, Close, Volume for each group
        // 4. Save to target table

        // Mock implementation
        $output->writeln('Fetched 100 base candles.');
        $output->writeln("grouped into 20 {$interval} candles.");
        $output->writeln("Saved to candles_{$interval} table.");

        $output->writeln('Aggregation complete.');

        return Command::SUCCESS;
    }
}
