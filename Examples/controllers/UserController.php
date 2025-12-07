<?php

declare(strict_types=1);

namespace App\Controllers;

use AttributeRouter\Attributes\Route;
use AttributeRouter\Attributes\Middleware;
use AttributeRouter\Attributes\RateLimit;
use AttributeRouter\Attributes\Cache;
use AttributeRouter\Attributes\Group;

/**
 * Example User Controller
 * Demonstrates all AttributeRouter features
 */
#[Group(prefix: '/api/v1', middleware: ['cors'])]
class UserController
{
    /**
     * List all users
     * Cached for 5 minutes
     */
    #[Route('GET', '/users', name: 'users.index')]
    #[Cache(300)]
    #[RateLimit(100)]
    public function index(): array
    {
        return [
            'data' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ],
            'meta' => ['total' => 2]
        ];
    }
    
    /**
     * Get single user by ID
     * Dynamic route with constraint
     */
    #[Route('GET', '/users/{id}', name: 'users.show', where: ['id' => '\d+'])]
    #[Cache(600)]
    #[RateLimit(200)]
    public function show(int $id): array
    {
        return [
            'data' => [
                'id' => $id,
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ]
        ];
    }
    
    /**
     * Create new user
     * Requires authentication
     */
    #[Route('POST', '/users', name: 'users.store')]
    #[Middleware(['auth', 'validate:user'])]
    #[RateLimit(10)]
    public function store(): array
    {
        $data = $_POST;
        
        return [
            'message' => 'User created successfully',
            'data' => [
                'id' => 3,
                'name' => $data['name'] ?? 'Unknown',
                'email' => $data['email'] ?? 'unknown@example.com'
            ]
        ];
    }
    
    /**
     * Update user
     * Admin only
     */
    #[Route('PUT', '/users/{id}', name: 'users.update')]
    #[Middleware(['auth', 'admin'])]
    #[RateLimit(20)]
    public function update(int $id): array
    {
        return [
            'message' => 'User updated successfully',
            'data' => ['id' => $id]
        ];
    }
    
    /**
     * Delete user
     * Admin only, strict rate limit
     */
    #[Route('DELETE', '/users/{id}', name: 'users.destroy')]
    #[Middleware(['auth', 'admin'])]
    #[RateLimit(5, per: 'hour')]
    public function destroy(int $id): array
    {
        return [
            'message' => 'User deleted successfully',
            'data' => ['id' => $id]
        ];
    }
    
    /**
     * Get user profile (by slug)
     */
    #[Route('GET', '/profile/{slug}', name: 'users.profile')]
    #[Cache(1800)]
    public function profile(string $slug): array
    {
        return [
            'data' => [
                'slug' => $slug,
                'name' => ucfirst($slug),
                'bio' => 'Sample user bio'
            ]
        ];
    }
}