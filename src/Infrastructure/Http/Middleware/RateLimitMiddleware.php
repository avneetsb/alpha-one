<?php

namespace TradingPlatform\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;

/**
 * Middleware: Rate Limiter
 *
 * Limits the number of requests a client can make within a specified time window.
 * Uses Redis to track request counts per IP address.
 */
class RateLimitMiddleware
{
    /**
     * Handle the incoming request.
     *
     * @param  mixed  $request  The HTTP request.
     * @param  \Closure  $next  The next middleware/handler.
     * @param  int  $limit  Max requests allowed (default: 60).
     * @param  int  $window  Time window in seconds (default: 60).
     * @return mixed The response.
     */
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
                ],
            ], 429);
        }

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', $limit);
        $response->headers->set('X-RateLimit-Remaining', max(0, $limit - $current));
        $response->headers->set('X-RateLimit-Reset', time() + Redis::ttl($key));

        return $response;
    }
}
