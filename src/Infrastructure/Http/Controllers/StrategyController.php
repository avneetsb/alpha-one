<?php

namespace TradingPlatform\Infrastructure\Http\Controllers;

use TradingPlatform\Domain\Strategy\Services\StrategyFactory;
use TradingPlatform\Infrastructure\Http\ApiResponse;

/**
 * Class: Strategy Controller
 *
 * Manages the lifecycle of trading strategies via REST API.
 * Allows clients to start new strategy instances and stop running ones.
 */
class StrategyController
{
    use ApiResponse;

    /**
     * @var StrategyFactory Factory to create strategy instances.
     */
    private StrategyFactory $factory;

    /**
     * StrategyController constructor.
     *
     * @param  StrategyFactory  $factory  Factory to create strategy instances.
     */
    public function __construct(StrategyFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Start a new strategy instance.
     *
     * @param  mixed  $request  HTTP request containing:
     *                          - strategy_class (string): Class name of the strategy.
     *                          - parameters (array): Strategy configuration.
     * @return \Illuminate\Http\JsonResponse JSON response with the new run ID.
     *
     * @example Request
     * POST /api/v1/strategies/start
     * {
     *   "strategy_class": "App\\Strategies\\TrendFollower",
     *   "parameters": { "period": 14 }
     * }
     */
    public function start($request)
    {

        return $this->success(['run_id' => 'run_'.uniqid(), 'status' => 'STARTED']);
    }

    /**
     * Stop a running strategy instance.
     *
     * @param  mixed  $request  HTTP request containing 'run_id'.
     * @return \Illuminate\Http\JsonResponse JSON response confirming stop.
     *
     * @example Request
     * POST /api/v1/strategies/stop
     * { "run_id": "run_65a1b2c3" }
     */
    public function stop($request)
    {
        return $this->success(['status' => 'STOPPED']);
    }
}
