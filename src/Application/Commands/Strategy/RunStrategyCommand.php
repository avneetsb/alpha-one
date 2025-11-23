<?php

namespace TradingPlatform\Application\Commands\Strategy;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TradingPlatform\Domain\Strategy\StrategyRunner;
use TradingPlatform\Infrastructure\Broker\Dhan\DhanOrderAdapter;

/**
 * Command: Run Strategy
 *
 * Deploys and executes a trading strategy in a specified mode (Paper, Sandbox, Live).
 * Manages the strategy lifecycle, risk limits, and broker connections.
 */
class RunStrategyCommand extends Command
{
    protected static $defaultName = 'cli:strategy:run';

    /**
     * Configure the command options.
     *
     * Defines arguments for the strategy class and options for execution mode,
     * capital, broker, risk limits, and duration.
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Run a trading strategy in paper, sandbox, or live mode')
            ->addArgument('strategyClass', InputArgument::REQUIRED, 'Fully-qualified strategy class name')
            ->addOption('mode', 'm', InputOption::VALUE_REQUIRED, 'Execution mode (paper|sandbox|live)', 'paper')
            ->addOption('capital', 'c', InputOption::VALUE_OPTIONAL, 'Initial capital for paper/sandbox mode', 100000)
            ->addOption('broker', 'b', InputOption::VALUE_OPTIONAL, 'Broker identifier', 'dhan')
            ->addOption('duration', 'd', InputOption::VALUE_OPTIONAL, 'Run duration in seconds (0 = indefinite)', 0)
            ->addOption('instruments', 'i', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Instruments to trade')
            ->addOption('max-trades', null, InputOption::VALUE_OPTIONAL, 'Maximum daily trades', 100)
            ->addOption('max-loss', null, InputOption::VALUE_OPTIONAL, 'Maximum daily loss', 10000)
            ->addOption('max-position', null, InputOption::VALUE_OPTIONAL, 'Maximum position size', 1000)
            ->setHelp(
                "Usage:\n".
                "  Paper Trading:\n".
                "    php bin/console cli:strategy:run \"App\\Domain\\Strategy\\Strategies\\TestStrategy\" \\\n".
                "        --mode=paper --capital=100000 --instruments=RELIANCE --instruments=TCS\n\n".
                "  Sandbox Trading:\n".
                "    php bin/console cli:strategy:run \"App\\Domain\\Strategy\\Strategies\\TestStrategy\" \\\n".
                "        --mode=sandbox --broker=dhan --capital=50000\n\n".
                "  Live Trading:\n".
                "    php bin/console cli:strategy:run \"App\\Domain\\Strategy\\Strategies\\TestStrategy\" \\\n".
                "        --mode=live --broker=dhan --max-trades=50 --max-loss=5000\n\n".
                "Options:\n".
                "  strategyClass      Required. Fully-qualified strategy class name.\n".
                "  --mode, -m         Execution mode: paper, sandbox, or live (default: paper).\n".
                "  --capital, -c      Initial capital for paper/sandbox mode (default: 100000).\n".
                "  --broker, -b       Broker identifier (default: dhan).\n".
                "  --duration, -d     Run duration in seconds, 0 for indefinite (default: 0).\n".
                "  --instruments, -i  Instruments to subscribe to (repeatable).\n".
                "  --max-trades       Maximum daily trades (default: 100).\n".
                "  --max-loss         Maximum daily loss (default: 10000).\n".
                "  --max-position     Maximum position size (default: 1000).\n\n".
                "Behavior:\n".
                "  - PAPER mode: Simulates trading with virtual money, no broker interaction.\n".
                "  - SANDBOX mode: Uses broker's test environment with test money.\n".
                "  - LIVE mode: Real trading with real money (requires confirmation).\n"
            );
    }

    /**
     * Execute the strategy runner.
     *
     * Instantiates the strategy, configures the runner with the selected mode and limits,
     * and starts the execution loop. Handles live trading confirmation.
     *
     * @param  InputInterface  $input  Command input.
     * @param  OutputInterface  $output  Command output.
     * @return int Command exit code.
     *
     * @example Run paper trading
     * ```bash
     * php bin/console cli:strategy:run App\Strategies\MyStrategy --mode=paper --instruments=NIFTY
     * ```
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $strategyClass = $input->getArgument('strategyClass');
        $mode = $input->getOption('mode');
        $capital = (float) $input->getOption('capital');
        $broker = $input->getOption('broker');
        $duration = (int) $input->getOption('duration');
        $instruments = $input->getOption('instruments');

        // Validate strategy class
        if (! class_exists($strategyClass)) {
            $output->writeln("<error>Strategy class not found: $strategyClass</error>");

            return Command::FAILURE;
        }

        // Safety confirmation for live mode
        if ($mode === StrategyRunner::MODE_LIVE) {
            $output->writeln('<error>⚠️  WARNING: LIVE TRADING MODE ⚠️</error>');
            $output->writeln('<error>This will execute REAL trades with REAL money!</error>');
            $output->writeln('');
            $output->write('Type "CONFIRM LIVE TRADING" to proceed: ');

            $handle = fopen('php://stdin', 'r');
            $confirmation = trim(fgets($handle));
            fclose($handle);

            if ($confirmation !== 'CONFIRM LIVE TRADING') {
                $output->writeln('<info>Live trading cancelled.</info>');

                return Command::SUCCESS;
            }
        }

        // Create strategy instance
        $strategy = new $strategyClass;

        // Setup broker adapter for sandbox/live modes
        $brokerAdapter = null;
        if (in_array($mode, [StrategyRunner::MODE_SANDBOX, StrategyRunner::MODE_LIVE])) {
            if ($broker === 'dhan') {
                $accessToken = getenv('DHAN_ACCESS_TOKEN');
                if (! $accessToken) {
                    $output->writeln('<error>DHAN_ACCESS_TOKEN not set in environment</error>');

                    return Command::FAILURE;
                }
                $brokerAdapter = new DhanOrderAdapter($accessToken);
            } else {
                $output->writeln("<error>Unsupported broker: $broker</error>");

                return Command::FAILURE;
            }
        }

        // Setup risk limits
        $riskLimits = [
            'max_daily_trades' => (int) $input->getOption('max-trades'),
            'max_daily_loss' => (float) $input->getOption('max-loss'),
            'max_position_size' => (float) $input->getOption('max-position'),
        ];

        // Create strategy runner
        $runner = new StrategyRunner(
            $strategy,
            $mode,
            $capital,
            $brokerAdapter,
            $riskLimits
        );

        // Display startup information
        $output->writeln('<info>=== Strategy Runner Started ===</info>');
        $output->writeln("Strategy: {$strategy->getName()}");
        $output->writeln("Mode: <comment>$mode</comment>");
        $output->writeln('Initial Capital: ₹'.number_format($capital, 2));
        $output->writeln('Risk Limits:');
        $output->writeln("  - Max Daily Trades: {$riskLimits['max_daily_trades']}");
        $output->writeln('  - Max Daily Loss: ₹'.number_format($riskLimits['max_daily_loss'], 2));
        $output->writeln("  - Max Position Size: {$riskLimits['max_position_size']}");
        $output->writeln('');

        // Subscribe to instruments
        if (! empty($instruments)) {
            $output->writeln('Subscribing to instruments: '.implode(', ', $instruments));
            // In production, this would subscribe to market data feed
        }

        // Start the runner
        $runner->start();

        // Simulate market data feed (in production, this would be real-time data)
        $output->writeln('<info>Strategy is now running...</info>');
        $output->writeln('Press Ctrl+C to stop');
        $output->writeln('');

        $startTime = time();
        $tickCount = 0;

        try {
            while (true) {
                // Check duration limit
                if ($duration > 0 && (time() - $startTime) >= $duration) {
                    $output->writeln('<info>Duration limit reached. Stopping...</info>');
                    break;
                }

                // In production, this would receive real ticks from market data feed
                // For demo, we'll simulate with a delay
                sleep(1);

                // Display periodic status
                $tickCount++;
                if ($tickCount % 10 === 0) {
                    $this->displayStatus($output, $runner);
                }

                // Check for Ctrl+C (in production, use signal handlers)
                // For now, we'll just run for the specified duration or indefinitely
            }
        } catch (\Exception $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
            $runner->stop();

            return Command::FAILURE;
        }

        // Stop the runner
        $runner->stop();

        // Display final statistics
        $this->displayFinalStats($output, $runner);

        return Command::SUCCESS;
    }

    /**
     * Display current status.
     *
     * @param  OutputInterface  $output  Command output.
     * @param  StrategyRunner  $runner  Strategy runner instance.
     */
    private function displayStatus(OutputInterface $output, StrategyRunner $runner): void
    {
        $metrics = $runner->getPerformanceMetrics();

        $output->writeln('--- Status Update ---');
        $output->writeln('Mode: '.$metrics['mode']);
        $output->writeln('Total Trades: '.$metrics['total_trades']);

        if ($metrics['mode'] === StrategyRunner::MODE_PAPER) {
            $output->writeln('Current Value: ₹'.number_format($metrics['current_value'], 2));
            $output->writeln('Cash: ₹'.number_format($metrics['cash'], 2));
            $output->writeln('P&L: ₹'.number_format($metrics['total_pnl'], 2).' ('.round($metrics['return_pct'], 2).'%)');
            $output->writeln('Open Positions: '.$metrics['positions']);
        }

        $output->writeln('');
    }

