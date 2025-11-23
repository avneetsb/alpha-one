<?php

namespace TradingPlatform\Infrastructure\Http\Controllers;

use TradingPlatform\Domain\Exchange\Services\InstrumentManager;
use TradingPlatform\Infrastructure\Http\ApiResponse;

/**
 * Class: Instrument Controller
 *
 * Exposes REST API endpoints for instrument management.
 * Allows clients to list available instruments with filtering and trigger
 * synchronization with the broker's master list.
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
     *
     * @param  mixed  $request  HTTP Request object containing filters.
     *                          - 'tradable' (bool): Filter by tradable status.
     *                          - 'symbol' (string): Filter by symbol name.
     *                          - 'type' (string): Filter by instrument type (EQUITY, OPTION, etc.).
     * @return \Illuminate\Http\JsonResponse JSON response with list of instruments.
     *
     * @example Request
     * GET /api/v1/instruments?tradable=1&type=EQUITY
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
     *
     * Triggers a synchronization process to update the local instrument database
     * with the latest master list from the configured broker.
     *
     * @return \Illuminate\Http\JsonResponse JSON response confirming sync initiation.
     *
     * @example Request
     * POST /api/v1/instruments/refresh
     */
    public function refresh()
    {
        $this->manager->syncInstruments();

        return $this->success(['message' => 'Instruments refreshed']);
    }
}
