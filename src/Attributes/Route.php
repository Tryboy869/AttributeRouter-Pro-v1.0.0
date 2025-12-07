<?php

declare(strict_types=1);

namespace AttributeRouter\Attributes;

use Attribute;

/**
 * Route Attribute - Declares HTTP routes on controller methods
 * 
 * Usage:
 * #[Route('GET', '/users')]
 * #[Route('POST', '/users', name: 'users.create')]
 * #[Route('GET', '/users/{id}', where: ['id' => '\d+'])]
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{
    public readonly string $method;
    public readonly string $uri;
    public readonly ?string $name;
    public readonly array $where;
    public readonly ?string $prefix;
    
    /**
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH)
     * @param string $uri Route URI pattern (e.g., '/users/{id}')
     * @param string|null $name Named route for URL generation
     * @param array $where Regex constraints for parameters ['id' => '\d+']
     * @param string|null $prefix URI prefix to prepend
     */
    public function __construct(
        string $method,
        string $uri,
        ?string $name = null,
        array $where = [],
        ?string $prefix = null
    ) {
        $this->method = strtoupper($method);
        $this->uri = $this->normalizeUri($uri);
        $this->name = $name;
        $this->where = $where;
        $this->prefix = $prefix;
    }
    
    /**
     * Get full URI with prefix
     */
    public function getFullUri(): string
    {
        if ($this->prefix) {
            $prefix = trim($this->prefix, '/');
            $uri = trim($this->uri, '/');
            return '/' . $prefix . '/' . $uri;
        }
        
        return $this->uri;
    }
    
    /**
     * Convert URI pattern to regex
     */
    public function toPattern(): string
    {
        $uri = $this->getFullUri();
        
        // No parameters = exact match
        if (strpos($uri, '{') === false) {
            return null; // Will use static matching
        }
        
        // Convert {param} to named capture groups
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            function($matches) {
                $param = $matches[1];
                $constraint = $this->where[$param] ?? '[^/]+';
                return "(?<{$param}>{$constraint})";
            },
            $uri
        );
        
        return '#^' . $pattern . '$#';
    }
    
    private function normalizeUri(string $uri): string
    {
        $uri = trim($uri, '/');
        return $uri === '' ? '/' : '/' . $uri;
    }
}