<?php

namespace TradingPlatform\Infrastructure\Http;

/**
 * Class: Minimal HTTP Router
 *
 * A lightweight, regex-based router for handling REST API requests.
 * Supports standard HTTP methods (GET, POST, PUT, DELETE), path parameters,
 * and middleware pipelines.
 */
class Router
{
    private array $routes = [];

    private array $middleware = [];

    /**
     * Register a GET route.
     *
     * @param  string  $path  URI path (e.g., '/users/{id}').
     * @param  callable  $handler  Callback or [Controller, method].
     *
     * @example
     * ```php
     * $router->get('/health', fn() => ['status' => 'ok']);
     * ```
     */
    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route.
     */
    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register a PUT route.
     */
    public function put(string $path, callable $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Register a DELETE route.
     */
    public function delete(string $path, callable $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
        ];
    }

    /**
     * Add a middleware callable returning optional error array.
     * If a middleware returns a non-null result, dispatch is short-circuited.
     */
    public function addMiddleware(callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Dispatch the current request to the matching route.
     * Executes middleware before routing and returns handler result or 404.
     */
    public function dispatch(): array
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Run middleware
        foreach ($this->middleware as $middleware) {
            $result = $middleware();
            if ($result !== null) {
                return $result; // Middleware blocked request
            }
        }

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] === $method) {
                $pattern = $this->convertToRegex($route['path']);
                if (preg_match($pattern, $path, $matches)) {
                    array_shift($matches); // Remove full match

                    return $route['handler'](...$matches);
                }
            }
        }

        return $this->notFound();
    }

    private function convertToRegex(string $path): string
    {
        // Convert /path/{id} to /path/([^/]+)
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $path);

        return '#^'.$pattern.'$#';
    }

    private function notFound(): array
    {
        http_response_code(404);

        return ['error' => 'Route not found'];
    }

    /**
     * Send a JSON response with given HTTP status.
     */
    public function sendJson(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
