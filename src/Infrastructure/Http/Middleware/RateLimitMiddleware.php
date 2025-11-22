<?php

namespace TradingPlatform\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;

class RateLimitMiddleware
{
    public function handle($request, Closure $next, $limit = 60, $window = 60)
    {
        $ip = $request->ip();
        $key = "ratelimit:{$ip}";

        $current = Redis::incr($key);

        if ($current === 1) {
            Redis::expire($key, $window);
        }

        if ($current > $limit) {
            return response()->json([
                'status' => 'error',
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Too many requests',
                ]
            ], 429);
        }

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', $limit);
        $response->headers->set('X-RateLimit-Remaining', max(0, $limit - $current));
        $response->headers->set('X-RateLimit-Reset', time() + Redis::ttl($key));

        return $response;
    }
}
