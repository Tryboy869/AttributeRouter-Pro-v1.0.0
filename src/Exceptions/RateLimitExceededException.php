<?php

declare(strict_types=1);

namespace AttributeRouter\Exceptions;

/**
 * RateLimitExceededException
 * 
 * Thrown when a user exceeds the configured rate limit for a route.
 * The RateLimiter automatically sets appropriate HTTP headers:
 * - X-RateLimit-Limit: Maximum requests allowed
 * - X-RateLimit-Remaining: Requests remaining
 * - X-RateLimit-Reset: Timestamp when limit resets
 * - Retry-After: Seconds to wait before retrying
 * 
 * Returns HTTP 429 status code.
 * 
 * @package AttributeRouter
 * @author Nexus Studio <nexusstudio100@gmail.com>
 */
class RateLimitExceededException extends \Exception
{
    /**
     * Create a new RateLimitExceededException instance
     * 
     * @param string $message Exception message
     * @param int $code HTTP status code (default: 429)
     */
    public function __construct(
        string $message = "Rate limit exceeded",
        int $code = 429
    ) {
        parent::__construct($message, $code);
    }
}