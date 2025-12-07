# AttributeRouter Pro

> Modern PHP 8.1+ router using Attributes for elegant route declaration. Zero external dependencies, production-ready, high-performance.

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://www.php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![CodeCanyon](https://img.shields.io/badge/CodeCanyon-Premium-orange)](https://codecanyon.net)

## 🚀 Features

- **✨ PHP 8 Attributes** - Declare routes directly on controller methods
- **⚡ Zero Dependencies** - No external libraries required
- **🎯 Auto-Discovery** - Automatically scan controllers for routes
- **🔥 High Performance** - Static route optimization, opcache support
- **🛡️ Middleware System** - Route and global middleware support
- **⏱️ Rate Limiting** - Built-in rate limiter with customizable rules
- **💾 Response Caching** - Cache route responses for performance
- **🎨 Named Routes** - Generate URLs from route names
- **🔍 Route Grouping** - Organize routes with prefixes and shared middleware
- **📊 Debug Mode** - Detailed routing information for development
- **🔒 PSR-7 Compatible** - Works with existing middleware ecosystem

## 📦 Installation

### Via Composer

```bash
composer require nexusstudio/attributerouter-pro
```

### Manual Installation

1. Download the package
2. Extract to your project directory
3. Include the autoloader: `require 'vendor/autoload.php'`

## 🎯 Quick Start

### 1. Create a Controller

```php
<?php

namespace App\Controllers;

use AttributeRouter\Attributes\Route;
use AttributeRouter\Attributes\Middleware;
use AttributeRouter\Attributes\RateLimit;

class UserController
{
    #[Route('GET', '/users')]
    #[RateLimit(100)] // 100 requests per minute
    public function index(): array
    {
        return ['users' => [/* ... */]];
    }
    
    #[Route('GET', '/users/{id}', where: ['id' => '\d+'])]
    public function show(int $id): array
    {
        return ['user' => ['id' => $id]];
    }
    
    #[Route('POST', '/users')]
    #[Middleware(['auth', 'validate'])]
    #[RateLimit(10)] // Stricter limit for writes
    public function store(): array
    {
        return ['message' => 'User created'];
    }
}
```

### 2. Bootstrap the Router

```php
<?php

require 'vendor/autoload.php';

use AttributeRouter\Router;

$router = new Router([
    'controllers_path' => __DIR__ . '/app/Controllers',
    'base_namespace' => 'App\\Controllers',
    'cache_enabled' => true,
]);

// Auto-discover routes
$router->discoverRoutes();

// Run router
$response = $router->run();

// Send response
header('Content-Type: application/json');
echo json_encode($response);
```

### 3. Configure Web Server

#### Apache (.htaccess)

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 📖 Documentation

### Route Declaration

#### Basic Routes

```php
#[Route('GET', '/products')]
public function listProducts() { }

#[Route('POST', '/products')]
public function createProduct() { }
```

#### Dynamic Routes

```php
#[Route('GET', '/products/{id}')]
public function showProduct(int $id) { }

#[Route('GET', '/posts/{slug}')]
public function showPost(string $slug) { }
```

#### Route Constraints

```php
#[Route('GET', '/users/{id}', where: ['id' => '\d+'])]
public function showUser(int $id) { }

#[Route('GET', '/posts/{year}/{month}', where: [
    'year' => '\d{4}',
    'month' => '\d{2}'
])]
public function archive(int $year, int $month) { }
```

#### Named Routes

```php
#[Route('GET', '/users/{id}', name: 'users.show')]
public function showUser(int $id) { }

// Generate URL
$url = $router->url('users.show', ['id' => 123]);
// Returns: /users/123
```

### Middleware

#### Route Middleware

```php
#[Route('POST', '/admin/users')]
#[Middleware(['auth', 'admin'])]
public function createUser() { }
```

#### Class-Level Middleware

```php
#[Middleware('auth')]
class AdminController
{
    #[Route('GET', '/admin/dashboard')]
    public function dashboard() { } // Inherits 'auth' middleware
}
```

#### Custom Middleware

```php
class AuthMiddleware
{
    public function handle(array $request, callable $next): mixed
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            return ['error' => 'Unauthorized'];
        }
        
        return $next();
    }
}

// Register alias
$router->middleware()->alias('auth', AuthMiddleware::class);
```

### Rate Limiting

```php
#[Route('POST', '/api/data')]
#[RateLimit(60)] // 60 requests per minute
public function apiEndpoint() { }

#[Route('POST', '/upload')]
#[RateLimit(10, per: 'hour')] // 10 per hour
public function upload() { }

#[RateLimit(1000, per: 'day', by: 'user_id')] // Per user
public function userAction() { }
```