    /**
     * Display final statistics.
     *
     * @param  OutputInterface  $output  Command output.
     * @param  StrategyRunner  $runner  Strategy runner instance.
     */
    private function displayFinalStats(OutputInterface $output, StrategyRunner $runner): void
    {
        $output->writeln('');
        $output->writeln('<info>=== Final Statistics ===</info>');

        $metrics = $runner->getPerformanceMetrics();
        $executionLog = $runner->getExecutionLog();

        $output->writeln("Total Trades: {$metrics['total_trades']}");

        if ($metrics['mode'] === StrategyRunner::MODE_PAPER) {
            $output->writeln('Initial Capital: ₹'.number_format($metrics['initial_capital'], 2));
            $output->writeln('Final Value: ₹'.number_format($metrics['current_value'], 2));
            $output->writeln('Total P&L: ₹'.number_format($metrics['total_pnl'], 2));
            $output->writeln('Return: '.round($metrics['return_pct'], 2).'%');
        }

        // Display recent trades
        if (! empty($executionLog)) {
            $output->writeln('');
            $output->writeln('Recent Trades:');
            $recentTrades = array_slice($executionLog, -5);
            foreach ($recentTrades as $trade) {
                $output->writeln(sprintf(
                    '  %s %s %s @ ₹%.2f [%s]',
                    date('H:i:s', $trade['timestamp']),
                    $trade['action'],
                    $trade['instrument_id'],
                    $trade['price'],
                    $trade['status']
                ));
            }
        }
    }
}
