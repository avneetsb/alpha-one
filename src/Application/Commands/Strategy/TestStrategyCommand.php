<?php

namespace TradingPlatform\Application\Commands\Strategy;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Application\Engine\StrategyEngine;
use TradingPlatform\Domain\Strategy\Strategies\TestStrategy;
use TradingPlatform\Domain\MarketData\Tick;

class TestStrategyCommand extends Command
{
    protected static $defaultName = 'cli:strategy:test';

    protected function configure()
    {
        $this->setDescription('Test the Strategy Engine with a dummy strategy')
            ->setHelp(
                "Usage:\n" .
                "  php bin/console cli:strategy:test\n\n" .
                "Behavior:\n" .
                "  Registers a demo strategy, feeds a dummy tick, and logs generated signals.\n"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $engine = new StrategyEngine();
        $strategy = new TestStrategy('TestStrategy');
        $engine->registerStrategy($strategy);

        $output->writeln("Feeding tick with price 101...");
        
        // Create a dummy tick
        // Tick model expects attributes.
        $tick = new Tick([
            'instrument_id' => 1,
            'price' => 101.0,
            'volume' => 100,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        $engine->processTick($tick);

        $output->writeln("Check logs for signal generation.");
        return Command::SUCCESS;
    }
}
