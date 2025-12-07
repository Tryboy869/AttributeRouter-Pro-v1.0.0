<?php

declare(strict_types=1);

namespace AttributeRouter;

use AttributeRouter\Exceptions\RouteNotFoundException;
use AttributeRouter\Exceptions\MethodNotAllowedException;

/**
 * AttributeRouter Pro - Core Routing Engine
 * 
 * Modern PHP 8.1+ router using Attributes for route declaration.
 * Zero external dependencies, production-ready, high-performance.
 * 
 * @version 1.0.0
 * @author Nexus Studio <nexusstudio100@gmail.com>
 * @license MIT
 */
class Router
{
    private RouteCollection $routes;
    private RouteDiscovery $discovery;
    private RouteDispatcher $dispatcher;
    private MiddlewareManager $middleware;
    private array $config;
    private bool $cacheEnabled = false;
    private ?string $cacheFile = null;
    
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'cache_enabled' => false,
            'cache_path' => __DIR__ . '/../cache',
            'debug' => false,
            'controllers_path' => __DIR__ . '/../app/Controllers',
            'base_namespace' => 'App\\Controllers',
        ], $config);
        
        $this->routes = new RouteCollection();
        $this->discovery = new RouteDiscovery($this->routes);
        $this->dispatcher = new RouteDispatcher();
        $this->middleware = new MiddlewareManager();
        
        $this->cacheEnabled = $this->config['cache_enabled'];
        $this->cacheFile = $this->config['cache_path'] . '/routes.cache';
    }
    
    /**
     * Discover routes from controllers directory
     */
    public function discoverRoutes(string $path = null, string $namespace = null): self
    {
        $path = $path ?? $this->config['controllers_path'];
        $namespace = $namespace ?? $this->config['base_namespace'];
        
        // Try load from cache in production
        if ($this->cacheEnabled && $this->loadFromCache()) {
            return $this;
        }
        
        // Scan controllers and discover routes
        $this->discovery->scanDirectory($path, $namespace);
        
        // Save to cache if enabled
        if ($this->cacheEnabled) {
            $this->saveToCache();
        }
        
        return $this;
    }
    
    /**
     * Match incoming request to a route
     */
    public function match(string $method, string $uri): ?array
    {
        $uri = $this->normalizeUri($uri);
        $method = strtoupper($method);
        
        // Try exact match first (fastest)
        $route = $this->routes->findExact($method, $uri);
        if ($route) {
            return $this->prepareMatch($route, []);
        }
        
        // Try dynamic routes
        foreach ($this->routes->getDynamic($method) as $route) {
            $params = $this->matchDynamic($route['pattern'], $uri);
            if ($params !== null) {
                return $this->prepareMatch($route, $params);
            }
        }
        
        // Check if URI exists with different method
        if ($this->routes->uriExists($uri)) {
            throw new MethodNotAllowedException(
                "Method $method not allowed for $uri"
            );
        }
        
        throw new RouteNotFoundException("Route not found: $method $uri");
    }
    
    /**
     * Dispatch matched route
     */
    public function dispatch(array $match): mixed
    {
        $startTime = microtime(true);
        
        try {
            // Execute middleware chain
            $response = $this->middleware->execute(
                $match['middleware'] ?? [],
                function() use ($match) {
                    return $this->dispatcher->dispatch(
                        $match['controller'],
                        $match['method'],
                        $match['params']
                    );
                }
            );
            
            if ($this->config['debug']) {
                $execTime = (microtime(true) - $startTime) * 1000;
                $this->logDebug($match, $execTime);
            }
            
            return $response;
            
        } catch (\Throwable $e) {
            return $this->handleError($e);
        }
    }
    
    /**
     * Run router (match + dispatch)
     */
    public function run(): mixed
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        $match = $this->match($method, $uri);
        return $this->dispatch($match);
    }
    
    /**
     * Generate URL from named route
     */
    public function url(string $name, array $params = []): string
    {
        $route = $this->routes->findByName($name);
        if (!$route) {
            throw new \RuntimeException("Named route not found: $name");
        }
        
        $uri = $route['uri'];
        
        // Replace parameters
        foreach ($params as $key => $value) {
            $uri = str_replace("{{$key}}", (string)$value, $uri);
        }
        
        // Check for missing params
        if (preg_match('/\{[^}]+\}/', $uri)) {
            throw new \RuntimeException("Missing route parameters for $name");
        }
        
        return $uri;
    }
    
    /**
     * Register global middleware
     */
    public function middleware(string|array $middleware): self
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        foreach ($middlewares as $mw) {
            $this->middleware->addGlobal($mw);
        }
        return $this;
    }
    
    /**
     * Get all registered routes
     */
    public function getRoutes(): array
    {
        return $this->routes->all();
    }
    
    /**
     * Clear route cache
     */
    public function clearCache(): bool
    {
        if (file_exists($this->cacheFile)) {
            return unlink($this->cacheFile);
        }
        return true;
    }
    
    // ==================== PRIVATE METHODS ====================
    
    private function normalizeUri(string $uri): string
    {
        $uri = trim($uri, '/');
        return $uri === '' ? '/' : '/' . $uri;
    }
    
    private function matchDynamic(string $pattern, string $uri): ?array
    {
        if (!preg_match($pattern, $uri, $matches)) {
            return null;
        }
        
        // Extract named parameters
        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }
        
        return $params;
    }
    
    private function prepareMatch(array $route, array $params): array
    {
        return [
            'controller' => $route['controller'],
            'method' => $route['method'],
            'params' => $params,
            'middleware' => $route['middleware'] ?? [],
            'name' => $route['name'] ?? null,
            'cache' => $route['cache'] ?? null,
            'rateLimit' => $route['rateLimit'] ?? null,
        ];
    }
    
    private function loadFromCache(): bool
    {
        if (!file_exists($this->cacheFile)) {
            return false;
        }
        
        try {
            $cached = require $this->cacheFile;
            $this->routes->loadFromArray($cached);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    private function saveToCache(): void
    {
        $cacheDir = dirname($this->cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $data = var_export($this->routes->toArray(), true);
        $content = "<?php\nreturn {$data};";
        
        file_put_contents($this->cacheFile, $content, LOCK_EX);
    }
    
    private function logDebug(array $match, float $execTime): void
    {
        error_log(sprintf(
            "[AttributeRouter] %s %s -> %s::%s (%.2fms)",
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI'],
            $match['controller'],
            $match['method'],
            $execTime
        ));
    }
    
    private function handleError(\Throwable $e): mixed
    {
        if ($this->config['debug']) {
            throw $e;
        }
        
        // Production error handling
        http_response_code(500);
        return [
            'error' => 'Internal Server Error',
            'message' => $e->getMessage(),
        ];
    }
}