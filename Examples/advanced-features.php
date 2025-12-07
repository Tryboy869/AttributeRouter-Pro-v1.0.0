<?php

/**
 * ADVANCED FEATURES EXAMPLE
 * Demonstrates advanced AttributeRouter Pro capabilities
 */

require __DIR__ . '/../vendor/autoload.php';

use AttributeRouter\Router;

// ========== ADVANCED CONFIGURATION ==========

$router = new Router([
    'controllers_path' => __DIR__ . '/controllers',
    'base_namespace' => 'App\\Controllers',
    'cache_enabled' => true,
    'cache_path' => __DIR__ . '/../cache',
    'debug' => true,
]);

// ========== MIDDLEWARE CONFIGURATION ==========

// Register middleware aliases
$router->middleware()->alias('auth', AuthMiddleware::class);
$router->middleware()->alias('admin', AdminMiddleware::class);
$router->middleware()->alias('cors', CorsMiddleware::class);
$router->middleware()->alias('json-response', JsonResponseMiddleware::class);

// Register global middleware (executed on all routes)
$router->middleware(['cors', 'json-response']);

// ========== DEPENDENCY INJECTION ==========

// Bind dependencies for controllers
$router->bind(Database::class, function() {
    return new Database([
        'host' => 'localhost',
        'database' => 'myapp',
        'username' => 'root',
        'password' => '',
    ]);
});

$router->bind(Logger::class, function() {
    return new Logger(__DIR__ . '/../logs/app.log');
});

// ========== DISCOVER ROUTES ==========

$router->discoverRoutes();

// ========== ADVANCED ERROR HANDLING ==========

try {
    $response = $router->run();
    
    // Send JSON response with pretty print
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (\AttributeRouter\Exceptions\RateLimitExceededException $e) {
    // Rate limit headers already set by RateLimiter
    http_response_code(429);
    echo json_encode([
        'error' => 'Too Many Requests',
        'message' => $e->getMessage(),
        'code' => 'RATE_LIMIT_EXCEEDED',
        'retry_after' => $_SERVER['HTTP_RETRY_AFTER'] ?? 60
    ], JSON_PRETTY_PRINT);
    
} catch (\AttributeRouter\Exceptions\RouteNotFoundException $e) {
    http_response_code(404);
    echo json_encode([
        'error' => 'Not Found',
        'message' => $e->getMessage(),
        'code' => 'ROUTE_NOT_FOUND',
        'path' => $_SERVER['REQUEST_URI']
    ], JSON_PRETTY_PRINT);
    
} catch (\AttributeRouter\Exceptions\MethodNotAllowedException $e) {
    http_response_code(405);
    echo json_encode([
        'error' => 'Method Not Allowed',
        'message' => $e->getMessage(),
        'code' => 'METHOD_NOT_ALLOWED',
        'method' => $_SERVER['REQUEST_METHOD']
    ], JSON_PRETTY_PRINT);
    
} catch (\Throwable $e) {
    http_response_code(500);
    
    // Show detailed error in debug mode
    $errorResponse = [
        'error' => 'Internal Server Error',
        'code' => 'INTERNAL_ERROR'
    ];
    
    if ($router->config['debug']) {
        $errorResponse['message'] = $e->getMessage();
        $errorResponse['file'] = $e->getFile();
        $errorResponse['line'] = $e->getLine();
        $errorResponse['trace'] = $e->getTraceAsString();
    }
    
    echo json_encode($errorResponse, JSON_PRETTY_PRINT);
}

// ========== EXAMPLE MIDDLEWARE CLASSES ==========

/**
 * Authentication Middleware
 */
class AuthMiddleware
{
    public function handle(array $request, callable $next): mixed
    {
        // Check for API token
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        
        if (!$token || !$this->validateToken($token)) {
            http_response_code(401);
            return ['error' => 'Unauthorized', 'code' => 'AUTH_REQUIRED'];
        }
        
        // Store user info for controller access
        $_SERVER['USER_ID'] = $this->getUserIdFromToken($token);
        
        return $next();
    }
    
    private function validateToken(string $token): bool
    {
        // Token validation logic
        return str_starts_with($token, 'Bearer ');
    }
    
    private function getUserIdFromToken(string $token): int
    {
        // Extract user ID from token
        return 1; // Mock user ID
    }
}

/**
 * Admin Middleware
 */
class AdminMiddleware
{
    public function handle(array $request, callable $next): mixed
    {
        $userId = $_SERVER['USER_ID'] ?? null;
        
        if (!$userId || !$this->isAdmin($userId)) {
            http_response_code(403);
            return ['error' => 'Forbidden', 'code' => 'ADMIN_REQUIRED'];
        }
        
        return $next();
    }
    
    private function isAdmin(int $userId): bool
    {
        // Check if user is admin
        return $userId === 1; // Mock admin check
    }
}

/**
 * CORS Middleware
 */
class CorsMiddleware
{
    public function handle(array $request, callable $next): mixed
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
        
        return $next();
    }
}

/**
 * JSON Response Middleware
 */
class JsonResponseMiddleware
{
    public function handle(array $request, callable $next): mixed
    {
        header('Content-Type: application/json');
        return $next();
    }
}

/**
 * Mock Database Class
 */
class Database
{
    public function __construct(private array $config) {}
    
    public function query(string $sql): array
    {
        return []; // Mock query result
    }
}

/**
 * Mock Logger Class
 */
class Logger
{
    public function __construct(private string $logFile) {}
    
    public function log(string $message): void
    {
        file_put_contents($this->logFile, date('Y-m-d H:i:s') . " - {$message}\n", FILE_APPEND);
    }
}