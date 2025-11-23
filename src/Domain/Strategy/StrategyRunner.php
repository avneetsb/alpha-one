<?php

namespace TradingPlatform\Domain\Strategy;

use TradingPlatform\Domain\MarketData\Tick;
use TradingPlatform\Domain\Order\Order;
use TradingPlatform\Domain\Portfolio\Position;
use TradingPlatform\Infrastructure\Broker\BrokerAdapterInterface;
use TradingPlatform\Infrastructure\Logger\LoggerService;

/**
 * Strategy Runner
 *
 * Executes trading strategies in different modes: paper, sandbox, and live.
 * Handles order execution, position tracking, and risk management based on the mode.
 *
 * **Modes:**
 * - PAPER: Simulated trading with virtual money, no real broker interaction
 * - SANDBOX: Broker sandbox/test environment with real API but test money
 * - LIVE: Real trading with real money and real broker execution
 *
 * @version 1.0.0
 */
class StrategyRunner
{
    public const MODE_PAPER = 'paper';

    public const MODE_SANDBOX = 'sandbox';

    public const MODE_LIVE = 'live';

    private Strategy $strategy;

    private string $mode;

    private ?BrokerAdapterInterface $brokerAdapter;

    private array $virtualPositions = [];

    private float $virtualCash;

    private float $initialCapital;

    private array $executionLog = [];

    private $logger;

    private bool $isRunning = false;

    private array $riskLimits = [];

    /**
     * StrategyRunner constructor.
     *
     * @param  Strategy  $strategy  Strategy to run.
     * @param  string  $mode  Execution mode (paper, sandbox, live).
     * @param  float  $initialCapital  Starting capital for paper/sandbox mode.
     * @param  BrokerAdapterInterface|null  $brokerAdapter  Broker adapter for sandbox/live modes.
     * @param  array  $riskLimits  Risk limits configuration.
     */
    public function __construct(
        Strategy $strategy,
        string $mode = self::MODE_PAPER,
        float $initialCapital = 100000,
        ?BrokerAdapterInterface $brokerAdapter = null,
        array $riskLimits = []
    ) {
        $this->strategy = $strategy;
        $this->mode = $mode;
        $this->initialCapital = $initialCapital;
        $this->virtualCash = $initialCapital;
        $this->brokerAdapter = $brokerAdapter;
        $this->logger = LoggerService::getLogger();
        $this->riskLimits = array_merge($this->getDefaultRiskLimits(), $riskLimits);

        $this->validateMode();
        $this->logger->info('Strategy runner initialized', [
            'strategy' => $strategy->getName(),
            'mode' => $mode,
            'capital' => $initialCapital,
        ]);
    }

    /**
     * Start the strategy runner.
     */
    public function start(): void
    {
        if ($this->isRunning) {
            throw new \RuntimeException('Strategy runner is already running');
        }

        $this->isRunning = true;
        $this->logger->info('Strategy runner started', [
            'strategy' => $this->strategy->getName(),
            'mode' => $this->mode,
        ]);
    }

    /**
     * Stop the strategy runner.
     */
    public function stop(): void
    {
        $this->isRunning = false;
        $this->logger->info('Strategy runner stopped', [
            'strategy' => $this->strategy->getName(),
            'mode' => $this->mode,
        ]);
    }

