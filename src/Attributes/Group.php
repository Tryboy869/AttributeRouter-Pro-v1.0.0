<?php

declare(strict_types=1);

namespace AttributeRouter\Attributes;

use Attribute;

/**
 * Group Attribute - Group routes with common prefix/middleware
 * 
 * Usage:
 * #[Group(prefix: '/api', middleware: ['auth'])]
 * #[Group(prefix: '/admin', middleware: ['auth', 'admin'])]
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Group
{
    public readonly ?string $prefix;
    public readonly array $middleware;
    public readonly ?string $name;
    
    /**
     * @param string|null $prefix URI prefix for all routes
     * @param string|array $middleware Common middleware for all routes
     * @param string|null $name Name prefix for all routes
     */
    public function __construct(
        ?string $prefix = null,
        string|array $middleware = [],
        ?string $name = null
    ) {
        $this->prefix = $prefix ? trim($prefix, '/') : null;
        $this->middleware = is_array($middleware) ? $middleware : [$middleware];
        $this->name = $name;
    }
    
    /**
     * Apply group settings to route
     */
    public function applyToRoute(array &$route): void
    {
        // Apply prefix
        if ($this->prefix) {
            $uri = trim($route['uri'], '/');
            $route['uri'] = '/' . $this->prefix . '/' . $uri;
        }
        
        // Merge middleware
        if (!empty($this->middleware)) {
            $route['middleware'] = array_merge(
                $this->middleware,
                $route['middleware'] ?? []
            );
        }
        
        // Apply name prefix
        if ($this->name && !empty($route['name'])) {
            $route['name'] = $this->name . '.' . $route['name'];
        }
    }
}