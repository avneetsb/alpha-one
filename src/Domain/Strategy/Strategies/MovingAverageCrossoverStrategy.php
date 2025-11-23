<?php

namespace TradingPlatform\Domain\Strategy\Strategies;

use TradingPlatform\Domain\MarketData\Candle;
use TradingPlatform\Domain\MarketData\Tick;
use TradingPlatform\Domain\Strategy\AbstractStrategy;
use TradingPlatform\Domain\Strategy\Signal;

/**
 * Moving Average Crossover Strategy
 *
 * A production-ready strategy that uses moving average crossovers
 * to generate trading signals. Optimized for deployment in paper,
 * sandbox, and live modes.
 *
 * **Strategy Logic:**
 * - BUY when fast MA crosses above slow MA (golden cross)
 * - SELL when fast MA crosses below slow MA (death cross)
 * - Uses position sizing and risk management
 *
 * **Hyperparameters:**
 * - fast_period: Fast moving average period (default: 10)
 * - slow_period: Slow moving average period (default: 20)
 * - position_size: Position size per trade (default: 100)
 * - stop_loss_pct: Stop loss percentage (default: 2.0)
 * - take_profit_pct: Take profit percentage (default: 4.0)
 *
 * @version 1.0.0
 */
class MovingAverageCrossoverStrategy extends AbstractStrategy
{
    private array $priceHistory = [];

    private ?float $entryPrice = null;

    private ?string $currentPosition = null; // 'LONG' or null

    /**
     * MovingAverageCrossoverStrategy constructor.
     *
     * @param  string  $name  Strategy name.
     */
    public function __construct(string $name = 'MA_Crossover')
    {
        parent::__construct($name);

        // Set default hyperparameters
        $this->hp = [
            'fast_period' => 10,
            'slow_period' => 20,
            'position_size' => 100,
            'stop_loss_pct' => 2.0,
            'take_profit_pct' => 4.0,
        ];
    }

    /**
     * Define hyperparameters for optimization.
     *
     * @return array Hyperparameter definitions.
     */
    public function hyperparameters(): array
    {
        return [
            'fast_period' => ['type' => 'int', 'min' => 5, 'max' => 20],
            'slow_period' => ['type' => 'int', 'min' => 15, 'max' => 50],
            'position_size' => ['type' => 'int', 'min' => 50, 'max' => 500],
            'stop_loss_pct' => ['type' => 'float', 'min' => 1.0, 'max' => 5.0],
            'take_profit_pct' => ['type' => 'float', 'min' => 2.0, 'max' => 10.0],
        ];
    }

    /**
     * Process a market tick.
     *
     * @param  Tick  $tick  Market tick data.
     * @return Signal|null Trading signal or null.
     */
    public function onTick(Tick $tick): ?Signal
    {
        // Add price to history
        $this->priceHistory[] = $tick->price;

        // Keep only necessary history
        $maxPeriod = max($this->hp['fast_period'], $this->hp['slow_period']);
        if (count($this->priceHistory) > $maxPeriod + 10) {
            array_shift($this->priceHistory);
        }

        // Need enough data for both MAs
        if (count($this->priceHistory) < $this->hp['slow_period']) {
            return null;
        }

        // Calculate moving averages
        $fastMA = $this->calculateMA($this->hp['fast_period']);
        $slowMA = $this->calculateMA($this->hp['slow_period']);

        // Get previous MAs for crossover detection
        if (count($this->priceHistory) < $this->hp['slow_period'] + 1) {
            return null;
        }

        $prevFastMA = $this->calculateMA($this->hp['fast_period'], 1);
        $prevSlowMA = $this->calculateMA($this->hp['slow_period'], 1);

        // Check for exit conditions if we have a position
        if ($this->currentPosition === 'LONG' && $this->entryPrice !== null) {
            $currentPnlPct = (($tick->price - $this->entryPrice) / $this->entryPrice) * 100;

            // Stop loss
            if ($currentPnlPct <= -$this->hp['stop_loss_pct']) {
                $this->currentPosition = null;
                $this->entryPrice = null;

                return new Signal(
                    $this->name,
                    'SELL',
                    $tick->instrument_id,
                    $tick->price,
                    $this->hp['position_size'],
                    'MARKET'
                );
            }

            // Take profit
            if ($currentPnlPct >= $this->hp['take_profit_pct']) {
                $this->currentPosition = null;
                $this->entryPrice = null;

                return new Signal(
                    $this->name,
                    'SELL',
                    $tick->instrument_id,
                    $tick->price,
                    $this->hp['position_size'],
                    'MARKET'
                );
            }

            // Death cross - exit position
            if ($prevFastMA > $prevSlowMA && $fastMA < $slowMA) {
                $this->currentPosition = null;
                $this->entryPrice = null;

                return new Signal(
                    $this->name,
                    'SELL',
                    $tick->instrument_id,
                    $tick->price,
                    $this->hp['position_size'],
                    'MARKET'
                );
            }
        }

        // Check for entry conditions if we don't have a position
        if ($this->currentPosition === null) {
            // Golden cross - enter long
            if ($prevFastMA < $prevSlowMA && $fastMA > $slowMA) {
                $this->currentPosition = 'LONG';
                $this->entryPrice = $tick->price;

                return new Signal(
                    $this->name,
                    'BUY',
                    $tick->instrument_id,
                    $tick->price,
                    $this->hp['position_size'],
                    'MARKET'
                );
            }
        }

        return null;
    }

    /**
     * Process a completed candle.
     *
     * @param  Candle  $candle  Completed candle data.
     * @return Signal|null Trading signal or null.
     */
    public function onCandle(Candle $candle): ?Signal
    {
        // For this strategy, we primarily use tick data
        // But we could also implement candle-based logic here
        return null;
    }

    /**
     * Calculate simple moving average.
     *
     * @param  int  $period  MA period.
     * @param  int  $offset  Offset from current (0 = current, 1 = previous).
     * @return float Moving average value.
     */
    private function calculateMA(int $period, int $offset = 0): float
    {
        $dataCount = count($this->priceHistory);
        $endIndex = $dataCount - 1 - $offset;
        $startIndex = $endIndex - $period + 1;

        if ($startIndex < 0) {
            return 0.0;
        }

        $sum = 0;
        for ($i = $startIndex; $i <= $endIndex; $i++) {
            $sum += $this->priceHistory[$i];
        }

        return $sum / $period;
    }

    /**
     * Get strategy description.
     *
     * @return string Strategy description.
     */
    public function getDescription(): string
    {
        return 'Moving Average Crossover Strategy with dynamic position sizing and risk management';
    }

    /**
     * Get current state for monitoring.
     *
     * @return array Current state.
     */
    public function getState(): array
    {
        $fastMA = count($this->priceHistory) >= $this->hp['fast_period']
            ? $this->calculateMA($this->hp['fast_period'])
            : null;

        $slowMA = count($this->priceHistory) >= $this->hp['slow_period']
            ? $this->calculateMA($this->hp['slow_period'])
            : null;

        return [
            'position' => $this->currentPosition,
            'entry_price' => $this->entryPrice,
            'fast_ma' => $fastMA,
            'slow_ma' => $slowMA,
            'price_history_size' => count($this->priceHistory),
        ];
    }
}
