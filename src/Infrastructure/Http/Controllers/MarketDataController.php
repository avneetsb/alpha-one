<?php

namespace TradingPlatform\Infrastructure\Http\Controllers;

use TradingPlatform\Infrastructure\Http\ApiResponse;

/**
 * Market Data Controller
 *
 * Manages WebSocket feed subscriptions for instruments and provides
 * connection status endpoints.
 *
 * @package TradingPlatform\Infrastructure\Http\Controllers
 * @version 1.0.0
 */
class MarketDataController
{
    use ApiResponse;

    /**
     * Subscribe to market data for provided instruments.
     */
    public function subscribe($request)
    {

        return $this->success(['subscribed' => $request->input('instruments')]);
    }

    /**
     * Unsubscribe from market data for provided instruments.
     */
    public function unsubscribe($request)
    {
        return $this->success(['unsubscribed' => $request->input('instruments')]);
    }

    /**
     * Get WebSocket connection status and current subscriptions.
     */
    public function status()
    {
        return $this->success(['ws' => 'connected', 'subscriptions' => []]);
    }
}
