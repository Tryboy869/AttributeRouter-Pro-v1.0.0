<?php

declare(strict_types=1);

namespace AttributeRouter;

/**
 * Route storage and retrieval system
 * Optimized for fast lookups with separate static/dynamic collections
 */
class RouteCollection
{
    private array $static = [];  // Exact matches: method => [uri => route]
    private array $dynamic = []; // Dynamic matches: method => [route1, route2...]
    private array $named = [];   // Named routes: name => route
    private array $all = [];     // All routes for listing
    
    /**
     * Add a route to collection
     */
    public function add(array $route): void
    {
        $method = strtoupper($route['method']);
        $uri = $route['uri'];
        
        // Store in appropriate collection
        if ($this->isStatic($uri)) {
            $this->static[$method][$uri] = $route;
        } else {
            $this->dynamic[$method][] = $route;
        }
        
        // Store named route
        if (!empty($route['name'])) {
            $this->named[$route['name']] = $route;
        }
        
        // Store in all collection
        $this->all[] = $route;
    }
    
    /**
     * Find exact static route
     */
    public function findExact(string $method, string $uri): ?array
    {
        return $this->static[$method][$uri] ?? null;
    }
    
    /**
     * Get all dynamic routes for method
     */
    public function getDynamic(string $method): array
    {
        return $this->dynamic[$method] ?? [];
    }
    
    /**
     * Find route by name
     */
    public function findByName(string $name): ?array
    {
        return $this->named[$name] ?? null;
    }
    
    /**
     * Check if URI exists with any method
     */
    public function uriExists(string $uri): bool
    {
        foreach ($this->static as $routes) {
            if (isset($routes[$uri])) {
                return true;
            }
        }
        
        foreach ($this->dynamic as $routes) {
            foreach ($routes as $route) {
                if (preg_match($route['pattern'], $uri)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get all routes
     */
    public function all(): array
    {
        return $this->all;
    }
    
    /**
     * Convert to array for caching
     */
    public function toArray(): array
    {
        return [
            'static' => $this->static,
            'dynamic' => $this->dynamic,
            'named' => $this->named,
            'all' => $this->all,
        ];
    }
    
    /**
     * Load from cached array
     */
    public function loadFromArray(array $data): void
    {
        $this->static = $data['static'] ?? [];
        $this->dynamic = $data['dynamic'] ?? [];
        $this->named = $data['named'] ?? [];
        $this->all = $data['all'] ?? [];
    }
    
    /**
     * Count total routes
     */
    public function count(): int
    {
        return count($this->all);
    }
    
    /**
     * Clear all routes
     */
    public function clear(): void
    {
        $this->static = [];
        $this->dynamic = [];
        $this->named = [];
        $this->all = [];
    }
    
    /**
     * Get routes grouped by method
     */
    public function byMethod(): array
    {
        $grouped = [];
        
        foreach ($this->all as $route) {
            $method = $route['method'];
            $grouped[$method][] = $route;
        }
        
        return $grouped;
    }
    
    /**
     * Check if URI is static (no parameters)
     */
    private function isStatic(string $uri): bool
    {
        return strpos($uri, '{') === false;
    }
}