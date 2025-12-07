<?php

declare(strict_types=1);

namespace AttributeRouter\Attributes;

use Attribute;

/**
 * Middleware Attribute - Attach middleware to routes
 * 
 * Usage:
 * #[Middleware('auth')]
 * #[Middleware(['auth', 'admin'])]
 * #[Middleware('auth:api', priority: 10)]
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Middleware
{
    public readonly array $middleware;
    public readonly int $priority;
    
    /**
     * @param string|array $middleware Middleware class(es) or name(s)
     * @param int $priority Execution priority (higher = earlier)
     */
    public function __construct(
        string|array $middleware,
        int $priority = 0
    ) {
        $this->middleware = is_array($middleware) ? $middleware : [$middleware];
        $this->priority = $priority;
    }
    
    /**
     * Get all middleware in priority order
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }
}