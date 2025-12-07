# Advanced Usage

## Custom Middleware

### Creating Middleware

```php
class LoggingMiddleware
{
    public function handle(array $request, callable $next): mixed
    {
        $start = microtime(true);
        
        // Before request
        $this->logRequest();
        
        // Execute next middleware/controller
        $response = $next();
        
        // After response
        $duration = (microtime(true) - $start) * 1000;
        $this->logResponse($duration);
        
        return $response;
    }
    
    private function logRequest(): void
    {
        error_log(sprintf(
            "[%s] %s %s",
            date('Y-m-d H:i:s'),
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI']
        ));
    }
    
    private function logResponse(float $duration): void
    {
        error_log(sprintf("Request completed in %.2fms", $duration));
    }
}
```

### Registering Middleware

```php
// Register alias
$router->middleware()->alias('log', LoggingMiddleware::class);

// Use in controller
#[Route('GET', '/users')]
#[Middleware('log')]
public function index() {}
```

## Dependency Injection

### Binding Dependencies

```php
// Bind interface to implementation
$router->bind(UserRepositoryInterface::class, function() {
    return new DatabaseUserRepository(
        new PDO('mysql:host=localhost;dbname=myapp', 'root', '')
    );
});

// Bind singleton
$router->bind(Logger::class, function() {
    static $logger = null;
    return $logger ??= new FileLogger(__DIR__ . '/logs/app.log');
});
```

### Using in Controllers

```php
class UserController
{
    // Constructor injection
    public function __construct(
        private UserRepositoryInterface $users,
        private Logger $logger
    ) {}
    
    #[Route('GET', '/users/{id}')]
    public function show(int $id): array
    {
        $this->logger->info("Fetching user {$id}");
        $user = $this->users->find($id);
        
        return ['data' => $user];
    }
}
```

## Advanced Rate Limiting

### Custom Rate Limiter

```php
use AttributeRouter\RateLimiter;

class RedisRateLimiter extends RateLimiter
{
    private Redis $redis;
    
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }
    
    public function check(RateLimit $config): void
    {
        $key = $config->getKey();
        $limit = $config->maxAttempts;
        $window = $config->getDecaySeconds();
        
        $current = (int) $this->redis->get($key);
        
        if ($current >= $limit) {
            throw new RateLimitExceededException();
        }
        
        $this->redis->incr($key);
        $this->redis->expire($key, $window);
    }
}

// Use custom limiter
$router->setRateLimiter(new RedisRateLimiter($redis));
```

### Per-User Rate Limiting

```php
#[Route('POST', '/api/upload')]
#[RateLimit(10, per: 'hour', by: 'user_id')]
public function upload(): array
{
    // Rate limited per authenticated user
}
```

## Response Caching

### Cache with Tags

```php
#[Route('GET', '/products/{category}')]
#[Cache(600, tags: ['products', 'categories'])]
public function byCategory(string $category): array
{
    return ['products' => $this->products->findByCategory($category)];
}

// Invalidate cache by tag
$cache->invalidateTag('products');
```

### Vary by Headers

```php
#[Route('GET', '/content')]
#[Cache(300, vary: ['Accept-Language', 'Accept-Encoding'])]
public function content(): array
{
    // Different cache per language/encoding
}
```

## Route Constraints

### Multiple Constraints

```php
#[Route('GET', '/posts/{year}/{month}/{slug}', where: [
    'year' => '\d{4}',
    'month' => '(0[1-9]|1[0-2])',
    'slug' => '[a-z0-9-]+'
])]
public function showPost(int $year, int $month, string $slug): array
{
    return ['post' => $this->posts->find($year, $month, $slug)];
}
```

## Error Handling

### Custom Error Handler

```php
class ErrorHandler
{
    public function handle(\Throwable $e): array
    {
        match(true) {
            $e instanceof ValidationException => $this->validationError($e),
            $e instanceof AuthException => $this->authError($e),
            $e instanceof NotFoundException => $this->notFoundError($e),
            default => $this->genericError($e)
        };
    }
    
    private function validationError(ValidationException $e): void
    {
        http_response_code(422);
        echo json_encode([
            'error' => 'Validation Failed',
            'errors' => $e->getErrors()
        ]);
    }
}
```

## Testing

### Unit Testing Routes

```php
use PHPUnit\Framework\TestCase;
use AttributeRouter\Router;

class RouterTest extends TestCase
{
    private Router $router;
    
    protected function setUp(): void
    {
        $this->router = new Router([
            'controllers_path' => __DIR__ . '/Controllers',
            'base_namespace' => 'Tests\\Controllers',
        ]);
        
        $this->router->discoverRoutes();
    }
    
    public function test_route_matches_correctly(): void
    {
        $match = $this->router->match('GET', '/users/123');
        
        $this->assertNotNull($match);
        $this->assertEquals(123, $match['params']['id']);
    }
    
    public function test_middleware_is_applied(): void
    {
        $match = $this->router->match('POST', '/admin/users');
        
        $this->assertContains('auth', $match['middleware']);
        $this->assertContains('admin', $match['middleware']);
    }
}
```

### Integration Testing

```php
public function test_full_request_cycle(): void
{
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/users/123';
    
    $response = $this->router->run();
    
    $this->assertArrayHasKey('data', $response);
    $this->assertEquals(123, $response['data']['id']);
}
```

## Performance Optimization

### Route Caching

```php
// Enable in production
$router = new Router([
    'cache_enabled' => true,
    'cache_path' => __DIR__ . '/cache',
]);

// Precompile routes
php router.php cache
```

### Opcache Configuration

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  ; Production only
```

### APCu for Rate Limiting

```php
class ApcuRateLimiter extends RateLimiter
{
    protected function get(string $key): ?array
    {
        $data = apcu_fetch($key);
        return $data ?: null;
    }
    
    protected function set(string $key, array $data): void
    {
        apcu_store($key, $data, $data['expires_at'] - time());
    }
}
```

## API Versioning

### Version in URL

```php
#[Group(prefix: '/api/v1')]
class V1ApiController
{
    #[Route('GET', '/users')]
    public function users() {}
}

#[Group(prefix: '/api/v2')]
class V2ApiController
{
    #[Route('GET', '/users')]
    public function users() {}
}
```

### Version in Header

```php
class VersionMiddleware
{
    public function handle(array $request, callable $next): mixed
    {
        $version = $_SERVER['HTTP_API_VERSION'] ?? 'v1';
        $_SERVER['API_VERSION'] = $version;
        
        return $next();
    }
}
```

## See Also

- [Quick Start](quick-start.md)
- [API Reference](api-reference.md)
- [Examples](/examples)