    /**
     * Process a market tick through the strategy.
     *
     * @param  Tick  $tick  Market tick data.
     */
    public function processTick(Tick $tick): void
    {
        if (! $this->isRunning) {
            return;
        }

        try {
            // Pass tick to strategy
            $signal = $this->strategy->onTick($tick);

            if ($signal) {
                $this->executeSignal($signal, $tick);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error processing tick', [
                'error' => $e->getMessage(),
                'strategy' => $this->strategy->getName(),
            ]);
        }
    }

    /**
     * Execute a trading signal based on the current mode.
     *
     * @param  Signal  $signal  Trading signal to execute.
     * @param  Tick  $tick  Current market tick.
     */
    private function executeSignal(Signal $signal, Tick $tick): void
    {
        // Validate against risk limits
        if (! $this->validateRiskLimits($signal)) {
            $this->logger->warning('Signal rejected by risk limits', [
                'signal' => $signal->action,
                'instrument' => $signal->instrumentId,
            ]);

            return;
        }

        switch ($this->mode) {
            case self::MODE_PAPER:
                $this->executePaperTrade($signal, $tick);
                break;

            case self::MODE_SANDBOX:
                $this->executeSandboxTrade($signal, $tick);
                break;

            case self::MODE_LIVE:
                $this->executeLiveTrade($signal, $tick);
                break;
        }
    }

    /**
     * Execute trade in paper mode (simulated).
     *
     * @param  Signal  $signal  Trading signal.
     * @param  Tick  $tick  Current market tick.
     */
    private function executePaperTrade(Signal $signal, Tick $tick): void
    {
        $instrumentId = $signal->instrumentId;
        $quantity = $signal->quantity ?? 1;
        $price = $signal->price ?? $tick->price;

        if ($signal->action === 'BUY') {
            $cost = $quantity * $price;

            if ($this->virtualCash >= $cost) {
                $this->virtualCash -= $cost;

                if (! isset($this->virtualPositions[$instrumentId])) {
                    $this->virtualPositions[$instrumentId] = [
                        'quantity' => 0,
                        'avg_price' => 0,
                        'realized_pnl' => 0,
                    ];
                }

                $position = &$this->virtualPositions[$instrumentId];
                $totalQuantity = $position['quantity'] + $quantity;
                $position['avg_price'] = (($position['quantity'] * $position['avg_price']) + ($quantity * $price)) / $totalQuantity;
                $position['quantity'] = $totalQuantity;

                $this->logExecution('BUY', $instrumentId, $quantity, $price, 'FILLED');
            } else {
                $this->logger->warning('Insufficient virtual cash for paper trade', [
                    'required' => $cost,
                    'available' => $this->virtualCash,
                ]);
            }
        } elseif ($signal->action === 'SELL') {
            if (isset($this->virtualPositions[$instrumentId]) && $this->virtualPositions[$instrumentId]['quantity'] >= $quantity) {
                $position = &$this->virtualPositions[$instrumentId];
                $realizedPnl = ($price - $position['avg_price']) * $quantity;

                $position['quantity'] -= $quantity;
                $position['realized_pnl'] += $realizedPnl;
                $this->virtualCash += $quantity * $price;

                if ($position['quantity'] == 0) {
                    unset($this->virtualPositions[$instrumentId]);
                }

                $this->logExecution('SELL', $instrumentId, $quantity, $price, 'FILLED', $realizedPnl);
            } else {
                $this->logger->warning('Insufficient position for paper sell', [
                    'instrument' => $instrumentId,
                    'required' => $quantity,
                    'available' => $this->virtualPositions[$instrumentId]['quantity'] ?? 0,
                ]);
            }
        }
    }

    /**
     * Execute trade in sandbox mode (broker test environment).
     *
     * @param  Signal  $signal  Trading signal.
     * @param  Tick  $tick  Current market tick.
     */
    private function executeSandboxTrade(Signal $signal, Tick $tick): void
    {
        if (! $this->brokerAdapter) {
            throw new \RuntimeException('Broker adapter required for sandbox mode');
        }

        try {
            $order = $this->createOrderFromSignal($signal, $tick);
            $result = $this->brokerAdapter->placeOrder($order);

            $this->logExecution(
                $signal->action,
                $signal->instrumentId,
                $signal->quantity ?? 1,
                $signal->price ?? $tick->price,
                $result['status'] ?? 'PENDING',
                0,
                $result['orderId'] ?? null
            );

            $this->logger->info('Sandbox order placed', [
                'order_id' => $result['orderId'] ?? null,
                'status' => $result['status'] ?? 'PENDING',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Sandbox order failed', [
                'error' => $e->getMessage(),
                'signal' => $signal->action,
            ]);
        }
    }

    /**
     * Execute trade in live mode (real broker execution).
     *
     * @param  Signal  $signal  Trading signal.
     * @param  Tick  $tick  Current market tick.
     */
    private function executeLiveTrade(Signal $signal, Tick $tick): void
    {
        if (! $this->brokerAdapter) {
            throw new \RuntimeException('Broker adapter required for live mode');
        }

        // Additional safety checks for live trading
        if (! $this->confirmLiveTrade($signal)) {
            $this->logger->warning('Live trade confirmation failed', [
                'signal' => $signal->action,
                'instrument' => $signal->instrumentId,
            ]);

            return;
        }

        try {
            $order = $this->createOrderFromSignal($signal, $tick);
            $result = $this->brokerAdapter->placeOrder($order);

            $this->logExecution(
                $signal->action,
                $signal->instrumentId,
                $signal->quantity ?? 1,
                $signal->price ?? $tick->price,
                $result['status'] ?? 'PENDING',
                0,
                $result['orderId'] ?? null
            );

            $this->logger->alert('LIVE ORDER PLACED', [
                'order_id' => $result['orderId'] ?? null,
                'status' => $result['status'] ?? 'PENDING',
                'instrument' => $signal->instrumentId,
                'action' => $signal->action,
            ]);
        } catch (\Exception $e) {
            $this->logger->critical('LIVE ORDER FAILED', [
                'error' => $e->getMessage(),
                'signal' => $signal->action,
            ]);
            throw $e;
        }
    }

    /**
     * Create an order object from a signal.
     *
     * @param  Signal  $signal  Trading signal.
     * @param  Tick  $tick  Current market tick.
     * @return Order Order object.
     */
    private function createOrderFromSignal(Signal $signal, Tick $tick): Order
    {
        return new Order([
            'instrument_id' => $signal->instrumentId,
            'side' => $signal->action,
            'type' => $signal->orderType ?? 'MARKET',
            'quantity' => $signal->quantity ?? 1,
            'price' => $signal->price ?? $tick->price,
            'validity' => 'DAY',
            'client_order_id' => uniqid('strat_'),
            'strategy_name' => $this->strategy->getName(),
        ]);
    }

    /**
     * Validate signal against risk limits.
     *
     * @param  Signal  $signal  Trading signal to validate.
     * @return bool True if signal passes risk checks.
     */
    private function validateRiskLimits(Signal $signal): bool
    {
        // Check max position size
        if (isset($this->riskLimits['max_position_size'])) {
            $quantity = $signal->quantity ?? 1;
            if ($quantity > $this->riskLimits['max_position_size']) {
                return false;
            }
        }

        // Check max daily trades
        if (isset($this->riskLimits['max_daily_trades'])) {
            $todayTrades = $this->countTodayTrades();
            if ($todayTrades >= $this->riskLimits['max_daily_trades']) {
                return false;
            }
        }

        // Check max loss limit
        if (isset($this->riskLimits['max_daily_loss'])) {
            $todayPnl = $this->calculateTodayPnl();
            if ($todayPnl <= -$this->riskLimits['max_daily_loss']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Confirm live trade execution (additional safety).
     *
     * @param  Signal  $signal  Trading signal.
     * @return bool True if trade should proceed.
     */
    private function confirmLiveTrade(Signal $signal): bool
    {
        // In production, this could include:
        // - Manual approval for large trades
        // - Additional risk checks
        // - Market condition validation
        // - Circuit breaker checks

        return true;
    }

    /**
     * Log trade execution.
     *
     * @param  string  $action  Trade action (BUY/SELL).
     * @param  int  $instrumentId  Instrument ID.
     * @param  float  $quantity  Quantity traded.
     * @param  float  $price  Execution price.
     * @param  string  $status  Order status.
     * @param  float  $pnl  Realized P&L (for paper mode).
     * @param  string|null  $orderId  Broker order ID.
     */
    private function logExecution(
        string $action,
        int $instrumentId,
        float $quantity,
        float $price,
        string $status,
        float $pnl = 0,
        ?string $orderId = null
    ): void {
        $execution = [
            'timestamp' => time(),
            'action' => $action,
            'instrument_id' => $instrumentId,
            'quantity' => $quantity,
            'price' => $price,
            'status' => $status,
            'pnl' => $pnl,
            'order_id' => $orderId,
            'mode' => $this->mode,
        ];

        $this->executionLog[] = $execution;

        $this->logger->info('Trade executed', $execution);
    }

    /**
     * Get current performance metrics.
     *
     * @return array Performance metrics.
     */
    public function getPerformanceMetrics(): array
    {
        if ($this->mode === self::MODE_PAPER) {
            $totalValue = $this->virtualCash;
            foreach ($this->virtualPositions as $position) {
                // In real scenario, we'd use current market price
                $totalValue += $position['quantity'] * $position['avg_price'];
            }

            $totalPnl = $totalValue - $this->initialCapital;
            $returnPct = ($totalPnl / $this->initialCapital) * 100;

            return [
                'mode' => $this->mode,
                'initial_capital' => $this->initialCapital,
                'current_value' => $totalValue,
                'cash' => $this->virtualCash,
                'total_pnl' => $totalPnl,
                'return_pct' => $returnPct,
                'positions' => count($this->virtualPositions),
                'total_trades' => count($this->executionLog),
            ];
        }

        return [
            'mode' => $this->mode,
            'total_trades' => count($this->executionLog),
        ];
    }

    /**
     * Get execution log.
     *
     * @return array Execution log entries.
     */
    public function getExecutionLog(): array
    {
        return $this->executionLog;
    }

    /**
     * Get current positions.
     *
     * @return array Current positions.
     */
    public function getPositions(): array
    {
        if ($this->mode === self::MODE_PAPER) {
            return $this->virtualPositions;
        }

        // For sandbox/live, fetch from broker
        if ($this->brokerAdapter) {
            try {
                return $this->brokerAdapter->getPositions();
            } catch (\Exception $e) {
                $this->logger->error('Failed to fetch positions', ['error' => $e->getMessage()]);

                return [];
            }
        }

        return [];
    }

    /**
     * Validate execution mode.
     */
    private function validateMode(): void
    {
        $validModes = [self::MODE_PAPER, self::MODE_SANDBOX, self::MODE_LIVE];

        if (! in_array($this->mode, $validModes)) {
            throw new \InvalidArgumentException("Invalid mode: {$this->mode}. Must be one of: ".implode(', ', $validModes));
        }

        if (in_array($this->mode, [self::MODE_SANDBOX, self::MODE_LIVE]) && ! $this->brokerAdapter) {
            throw new \InvalidArgumentException("Broker adapter required for {$this->mode} mode");
        }
    }

    /**
     * Get default risk limits.
     *
     * @return array Default risk limits.
     */
    private function getDefaultRiskLimits(): array
    {
        return [
            'max_position_size' => 1000,
            'max_daily_trades' => 100,
            'max_daily_loss' => 10000,
        ];
    }

    /**
     * Count today's trades.
     *
     * @return int Number of trades today.
     */
    private function countTodayTrades(): int
    {
        $todayStart = strtotime('today');

        return count(array_filter($this->executionLog, fn ($log) => $log['timestamp'] >= $todayStart));
    }

    /**
     * Calculate today's P&L.
     *
     * @return float Today's P&L.
     */
    private function calculateTodayPnl(): float
    {
        $todayStart = strtotime('today');
        $todayTrades = array_filter($this->executionLog, fn ($log) => $log['timestamp'] >= $todayStart);

        return array_sum(array_column($todayTrades, 'pnl'));
    }
}
