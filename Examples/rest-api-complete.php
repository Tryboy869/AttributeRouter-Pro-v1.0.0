<?php

/**
 * COMPLETE REST API EXAMPLE
 * Full-featured REST API using AttributeRouter Pro
 * 
 * Features:
 * - RESTful endpoints (CRUD)
 * - Authentication & Authorization
 * - Rate limiting per endpoint
 * - Response caching
 * - Input validation
 * - Error handling
 * - API versioning
 */

require __DIR__ . '/../vendor/autoload.php';

use AttributeRouter\Router;

// ========== API CONFIGURATION ==========

$router = new Router([
    'controllers_path' => __DIR__ . '/controllers',
    'base_namespace' => 'App\\Controllers',
    'cache_enabled' => true,
    'debug' => false, // Disable in production
]);

// Register API middleware
$router->middleware()->alias('api-auth', ApiAuthMiddleware::class);
$router->middleware()->alias('api-response', ApiResponseMiddleware::class);

// Global middleware for all API routes
$router->middleware(['api-response']);

// Discover API routes
$router->discoverRoutes();

// ========== REQUEST HANDLING ==========

try {
    $response = $router->run();
    
    // Response already handled by ApiResponseMiddleware
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (\AttributeRouter\Exceptions\RateLimitExceededException $e) {
    http_response_code(429);
    echo json_encode([
        'error' => [
            'type' => 'rate_limit_exceeded',
            'message' => $e->getMessage(),
            'retry_after' => $_SERVER['HTTP_RETRY_AFTER'] ?? 60
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (\AttributeRouter\Exceptions\RouteNotFoundException $e) {
    http_response_code(404);
    echo json_encode([
        'error' => [
            'type' => 'not_found',
            'message' => 'The requested endpoint does not exist',
            'path' => $_SERVER['REQUEST_URI']
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (\AttributeRouter\Exceptions\MethodNotAllowedException $e) {
    http_response_code(405);
    echo json_encode([
        'error' => [
            'type' => 'method_not_allowed',
            'message' => 'The HTTP method is not supported for this endpoint',
            'method' => $_SERVER['REQUEST_METHOD']
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => [
            'type' => 'internal_error',
            'message' => 'An unexpected error occurred'
        ]
    ], JSON_PRETTY_PRINT);
    
    // Log error for debugging
    error_log($e->getMessage());
}

// ========== API MIDDLEWARE ==========

/**
 * API Authentication Middleware
 * Validates API keys and Bearer tokens
 */
class ApiAuthMiddleware
{
    public function handle(array $request, callable $next): mixed
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!$authHeader) {
            http_response_code(401);
            return [
                'error' => [
                    'type' => 'authentication_required',
                    'message' => 'API authentication is required'
                ]
            ];
        }
        
        // Validate Bearer token
        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            
            if (!$this->validateBearerToken($token)) {
                http_response_code(401);
                return [
                    'error' => [
                        'type' => 'invalid_token',
                        'message' => 'The provided token is invalid or expired'
                    ]
                ];
            }
            
            // Store authenticated user
            $_SERVER['API_USER'] = $this->getUserFromToken($token);
        }
        
        // Validate API Key
        elseif (isset($_SERVER['HTTP_X_API_KEY'])) {
            $apiKey = $_SERVER['HTTP_X_API_KEY'];
            
            if (!$this->validateApiKey($apiKey)) {
                http_response_code(401);
                return [
                    'error' => [
                        'type' => 'invalid_api_key',
                        'message' => 'The provided API key is invalid'
                    ]
                ];
            }
            
            $_SERVER['API_USER'] = $this->getUserFromApiKey($apiKey);
        }
        
        else {
            http_response_code(401);
            return [
                'error' => [
                    'type' => 'unsupported_auth_method',
                    'message' => 'Use Bearer token or X-API-Key header'
                ]
            ];
        }
        
        return $next();
    }
    
    private function validateBearerToken(string $token): bool
    {
        // Token validation logic (JWT, database, etc.)
        return strlen($token) > 10; // Mock validation
    }
    
    private function validateApiKey(string $key): bool
    {
        // API key validation
        return strlen($key) === 32; // Mock validation
    }
    
    private function getUserFromToken(string $token): array
    {
        return [
            'id' => 1,
            'email' => 'user@example.com',
            'role' => 'user'
        ];
    }
    
    private function getUserFromApiKey(string $key): array
    {
        return [
            'id' => 1,
            'email' => 'api@example.com',
            'role' => 'api'
        ];
    }
}

/**
 * API Response Middleware
 * Formats all responses consistently
 */
class ApiResponseMiddleware
{
    public function handle(array $request, callable $next): mixed
    {
        header('Content-Type: application/json');
        header('X-API-Version: 1.0');
        header('X-Request-ID: ' . uniqid('req_'));
        
        $response = $next();
        
        // Wrap response in standard format
        if (!isset($response['error'])) {
            return [
                'success' => true,
                'data' => $response,
                'meta' => [
                    'timestamp' => time(),
                    'request_id' => $_SERVER['HTTP_X_REQUEST_ID'] ?? uniqid('req_')
                ]
            ];
        }
        
        return $response;
    }
}