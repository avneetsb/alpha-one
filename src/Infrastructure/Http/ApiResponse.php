<?php

namespace TradingPlatform\Infrastructure\Http;

/**
 * API Response Helpers
 *
 * Provides standardized JSON responses for REST endpoints including
 * success, error, and paginated payloads. Ensures consistent response
 * shape across the platform with metadata for traceability.
 *
 * @package TradingPlatform\Infrastructure\Http
 * @version 1.0.0
 *
 * @example Success response:
 * return $this->success(['order_id' => 123], 'Order created');
 * // {
 * //   "status": "ok",
 * //   "data": {"order_id": 123},
 * //   "message": "Order created",
 * //   "error": null,
 * //   "meta": {"timestamp": "2024-01-15T14:30:00Z", "version": "v1"}
 * // }
 *
 * @example Error response:
 * return $this->error('Invalid request', 'BAD_REQUEST', 400, ['field' => 'price']);
 *
 * @example Paginated response:
 * return $this->paginated($items, ['page' => 1, 'per_page' => 50, 'total' => 1000]);
 */
trait ApiResponse
{
    protected function success($data = null, ?string $message = null, int $code = 200)
    {
        return response()->json([
            'status' => 'ok',
            'data' => $data,
            'message' => $message,
            'error' => null,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'version' => 'v1',
            ]
        ], $code);
    }

    /**
     * Return a standardized error response.
     *
     * @param string $message Human-readable error message
     * @param string $errorCode Machine-readable error code
     * @param int    $statusCode HTTP status code
     * @param mixed  $details Optional error details payload
     */
    protected function error(string $message, string $errorCode, int $statusCode = 400, $details = null)
    {
        return response()->json([
            'status' => 'error',
            'data' => null,
            'error' => [
                'code' => $errorCode,
                'message' => $message,
                'details' => $details,
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'version' => 'v1',
            ]
        ], $statusCode);
    }

    /**
     * Return a standardized paginated response.
     *
     * @param mixed $data Paginated items
     * @param array $meta Pagination metadata (page, per_page, total, etc.)
     */
    protected function paginated($data, $meta = [])
    {
        return response()->json([
            'status' => 'ok',
            'data' => $data,
            'error' => null,
            'meta' => array_merge([
                'timestamp' => now()->toIso8601String(),
                'version' => 'v1',
            ], $meta)
        ]);
    }
}
