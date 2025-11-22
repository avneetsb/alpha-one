<?php

namespace TradingPlatform\Application\Engine;

use TradingPlatform\Domain\Strategy\Strategy;
use TradingPlatform\Domain\Strategy\Signal;
use TradingPlatform\Domain\MarketData\Tick;
use TradingPlatform\Domain\MarketData\Candle;
use TradingPlatform\Infrastructure\Logger\LoggerService;

/**
 * Strategy Execution Engine
 *
 * Orchestrates strategy registration and event processing for ticks and candles.
 * Collects signals from strategies and hands them off for order placement.
 * Provides robust error handling and logging for operational observability.
 *
 * @package TradingPlatform\Application\Engine
 * @version 1.0.0
 *
 * @example Register and process:
 * $engine->registerStrategy($strategy);
 * $engine->processTick($tick);
 */
class StrategyEngine
{
    /** @var Strategy[] */
    private array $strategies = [];
    private $logger;

    public function __construct()
    {
        $this->logger = LoggerService::getLogger();
    }

    /**
     * Register a strategy for subsequent event processing.
     */
    public function registerStrategy(Strategy $strategy): void
    {
        $this->strategies[$strategy->getName()] = $strategy;
        $this->logger->info("Registered strategy: " . $strategy->getName());
    }

    /**
     * Process a market tick across all registered strategies.
     */
    public function processTick(Tick $tick): void
    {
        foreach ($this->strategies as $strategy) {
            try {
                $signal = $strategy->onTick($tick);
                if ($signal) {
                    $this->handleSignal($signal);
                }
            } catch (\Exception $e) {
                $this->logger->error("Error in strategy {$strategy->getName()} onTick: " . $e->getMessage());
            }
        }
    }

    /**
     * Process a completed candle across all registered strategies.
     */
    public function processCandle(Candle $candle): void
    {
        foreach ($this->strategies as $strategy) {
            try {
                $signal = $strategy->onCandle($candle);
                if ($signal) {
                    $this->handleSignal($signal);
                }
            } catch (\Exception $e) {
                $this->logger->error("Error in strategy {$strategy->getName()} onCandle: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle a generated trading signal.
     * In production, dispatches a job to place/cancel/modify orders.
     */
    private function handleSignal(Signal $signal): void
    {
        $this->logger->info("SIGNAL GENERATED: {$signal->action} {$signal->instrumentId} @ {$signal->price} by {$signal->strategyName}");
        
        // Here we would dispatch an OrderPlacementJob or similar
        // For now, we just log it.
        // QueueService::getConnection()->push(new PlaceOrderJob($signal));
    }
}
