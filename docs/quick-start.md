# Quick Start Guide

Get up and running with AttributeRouter Pro in 5 minutes.

## Step 1: Basic Setup

Create a simple controller:

```php
<?php
namespace App\Controllers;

use AttributeRouter\Attributes\Route;

class UserController
{
    #[Route('GET', '/users')]
    public function index(): array
    {
        return ['users' => ['John', 'Jane']];
    }
}
```

## Step 2: Bootstrap Router

```php
<?php
require 'vendor/autoload.php';

use AttributeRouter\Router;

$router = new Router([
    'controllers_path' => __DIR__ . '/app/Controllers',
    'base_namespace' => 'App\\Controllers',
]);

$router->discoverRoutes();
$response = $router->run();

header('Content-Type: application/json');
echo json_encode($response);
```

## Step 3: Dynamic Routes

Add dynamic parameters:

```php
#[Route('GET', '/users/{id}', where: ['id' => '\d+'])]
public function show(int $id): array
{
    return ['user' => ['id' => $id, 'name' => 'John']];
}
```

## Step 4: Add Middleware

Protect routes with middleware:

```php
#[Route('POST', '/users')]
#[Middleware('auth')]
public function store(): array
{
    return ['message' => 'User created'];
}
```

## Step 5: Rate Limiting

Add rate limits:

```php
#[Route('GET', '/api/data')]
#[RateLimit(60)] // 60 requests per minute
public function data(): array
{
    return ['data' => 'sensitive'];
}
```

## Step 6: Response Caching

Cache expensive operations:

```php
#[Route('GET', '/products')]
#[Cache(300)] // Cache for 5 minutes
public function products(): array
{
    return ['products' => $this->fetchFromDatabase()];
}
```

## Step 7: Named Routes

Generate URLs from routes:

```php
#[Route('GET', '/users/{id}', name: 'users.show')]
public function show(int $id): array
{
    return ['user' => ['id' => $id]];
}

// Generate URL
$url = $router->url('users.show', ['id' => 123]);
// Returns: /users/123
```

## Step 8: Route Grouping

Organize routes with prefixes:

```php
#[Group(prefix: '/api/v1', middleware: ['auth'])]
class ApiController
{
    #[Route('GET', '/users')] // Becomes /api/v1/users
    public function users(): array
    {
        return ['users' => []];
    }
}
```

## Common Patterns

### RESTful Controller

```php
class ProductController
{
    #[Route('GET', '/products', name: 'products.index')]
    public function index() {}
    
    #[Route('GET', '/products/{id}', name: 'products.show')]
    public function show(int $id) {}
    
    #[Route('POST', '/products', name: 'products.store')]
    #[Middleware('auth')]
    public function store() {}
    
    #[Route('PUT', '/products/{id}', name: 'products.update')]
    #[Middleware('auth')]
    public function update(int $id) {}
    
    #[Route('DELETE', '/products/{id}', name: 'products.destroy')]
    #[Middleware(['auth', 'admin'])]
    public function destroy(int $id) {}
}
```

### API Endpoint with Full Features

```php
#[Route('POST', '/api/orders')]
#[Middleware(['auth:api', 'validate'])]
#[RateLimit(20, per: 'minute')]
#[Cache(0)] // No cache for POST
public function createOrder(): array
{
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Process order
    $order = $this->orderService->create($data);
    
    http_response_code(201);
    return [
        'message' => 'Order created successfully',
        'data' => $order
    ];
}
```

## CLI Commands

Useful commands:

```bash
# Scan and list routes
php router.php scan

# Generate route cache
php router.php cache

# Clear cache
php router.php clear-cache

# Validate routes
php router.php validate
```

## Next Steps

- Read [Advanced Usage](advanced-usage.md) for complex patterns
- Check [API Reference](api-reference.md) for complete documentation
- Explore `/examples` for real-world code

## Tips

1. Use route caching in production for 10x speed
2. Group routes by feature/module
3. Use named routes for URL generation
4. Apply rate limiting to public APIs
5. Enable debug mode during development