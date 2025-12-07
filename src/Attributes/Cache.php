<?php

declare(strict_types=1);

namespace AttributeRouter\Attributes;

use Attribute;

/**
 * Cache Attribute - Response caching for routes
 * 
 * Usage:
 * #[Cache(300)]  // Cache for 5 minutes
 * #[Cache(3600, tags: ['products'])]
 * #[Cache(600, vary: ['Accept-Language'])]
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Cache
{
    public readonly int $ttl;
    public readonly array $tags;
    public readonly array $vary;
    public readonly ?string $key;
    
    /**
     * @param int $ttl Time to live in seconds
     * @param array $tags Cache tags for invalidation
     * @param array $vary HTTP headers to vary cache by
     * @param string|null $key Custom cache key (default: auto-generated)
     */
    public function __construct(
        int $ttl,
        array $tags = [],
        array $vary = [],
        ?string $key = null
    ) {
        $this->ttl = $ttl;
        $this->tags = $tags;
        $this->vary = $vary;
        $this->key = $key;
    }
    
    /**
     * Generate cache key for current request
     */
    public function getCacheKey(): string
    {
        if ($this->key) {
            return $this->key;
        }
        
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        $varyParts = [];
        foreach ($this->vary as $header) {
            $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
            $varyParts[] = $_SERVER[$headerKey] ?? '';
        }
        
        $keyParts = array_merge([$method, $uri], $varyParts);
        return 'cache:' . md5(implode(':', $keyParts));
    }
}