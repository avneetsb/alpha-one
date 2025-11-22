<?php

/**
 * REST API Entry Point
 */

require_once __DIR__ . '/../vendor/autoload.php';

use TradingPlatform\Infrastructure\Database\DatabaseConnection;
use TradingPlatform\Infrastructure\Http\Router;
use TradingPlatform\Infrastructure\Http\Middleware\AuthMiddleware;
use TradingPlatform\Infrastructure\Http\Controllers\{OrderController, MarketDataController, PortfolioController};

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize database
DatabaseConnection::getInstance();

// Create router
$router = new Router();

// Add authentication middleware (except for login)
$authMiddleware = new AuthMiddleware();

// Public routes
$router->post('/api/auth/login', function() use ($authMiddleware) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Simplified login - in production, verify against database
    if (($input['username'] ?? '') === 'admin' && ($input['password'] ?? '') === 'password') {
        $token = $authMiddleware->generateToken(['user_id' => 1, 'username' => 'admin']);
        return ['success' => true, 'token' => $token];
    }
    
    http_response_code(401);
    return ['error' => 'Invalid credentials'];
});

// Protected routes - Add middleware
$router->addMiddleware($authMiddleware);

// Order routes
$orderController = new OrderController();
$router->post('/api/orders', [$orderController, 'placeOrder']);
$router->get('/api/orders', [$orderController, 'getOrders']);
$router->get('/api/orders/{id}', [$orderController, 'getOrder']);
$router->delete('/api/orders/{id}', [$orderController, 'cancelOrder']);

// Market data routes
$marketDataController = new MarketDataController();
$router->get('/api/instruments', [$marketDataController, 'getInstruments']);
$router->get('/api/instruments/{id}', [$marketDataController, 'getInstrument']);
$router->get('/api/ticks/{instrumentId}', [$marketDataController, 'getLatestTick']);

// Portfolio routes
$portfolioController = new PortfolioController();
$router->get('/api/positions', [$portfolioController, 'getPositions']);
$router->get('/api/positions/{id}', [$portfolioController, 'getPosition']);

// Dispatch request
$response = $router->dispatch();
$router->sendJson($response);
