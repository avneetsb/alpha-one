<?php

namespace TradingPlatform\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;

/**
 * Middleware: Idempotency
 *
 * Ensures that identical requests (identified by `Idempotency-Key` header)
 * are processed only once. Caches successful responses and serves them
 * for subsequent duplicate requests.
 */
class IdempotencyMiddleware
{
    /**
     * Handle the incoming request.
     *
     * @param  mixed  $request  The HTTP request.
     * @param  \Closure  $next  The next middleware/handler.
     * @return mixed The response.
     *
     * @example Header
     * Idempotency-Key: 123e4567-e89b-12d3-a456-426614174000
     */
    public function handle($request, Closure $next)
    {
        if (! $request->hasHeader('Idempotency-Key')) {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key');
        $cacheKey = "idempotency:{$key}";

        // Check if key exists
        if (Redis::exists($cacheKey)) {
            $cachedResponse = json_decode(Redis::get($cacheKey), true);

            return response()->json($cachedResponse['body'], $cachedResponse['status']);
        }

        $response = $next($request);

        // Cache successful responses (2xx)
        if ($response->status() >= 200 && $response->status() < 300) {
            Redis::setex($cacheKey, 86400, json_encode([ // 24 hours TTL
                'status' => $response->status(),
                'body' => json_decode($response->content(), true),
            ]));
        }

        return $response;
    }
}
