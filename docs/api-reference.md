# API Reference

Complete API documentation for AttributeRouter Pro.

## Router Class

### Constructor

```php
public function __construct(array $config = [])
```

**Parameters:**
- `$config` (array): Configuration options

**Configuration Options:**

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `controllers_path` | string | Required | Path to controllers directory |
| `base_namespace` | string | Required | Base namespace for controllers |
| `cache_enabled` | bool | `false` | Enable route caching |
| `cache_path` | string | `__DIR__ . '/../cache'` | Cache directory path |
| `debug` | bool | `false` | Enable debug mode |

**Example:**
```php
$router = new Router([
    'controllers_path' => __DIR__ . '/app/Controllers',
    'base_namespace' => 'App\\Controllers',
    'cache_enabled' => true,
]);
```

---

### discoverRoutes()

```php
public function discoverRoutes(string $path = null, string $namespace = null): self
```

Scans controllers directory for route attributes.

**Parameters:**
- `$path` (string|null): Optional custom path to scan
- `$namespace` (string|null): Optional custom namespace

**Returns:** `self` (for method chaining)

**Example:**
```php
$router->discoverRoutes();
```

---

### match()

```php
public function match(string $method, string $uri): ?array
```

Matches HTTP request to a route.

**Parameters:**
- `$method` (string): HTTP method (GET, POST, etc.)
- `$uri` (string): Request URI

**Returns:** Array with route match data or throws exception

**Throws:**
- `RouteNotFoundException` - No matching route
- `MethodNotAllowedException` - Route exists but method not allowed

**Example:**
```php
try {
    $match = $router->match('GET', '/users/123');
} catch (RouteNotFoundException $e) {
    // Handle 404
}
```

---

### dispatch()

```php
public function dispatch(array $match): mixed
```

Executes matched route.

**Parameters:**
- `$match` (array): Route match data from `match()`

**Returns:** Mixed - Controller method return value

**Example:**
```php
$match = $router->match('GET', '/users');
$response = $router->dispatch($match);
```

---

### run()

```php
public function run(): mixed
```

Convenience method: matches and dispatches current HTTP request.

**Returns:** Mixed - Controller method return value

**Example:**
```php
$response = $router->run();
echo json_encode($response);
```

---

### url()

```php
public function url(string $name, array $params = []): string
```

Generates URL from named route.

**Parameters:**
- `$name` (string): Route name
- `$params` (array): Route parameters

**Returns:** string - Generated URL

**Throws:** `RuntimeException` - If route not found or missing params

**Example:**
```php
$url = $router->url('users.show', ['id' => 123]);
// Returns: /users/123
```

---

### middleware()

```php
public function middleware(string|array $middleware): self
```

Registers global middleware.

**Parameters:**
- `$middleware` (string|array): Middleware class(es)

**Returns:** `self`

**Example:**
```php
$router->middleware(['cors', 'auth']);
```

---

### clearCache()

```php
public function clearCache(): bool
```

Clears route cache.

**Returns:** bool - Success status

**Example:**
```php
$router->clearCache();
```

---

## Attributes

### Route

```php
#[Route(
    string $method,
    string $uri,
    ?string $name = null,
    array $where = [],
    ?string $prefix = null
)]
```

Declares HTTP route on controller method.

**Parameters:**
- `$method` - HTTP method (GET, POST, PUT, DELETE, PATCH)
- `$uri` - Route URI pattern
- `$name` - Optional route name
- `$where` - Parameter constraints (regex)
- `$prefix` - URI prefix

**Example:**
```php
#[Route('GET', '/users/{id}', name: 'users.show', where: ['id' => '\d+'])]
public function show(int $id) {}
```

---

### Middleware

```php
#[Middleware(
    string|array $middleware,
    int $priority = 0
)]
```

Attaches middleware to route or class.

**Parameters:**
- `$middleware` - Middleware class(es) or alias(es)
- `$priority` - Execution priority (higher = earlier)

**Example:**
```php
#[Middleware(['auth', 'admin'], priority: 10)]
public function adminAction() {}
```

---

### RateLimit

```php
#[RateLimit(
    int $maxAttempts,
    string $per = 'minute',
    string $by = 'ip'
)]
```

Rate limiting for routes.

**Parameters:**
- `$maxAttempts` - Maximum requests allowed
- `$per` - Time period ('second', 'minute', 'hour', 'day')
- `$by` - Rate limit key ('ip', 'user_id', 'api_key')

**Example:**
```php
#[RateLimit(60, per: 'minute')]
public function apiEndpoint() {}
```

---

### Cache

```php
#[Cache(
    int $ttl,
    array $tags = [],
    array $vary = [],
    ?string $key = null
)]
```

Response caching for routes.

**Parameters:**
- `$ttl` - Time to live in seconds
- `$tags` - Cache tags for invalidation
- `$vary` - HTTP headers to vary cache by
- `$key` - Custom cache key

**Example:**
```php
#[Cache(300, tags: ['products'], vary: ['Accept-Language'])]
public function products() {}
```

---

### Group

```php
#[Group(
    ?string $prefix = null,
    string|array $middleware = [],
    ?string $name = null
)]
```

Groups routes with common settings (class-level only).

**Parameters:**
- `$prefix` - URI prefix for all routes
- `$middleware` - Common middleware
- `$name` - Name prefix

**Example:**
```php
#[Group(prefix: '/api/v1', middleware: ['auth'], name: 'api')]
class ApiController {}
```

---

## Exceptions

### RouteNotFoundException

Thrown when no route matches the request.

**HTTP Code:** 404

**Example:**
```php
try {
    $router->run();
} catch (RouteNotFoundException $e) {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
}
```

---

### MethodNotAllowedException

Thrown when route exists but HTTP method not allowed.

**HTTP Code:** 405

---

### RateLimitExceededException

Thrown when rate limit exceeded.

**HTTP Code:** 429

**Headers Set:**
- `X-RateLimit-Limit`
- `X-RateLimit-Remaining`
- `X-RateLimit-Reset`
- `Retry-After`

---

## MiddlewareManager

### alias()

```php
public function alias(string $name, string $class): void
```

Registers middleware alias.

**Example:**
```php
$router->middleware()->alias('auth', AuthMiddleware::class);
```

---

### addGlobal()

```php
public function addGlobal(string $middleware): void
```

Adds global middleware.

**Example:**
```php
$router->middleware()->addGlobal('cors');
```

---

## RouteDispatcher

### bind()

```php
public function bind(string $class, callable|object $resolver): void
```

Registers dependency for injection.

**Example:**
```php
$router->bind(Database::class, fn() => new Database());
```

---

## CLI Commands

### scan

Scans and lists all routes.

```bash
php router.php scan
```

### cache

Generates route cache.

```bash
php router.php cache
```

### clear-cache

Clears route cache.

```bash
php router.php clear-cache
```

### list

Lists routes in table format.

```bash
php router.php list
```

### validate

Validates all routes.

```bash
php router.php validate
```

---

## See Also

- [Quick Start](quick-start.md)
- [Advanced Usage](advanced-usage.md)
- [Examples](/examples)