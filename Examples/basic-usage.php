<?php

require __DIR__ . '/vendor/autoload.php';

use AttributeRouter\Router;

/**
 * BASIC USAGE EXAMPLE
 * 
 * This demonstrates the simplest way to use AttributeRouter Pro
 */

// 1. Create router instance
$router = new Router([
    'controllers_path' => __DIR__ . '/app/Controllers',
    'base_namespace' => 'App\\Controllers',
    'cache_enabled' => true,
    'cache_path' => __DIR__ . '/cache',
    'debug' => true,
]);

// 2. Discover routes from controllers
$router->discoverRoutes();

// 3. Run router (match + dispatch current request)
try {
    $response = $router->run();
    
    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (\AttributeRouter\Exceptions\RouteNotFoundException $e) {
    http_response_code(404);
    echo json_encode(['error' => $e->getMessage()]);
    
} catch (\AttributeRouter\Exceptions\MethodNotAllowedException $e) {
    http_response_code(405);
    echo json_encode(['error' => $e->getMessage()]);
    
} catch (\AttributeRouter\Exceptions\RateLimitExceededException $e) {
    // Headers already set by RateLimiter
    echo json_encode(['error' => $e->getMessage()]);
    
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage()
    ]);
}