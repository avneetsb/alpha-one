<?php

namespace TradingPlatform\Infrastructure\Http\Controllers;

use TradingPlatform\Infrastructure\Http\ApiResponse;

/**
 * Class: Market Data Controller
 *
 * Manages real-time market data subscriptions via HTTP endpoints.
 * Allows clients to subscribe/unsubscribe to instruments and check connection status.
 */
class MarketDataController
{
    use ApiResponse;

    /**
     * Subscribe to market data for provided instruments.
     *
     * @param  mixed  $request  HTTP Request object containing 'instruments' array.
     * @return \Illuminate\Http\JsonResponse JSON response confirming subscription.
     *
     * @example Request
     * POST /api/v1/market-data/subscribe
     * { "instruments": ["RELIANCE", "INFY"] }
     */
    public function subscribe($request)
    {
        return $this->success(['subscribed' => $request->input('instruments')]);
    }

    /**
     * Unsubscribe from market data for provided instruments.
     *
     * @param  mixed  $request  HTTP Request object containing 'instruments' array.
     * @return \Illuminate\Http\JsonResponse JSON response confirming unsubscription.
     *
     * @example Request
     * POST /api/v1/market-data/unsubscribe
     * { "instruments": ["RELIANCE"] }
     */
    public function unsubscribe($request)
    {
        return $this->success(['unsubscribed' => $request->input('instruments')]);
    }

    /**
     * Get WebSocket connection status and current subscriptions.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function status()
    {
        return $this->success(['ws' => 'connected', 'subscriptions' => []]);
    }
}
