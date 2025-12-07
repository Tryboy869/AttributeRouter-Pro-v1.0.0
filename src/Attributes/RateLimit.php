<?php

declare(strict_types=1);

namespace AttributeRouter\Attributes;

use Attribute;

/**
 * RateLimit Attribute - Rate limiting for routes
 * 
 * Usage:
 * #[RateLimit(60)]  // 60 requests per minute
 * #[RateLimit(100, per: 'hour')]
 * #[RateLimit(1000, per: 'day', by: 'user_id')]
 */
#[Attribute(Attribute::TARGET_METHOD)]
class RateLimit
{
    public readonly int $maxAttempts;
    public readonly string $per;
    public readonly string $by;
    
    /**
     * @param int $maxAttempts Maximum requests allowed
     * @param string $per Time period ('minute', 'hour', 'day')
     * @param string $by Rate limit key ('ip', 'user_id', 'api_key')
     */
    public function __construct(
        int $maxAttempts,
        string $per = 'minute',
        string $by = 'ip'
    ) {
        $this->maxAttempts = $maxAttempts;
        $this->per = $per;
        $this->by = $by;
    }
    
    /**
     * Get decay time in seconds
     */
    public function getDecaySeconds(): int
    {
        return match($this->per) {
            'second' => 1,
            'minute' => 60,
            'hour' => 3600,
            'day' => 86400,
            default => 60
        };
    }
    
    /**
     * Get rate limit key for current request
     */
    public function getKey(): string
    {
        $identifier = match($this->by) {
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? 'guest',
            'api_key' => $_SERVER['HTTP_X_API_KEY'] ?? 'none',
            default => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        };
        
        return sprintf('ratelimit:%s:%s', $this->by, $identifier);
    }
}