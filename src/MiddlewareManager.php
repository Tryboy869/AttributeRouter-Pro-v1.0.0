<?php

declare(strict_types=1);

namespace AttributeRouter;

/**
 * Middleware execution pipeline
 * Manages global and route-specific middleware
 */
class MiddlewareManager
{
    private array $global = [];
    private array $aliases = [];
    
    /**
     * Register global middleware
     */
    public function addGlobal(string $middleware): void
    {
        $this->global[] = $middleware;
    }
    
    /**
     * Register middleware alias
     */
    public function alias(string $name, string $class): void
    {
        $this->aliases[$name] = $class;
    }
    
    /**
     * Execute middleware chain
     */
    public function execute(array $middleware, callable $final): mixed
    {
        // Merge global + route middleware
        $chain = array_merge($this->global, $middleware);
        
        // Resolve aliases
        $chain = array_map(
            fn($mw) => $this->resolve($mw),
            $chain
        );
        
        // Build pipeline
        return $this->buildPipeline($chain, $final)();
    }
    
    /**
     * Build middleware pipeline (onion layers)
     */
    private function buildPipeline(array $middleware, callable $core): callable
    {
        return array_reduce(
            array_reverse($middleware),
            function($next, $mw) {
                return function() use ($mw, $next) {
                    return $this->call($mw, $next);
                };
            },
            $core
        );
    }
    
    /**
     * Call single middleware
     */
    private function call(callable|string $middleware, callable $next): mixed
    {
        if (is_string($middleware)) {
            if (!class_exists($middleware)) {
                throw new \RuntimeException(
                    "Middleware class not found: {$middleware}"
                );
            }
            
            $middleware = new $middleware();
        }
        
        // Standard middleware interface: handle($request, $next)
        if (method_exists($middleware, 'handle')) {
            return $middleware->handle($_REQUEST, $next);
        }
        
        // Callable middleware
        if (is_callable($middleware)) {
            return $middleware($_REQUEST, $next);
        }
        
        throw new \RuntimeException(
            "Invalid middleware: must implement handle() or be callable"
        );
    }
    
    /**
     * Resolve middleware name to class
     */
    private function resolve(string $name): string
    {
        // Check if it's an alias
        if (isset($this->aliases[$name])) {
            return $this->aliases[$name];
        }
        
        // Check for parameters (e.g., 'auth:api')
        if (str_contains($name, ':')) {
            [$alias, $params] = explode(':', $name, 2);
            
            if (isset($this->aliases[$alias])) {
                // Store params for middleware access
                $_SERVER['MIDDLEWARE_PARAMS'] = $params;
                return $this->aliases[$alias];
            }
        }
        
        // Assume it's a class name
        return $name;
    }
}