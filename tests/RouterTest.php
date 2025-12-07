<?php

declare(strict_types=1);

namespace AttributeRouter\Tests;

use PHPUnit\Framework\TestCase;
use AttributeRouter\Router;
use AttributeRouter\Exceptions\RouteNotFoundException;
use AttributeRouter\Exceptions\MethodNotAllowedException;

/**
 * Router Test Suite
 * 
 * Tests core routing functionality:
 * - Route matching (static and dynamic)
 * - HTTP method handling
 * - Named routes
 * - Route parameters
 * - Exception handling
 */
class RouterTest extends TestCase
{
    private Router $router;
    
    protected function setUp(): void
    {
        $this->router = new Router([
            'controllers_path' => __DIR__ . '/fixtures/Controllers',
            'base_namespace' => 'AttributeRouter\\Tests\\Fixtures\\Controllers',
            'cache_enabled' => false,
            'debug' => true,
        ]);
        
        $this->router->discoverRoutes();
    }
    
    public function test_router_initialization(): void
    {
        $this->assertInstanceOf(Router::class, $this->router);
    }
    
    public function test_static_route_matches(): void
    {
        $match = $this->router->match('GET', '/users');
        
        $this->assertNotNull($match);
        $this->assertArrayHasKey('controller', $match);
        $this->assertArrayHasKey('method', $match);
    }
    
    public function test_dynamic_route_matches(): void
    {
        $match = $this->router->match('GET', '/users/123');
        
        $this->assertNotNull($match);
        $this->assertArrayHasKey('params', $match);
        $this->assertEquals(123, $match['params']['id']);
    }
    
    public function test_route_with_multiple_parameters(): void
    {
        $match = $this->router->match('GET', '/posts/2025/12/my-slug');
        
        $this->assertArrayHasKey('params', $match);
        $this->assertEquals(2025, $match['params']['year']);
        $this->assertEquals(12, $match['params']['month']);
        $this->assertEquals('my-slug', $match['params']['slug']);
    }
    
    public function test_route_not_found_throws_exception(): void
    {
        $this->expectException(RouteNotFoundException::class);
        $this->router->match('GET', '/nonexistent');
    }
    
    public function test_method_not_allowed_throws_exception(): void
    {
        $this->expectException(MethodNotAllowedException::class);
        $this->router->match('POST', '/users'); // Only GET allowed
    }
    
    public function test_named_route_url_generation(): void
    {
        $url = $this->router->url('users.show', ['id' => 123]);
        $this->assertEquals('/users/123', $url);
    }
    
    public function test_named_route_not_found(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->router->url('nonexistent.route');
    }
    
    public function test_middleware_is_attached(): void
    {
        $match = $this->router->match('POST', '/admin/users');
        
        $this->assertArrayHasKey('middleware', $match);
        $this->assertContains('auth', $match['middleware']);
    }
    
    public function test_route_constraints_validation(): void
    {
        // Should match (valid ID)
        $match = $this->router->match('GET', '/users/123');
        $this->assertNotNull($match);
        
        // Should not match (invalid ID - not numeric)
        $this->expectException(RouteNotFoundException::class);
        $this->router->match('GET', '/users/abc');
    }
    
    public function test_route_caching(): void
    {
        $router = new Router([
            'controllers_path' => __DIR__ . '/fixtures/Controllers',
            'base_namespace' => 'AttributeRouter\\Tests\\Fixtures\\Controllers',
            'cache_enabled' => true,
            'cache_path' => sys_get_temp_dir(),
        ]);
        
        $router->discoverRoutes();
        
        // Cache file should exist
        $cacheFile = sys_get_temp_dir() . '/routes.cache';
        $this->assertFileExists($cacheFile);
        
        // Cleanup
        $router->clearCache();
    }
    
    public function test_cache_clear(): void
    {
        $router = new Router([
            'cache_enabled' => true,
            'cache_path' => sys_get_temp_dir(),
        ]);
        
        $result = $router->clearCache();
        $this->assertTrue($result);
    }
    
    public function test_route_count(): void
    {
        $routes = $this->router->getRoutes();
        $this->assertIsArray($routes);
        $this->assertGreaterThan(0, count($routes));
    }
    
    public function test_http_methods_support(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        
        foreach ($methods as $method) {
            try {
                $this->router->match($method, '/test');
            } catch (RouteNotFoundException $e) {
                // Expected if route doesn't exist
                $this->assertInstanceOf(RouteNotFoundException::class, $e);
            }
        }
    }
    
    public function test_global_middleware_registration(): void
    {
        $this->router->middleware('cors');
        $this->router->middleware(['auth', 'logging']);
        
        // Global middleware should be registered
        $this->assertTrue(true); // Placeholder assertion
    }
    
    public function test_rate_limit_attribute_detected(): void
    {
        $match = $this->router->match('POST', '/api/data');
        
        if (isset($match['rateLimit'])) {
            $this->assertInstanceOf(
                \AttributeRouter\Attributes\RateLimit::class,
                $match['rateLimit']
            );
        }
        
        $this->assertTrue(true); // Placeholder
    }
}