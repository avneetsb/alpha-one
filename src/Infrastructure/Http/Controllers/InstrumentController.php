<?php

namespace TradingPlatform\Infrastructure\Http\Controllers;

use TradingPlatform\Domain\Exchange\Services\InstrumentManager;
use TradingPlatform\Infrastructure\Http\ApiResponse;

/**
 * Instrument Controller
 *
 * Exposes endpoints to list and refresh trading instruments.
 * Delegates to InstrumentManager for data retrieval and synchronization.
 *
 * @package TradingPlatform\Infrastructure\Http\Controllers
 * @version 1.0.0
 */
class InstrumentController
{
    use ApiResponse;

    private InstrumentManager $manager;

    public function __construct(InstrumentManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * List instruments with optional filters.
     */
    public function index($request)
    {
        // Mock request handling
        $filters = $request->only(['tradable', 'symbol', 'type']);
        $instruments = $this->manager->list($filters);
        
        return $this->success($instruments);
    }

    /**
     * Refresh instrument catalogue from broker feeds.
     */
    public function refresh()
    {
        $this->manager->syncInstruments();
        return $this->success(['message' => 'Instruments refreshed']);
    }
}
