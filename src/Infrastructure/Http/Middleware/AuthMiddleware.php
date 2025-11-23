<?php

namespace TradingPlatform\Infrastructure\Http\Middleware;

/**
 * Middleware: JWT Authentication
 *
 * Intercepts incoming requests to validate Bearer tokens.
 * Decodes the JWT payload and attaches user information to the global state.
 */
class AuthMiddleware
{
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = env('JWT_SECRET', 'your-secret-key-change-in-production');
    }

    /**
     * Handle the incoming request.
     *
     * Checks for the `Authorization` header, parses the Bearer token,
     * and verifies its signature and expiration.
     *
     * @return array|null Returns error array if auth fails, null if successful.
     */
    public function __invoke(): ?array
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($authHeader)) {
            http_response_code(401);

            return ['error' => 'Authentication required'];
        }

        // Extract token from "Bearer <token>"
        if (! preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            http_response_code(401);

            return ['error' => 'Invalid authorization header'];
        }

        $token = $matches[1];

        try {
            $payload = $this->verifyToken($token);
            $_SERVER['AUTH_USER'] = $payload; // Store user info

            return null; // Allow request to proceed
        } catch (\Exception $e) {
            http_response_code(401);

            return ['error' => 'Invalid or expired token'];
        }
    }

    /**
     * Verify JWT signature and expiration.
     */
    private function verifyToken(string $token): array
    {
        // Simple JWT verification (in production, use firebase/php-jwt)
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new \Exception('Invalid token format');
        }

        [$header, $payload, $signature] = $parts;

        // Verify signature
        $expectedSignature = hash_hmac('sha256', "$header.$payload", $this->secretKey, true);
        $expectedSignature = $this->base64UrlEncode($expectedSignature);

        if (! hash_equals($expectedSignature, $signature)) {
            throw new \Exception('Invalid signature');
        }

        // Decode payload
        $payloadData = json_decode($this->base64UrlDecode($payload), true);

        // Check expiration
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            throw new \Exception('Token expired');
        }

        return $payloadData;
    }

    /**
     * Generate a new JWT token.
     *
     * @param  array  $payload  Data to encode in the token.
     * @param  int  $expiresIn  Token validity duration in seconds.
     * @return string The signed JWT string.
     *
     * @example
     * ```php
     * $token = $auth->generateToken(['user_id' => 1, 'role' => 'admin']);
     * ```
     */
    public function generateToken(array $payload, int $expiresIn = 3600): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        $payload['iat'] = time();
        $payload['exp'] = time() + $expiresIn;
        $payload = json_encode($payload);

        $base64Header = $this->base64UrlEncode($header);
        $base64Payload = $this->base64UrlEncode($payload);

        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", $this->secretKey, true);
        $base64Signature = $this->base64UrlEncode($signature);

        return "$base64Header.$base64Payload.$base64Signature";
    }

    /**
     * Base64 URL-safe encoding.
     */
    private function base64UrlEncode($data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL-safe decoding.
     */
    private function base64UrlDecode($data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
