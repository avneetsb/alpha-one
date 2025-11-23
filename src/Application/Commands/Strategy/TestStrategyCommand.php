<?php

namespace TradingPlatform\Application\Commands\Strategy;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Application\Engine\StrategyEngine;
use TradingPlatform\Domain\MarketData\Tick;
use TradingPlatform\Domain\Strategy\Strategies\TestStrategy;

/**
 * Command: Test Strategy Engine
 *
 * A diagnostic command to verify the Strategy Engine's functionality.
 * Registers a dummy strategy and feeds it a mock tick to ensure signal generation works.
 */
class TestStrategyCommand extends Command
{
    protected static $defaultName = 'cli:strategy:test';

    /**
     * Configure the command.
     *
     * @return void
     */
    /**
     * Configure the command.
     *
     * Sets up the command signature and help text.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Test the Strategy Engine with a dummy strategy')
            ->setHelp(
                "Usage:\n".
                "  php bin/console cli:strategy:test\n\n".
                "Behavior:\n".
                "  Registers a demo strategy, feeds a dummy tick, and logs generated signals.\n".
                "  Useful for sanity checking the engine pipeline.\n"
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
     * Execute the engine test.
     *
     * Creates a StrategyEngine, registers a TestStrategy, and processes a single tick.
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface  $output  Command output.
     * @return int Command exit code.
     *
     * @example Run test
     * ```bash
     * php bin/console cli:strategy:test
     * ```
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $engine = new StrategyEngine;
        $strategy = new TestStrategy('TestStrategy');
        $engine->registerStrategy($strategy);

        $output->writeln('Feeding tick with price 101...');

        // Create a dummy tick
        // Tick model expects attributes.
        $tick = new Tick([
            'instrument_id' => 1,
            'price' => 101.0,
            'volume' => 100,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);

        $engine->processTick($tick);

        $output->writeln('Check logs for signal generation.');

        return Command::SUCCESS;
    }
}
