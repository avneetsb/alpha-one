<?php

namespace TradingPlatform\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;

class IdempotencyMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!$request->hasHeader('Idempotency-Key')) {
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
