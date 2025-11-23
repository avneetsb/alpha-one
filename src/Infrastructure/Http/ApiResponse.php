<?php

namespace TradingPlatform\Infrastructure\Http;

/**
 * Trait: API Response Helpers
 *
 * Provides standardized JSON response methods for REST API endpoints.
 * Ensures consistent response structure (status, data, error, meta) across the application.
 *
 * **Response Structure:**
 * - `status`: 'ok' or 'error'
 * - `data`: The payload (or null on error)
 * - `message`: Optional success message
 * - `error`: Error details (code, message, details)
 * - `meta`: Metadata (timestamp, version, pagination)
 */
trait ApiResponse
{
    /**
     * Return a standardized success response.
     *
     * @param  mixed  $data  The response payload (array, object, or null).
     * @param  string|null  $message  Optional success message.
     * @param  int  $code  HTTP status code (default: 200).
     * @return \Illuminate\Http\JsonResponse JSON response object.
     *
     * @example
     * ```php
     * return $this->success(['id' => 1], 'Created', 201);
     * ```
     */
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
            ],
        ], $code);
    }

    /**
     * Return a standardized error response.
     *
     * @param  string  $message  Human-readable error message
     * @param  string  $errorCode  Machine-readable error code
     * @param  int  $statusCode  HTTP status code
     * @param  mixed  $details  Optional error details payload
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
            ],
        ], $statusCode);
    }

    /**
     * Return a standardized paginated response.
     *
     * @param  mixed  $data  Paginated items
     * @param  array  $meta  Pagination metadata (page, per_page, total, etc.)
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
            ], $meta),
        ]);
    }
}
