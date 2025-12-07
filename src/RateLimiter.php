<?php

declare(strict_types=1);

namespace AttributeRouter;

use AttributeRouter\Attributes\RateLimit;
use AttributeRouter\Exceptions\RateLimitExceededException;

/**
 * Rate limiting implementation
 * Uses file-based storage (can be extended for Redis/Memcached)
 */
class RateLimiter
{
    private string $storagePath;
    
    public function __construct(string $storagePath = null)
    {
        $this->storagePath = $storagePath ?? sys_get_temp_dir() . '/attributerouter_ratelimit';
        
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }
    
    /**
     * Check if rate limit exceeded
     */
    public function check(RateLimit $config): void
    {
        $key = $config->getKey();
        $maxAttempts = $config->maxAttempts;
        $decaySeconds = $config->getDecaySeconds();
        
        $data = $this->get($key);
        $now = time();
        
        // First request or expired window
        if (!$data || $data['expires_at'] < $now) {
            $this->set($key, [
                'attempts' => 1,
                'expires_at' => $now + $decaySeconds,
            ]);
            return;
        }
        
        // Increment attempts
        $data['attempts']++;
        
        // Check if exceeded
        if ($data['attempts'] > $maxAttempts) {
            $retryAfter = $data['expires_at'] - $now;
            
            http_response_code(429);
            header("Retry-After: {$retryAfter}");
            header("X-RateLimit-Limit: {$maxAttempts}");
            header("X-RateLimit-Remaining: 0");
            header("X-RateLimit-Reset: {$data['expires_at']}");
            
            throw new RateLimitExceededException(
                "Rate limit exceeded. Try again in {$retryAfter} seconds."
            );
        }
        
        // Update attempts
        $this->set($key, $data);
        
        // Set rate limit headers
        $remaining = $maxAttempts - $data['attempts'];
        header("X-RateLimit-Limit: {$maxAttempts}");
        header("X-RateLimit-Remaining: {$remaining}");
        header("X-RateLimit-Reset: {$data['expires_at']}");
    }
    
    /**
     * Get rate limit data
     */
    private function get(string $key): ?array
    {
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($file), true);
        return $data ?: null;
    }
    
    /**
     * Set rate limit data
     */
    private function set(string $key, array $data): void
    {
        $file = $this->getFilePath($key);
        file_put_contents($file, json_encode($data), LOCK_EX);
    }
    
    /**
     * Get file path for key
     */
    private function getFilePath(string $key): string
    {
        return $this->storagePath . '/' . md5($key) . '.json';
    }
    
    /**
     * Clear expired rate limits (cleanup)
     */
    public function cleanup(): int
    {
        $cleared = 0;
        $now = time();
        
        $files = glob($this->storagePath . '/*.json');
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            
            if ($data && $data['expires_at'] < $now) {
                unlink($file);
                $cleared++;
            }
        }
        
        return $cleared;
    }
}