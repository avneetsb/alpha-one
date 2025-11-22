<?php

namespace TradingPlatform\Infrastructure\Http\Controllers;

use TradingPlatform\Domain\Strategy\Services\StrategyFactory;
use TradingPlatform\Infrastructure\Http\ApiResponse;

/**
 * Controller handling strategy lifecycle operations.
 *
 * Provides endpoints to start and stop strategies.
 */
class StrategyController
{
    use ApiResponse;

    private StrategyFactory $factory;

    /**
 * StrategyController constructor.
 *
 * @param StrategyFactory $factory Factory to create strategy instances.
 */
public function __construct(StrategyFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
 * Start a strategy.
 *
 * @param mixed $request HTTP request containing strategy parameters.
 * @return \Symfony\Component\HttpFoundation\Response JSON response with run ID.
 */
public function start($request)
    {

        return $this->success(['run_id' => 'run_' . uniqid(), 'status' => 'STARTED']);
    }

    /**
 * Stop a running strategy.
 *
 * @param mixed $request HTTP request identifying the strategy to stop.
 * @return \Symfony\Component\HttpFoundation\Response JSON response indicating status.
 */
public function stop($request)
    {
        return $this->success(['status' => 'STOPPED']);
    }
}