### Response Caching

```php
#[Route('GET', '/products')]
#[Cache(300)] // Cache for 5 minutes
public function listProducts() { }

#[Route('GET', '/profile')]
#[Cache(1800, tags: ['users'], vary: ['Accept-Language'])]
public function profile() { }
```

### Route Grouping

```php
#[Group(prefix: '/api/v1', middleware: ['cors'])]
class ApiController
{
    #[Route('GET', '/users')] // Becomes /api/v1/users
    public function listUsers() { }
}

#[Group(prefix: '/admin', middleware: ['auth', 'admin'], name: 'admin')]
class AdminController
{
    #[Route('GET', '/dashboard', name: 'dashboard')] 
    // Name becomes: admin.dashboard
    public function dashboard() { }
}
```

### Dependency Injection

```php
class UserController
{
    // Constructor injection
    public function __construct(
        private Database $db,
        private Logger $logger
    ) {}
    
    // Method injection
    #[Route('GET', '/users/{id}')]
    public function show(int $id, Request $request) {
        // $id from route, $request injected
    }
}

// Register dependencies
$router->bind(Database::class, fn() => new Database());
$router->bind(Logger::class, fn() => new Logger());
```

## ⚙️ Configuration

```php
$router = new Router([
    // Controllers directory
    'controllers_path' => __DIR__ . '/app/Controllers',
    
    // Base namespace for controllers
    'base_namespace' => 'App\\Controllers',
    
    // Enable route caching (production)
    'cache_enabled' => true,
    
    // Cache directory
    'cache_path' => __DIR__ . '/cache',
    
    // Debug mode (development)
    'debug' => false,
]);
```

## 🚀 Performance

### Benchmarks

| Feature | Performance |
|---------|-------------|
| Static routes | ~0.01ms |
| Dynamic routes | ~0.05ms |
| With middleware | ~0.08ms |
| With cache | ~0.02ms |

### Production Optimization

```php
// 1. Enable opcache in php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000

// 2. Enable route caching
$router = new Router(['cache_enabled' => true]);

// 3. Precompile routes (optional)
php router.php cache

// 4. Use APCu for rate limiting (recommended)
$router->setRateLimiter(new ApcuRateLimiter());
```

## 🛠️ CLI Tools

```bash
# Scan and list all routes
php router.php scan

# Clear route cache
php router.php clear-cache

# Generate route list
php router.php list

# Validate routes
php router.php validate
```

## 📊 Advanced Usage

### Error Handling

```php
try {
    $response = $router->run();
} catch (RouteNotFoundException $e) {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
} catch (MethodNotAllowedException $e) {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
} catch (RateLimitExceededException $e) {
    // Headers already set
    echo json_encode(['error' => $e->getMessage()]);
}
```

### Custom Route Resolution

```php
$router->addResolver(function($uri, $method) {
    // Custom resolution logic
    if (str_starts_with($uri, '/legacy/')) {
        return ['controller' => LegacyController::class];
    }
});
```

## 🧪 Testing

```php
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function test_route_matching()
    {
        $router = new Router();
        $router->discoverRoutes();
        
        $match = $router->match('GET', '/users/123');
        
        $this->assertNotNull($match);
        $this->assertEquals(123, $match['params']['id']);
    }
}
```

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🤝 Support

For support, email nexusstudio100@gmail.com

## 🎯 Why AttributeRouter Pro?

### vs Laravel Router
- ✅ Zero dependencies (Laravel requires 50+ packages)
- ✅ Standalone (no framework lock-in)
- ✅ Modern attributes syntax (cleaner than arrays)

### vs Symfony Router
- ✅ Simpler configuration
- ✅ Built-in rate limiting
- ✅ Automatic discovery

### vs FastRoute
- ✅ Attributes instead of manual registration
- ✅ Middleware system included
- ✅ Rate limiting included

## 🔥 Pro Tips

1. **Use route caching in production** - 10x faster route resolution
2. **Group routes by module** - Easier maintenance
3. **Leverage middleware for cross-cutting concerns** - DRY principle
4. **Use named routes for URL generation** - Refactoring-friendly
5. **Enable debug mode in development** - Catch issues early

## 📈 Roadmap

- [x] PHP 8.1 Attributes support
- [x] Rate limiting
- [x] Response caching
- [x] Middleware system
- [ ] OpenAPI documentation generation
- [ ] GraphQL support
- [ ] WebSocket routing
- [ ] Route versioning

---

**Made with ❤️ by Nexus Studio**

*Star this project if you find it useful!*
