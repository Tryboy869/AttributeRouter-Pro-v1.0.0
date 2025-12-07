<?php

declare(strict_types=1);

namespace App\Controllers;

use AttributeRouter\Attributes\Route;
use AttributeRouter\Attributes\Middleware;
use AttributeRouter\Attributes\RateLimit;
use AttributeRouter\Attributes\Cache;
use AttributeRouter\Attributes\Group;

/**
 * Product Controller - REST API Example
 * 
 * Demonstrates building a production-ready REST API with AttributeRouter Pro
 * Features: CRUD operations, authentication, rate limiting, caching, validation
 */
#[Group(prefix: '/api/v1', middleware: ['cors'])]
class ProductController
{
    /**
     * GET /api/v1/products
     * List all products with pagination
     */
    #[Route('GET', '/products', name: 'api.products.index')]
    #[Cache(300, tags: ['products'])]
    #[RateLimit(100)]
    public function index(): array
    {
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = (int) ($_GET['per_page'] ?? 20);
        
        $products = $this->fetchProducts($page, $perPage);
        
        return [
            'data' => $products,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => 100,
            ],
            'links' => [
                'self' => "/api/v1/products?page={$page}",
                'next' => "/api/v1/products?page=" . ($page + 1),
                'prev' => $page > 1 ? "/api/v1/products?page=" . ($page - 1) : null,
            ]
        ];
    }
    
    /**
     * GET /api/v1/products/{id}
     * Get single product by ID
     */
    #[Route('GET', '/products/{id}', name: 'api.products.show', where: ['id' => '\d+'])]
    #[Cache(600)]
    #[RateLimit(200)]
    public function show(int $id): array
    {
        $product = $this->findProduct($id);
        
        if (!$product) {
            http_response_code(404);
            return ['error' => 'Product not found', 'code' => 'PRODUCT_NOT_FOUND'];
        }
        
        return [
            'data' => $product,
            'links' => [
                'self' => "/api/v1/products/{$id}",
            ]
        ];
    }
    
    /**
     * POST /api/v1/products
     * Create new product (authenticated users only)
     */
    #[Route('POST', '/products', name: 'api.products.store')]
    #[Middleware(['auth:api'])]
    #[RateLimit(20)]
    public function store(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $errors = $this->validate($data, [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);
        
        if (!empty($errors)) {
            http_response_code(422);
            return ['errors' => $errors, 'code' => 'VALIDATION_FAILED'];
        }
        
        $product = $this->createProduct($data);
        
        http_response_code(201);
        return [
            'message' => 'Product created successfully',
            'data' => $product,
        ];
    }
    
    /**
     * PUT /api/v1/products/{id}
     * Update existing product
     */
    #[Route('PUT', '/products/{id}', name: 'api.products.update')]
    #[Middleware(['auth:api'])]
    #[RateLimit(30)]
    public function update(int $id): array
    {
        $product = $this->findProduct($id);
        
        if (!$product) {
            http_response_code(404);
            return ['error' => 'Product not found'];
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $updated = $this->updateProduct($id, $data);
        
        return [
            'message' => 'Product updated successfully',
            'data' => $updated,
        ];
    }
    
    /**
     * DELETE /api/v1/products/{id}
     * Delete product (admin only)
     */
    #[Route('DELETE', '/products/{id}', name: 'api.products.destroy')]
    #[Middleware(['auth:api', 'admin'])]
    #[RateLimit(10, per: 'hour')]
    public function destroy(int $id): array
    {
        $product = $this->findProduct($id);
        
        if (!$product) {
            http_response_code(404);
            return ['error' => 'Product not found'];
        }
        
        $this->deleteProduct($id);
        
        http_response_code(204);
        return [];
    }
    
    // ========== PRIVATE HELPERS ==========
    
    private function fetchProducts(int $page, int $perPage): array
    {
        $products = [];
        $start = ($page - 1) * $perPage;
        
        for ($i = $start; $i < $start + $perPage && $i < 100; $i++) {
            $products[] = [
                'id' => $i + 1,
                'name' => "Product " . ($i + 1),
                'price' => rand(10, 1000) / 10,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }
        
        return $products;
    }
    
    private function findProduct(int $id): ?array
    {
        if ($id < 1 || $id > 100) {
            return null;
        }
        
        return [
            'id' => $id,
            'name' => "Product {$id}",
            'price' => rand(10, 1000) / 10,
            'stock' => rand(0, 100),
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }
    
    private function createProduct(array $data): array
    {
        return array_merge($data, [
            'id' => rand(1000, 9999),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
    
    private function updateProduct(int $id, array $data): array
    {
        $product = $this->findProduct($id);
        return array_merge($product, $data, [
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
    
    private function deleteProduct(int $id): void
    {
        // Delete logic here
    }
    
    private function validate(array $data, array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            
            foreach ($fieldRules as $rule) {
                if ($rule === 'required' && empty($data[$field])) {
                    $errors[$field][] = "The {$field} field is required.";
                }
                
                if (str_starts_with($rule, 'max:')) {
                    $max = (int) substr($rule, 4);
                    if (isset($data[$field]) && strlen($data[$field]) > $max) {
                        $errors[$field][] = "The {$field} must not exceed {$max} characters.";
                    }
                }
                
                if (str_starts_with($rule, 'min:')) {
                    $min = (float) substr($rule, 4);
                    if (isset($data[$field]) && $data[$field] < $min) {
                        $errors[$field][] = "The {$field} must be at least {$min}.";
                    }
                }
                
                if ($rule === 'numeric' && isset($data[$field]) && !is_numeric($data[$field])) {
                    $errors[$field][] = "The {$field} must be a number.";
                }
            }
        }
        
        return $errors;
    }
}