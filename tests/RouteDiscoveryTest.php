<?php

declare(strict_types=1);

namespace AttributeRouter\Tests;

use PHPUnit\Framework\TestCase;
use AttributeRouter\RouteDiscovery;
use AttributeRouter\RouteCollection;
use AttributeRouter\Attributes\Route;

/**
 * RouteDiscovery Test Suite
 * 
 * Tests automatic route discovery:
 * - Controller scanning
 * - Attribute parsing
 * - Route registration
 * - Group attributes
 * - Middleware detection
 */
class RouteDiscoveryTest extends TestCase
{
    private RouteCollection $routes;
    private RouteDiscovery $discovery;
    
    protected function setUp(): void
    {
        $this->routes = new RouteCollection();
        $this->discovery = new RouteDiscovery($this->routes);
    }
    
    public function test_discovery_initialization(): void
    {
        $this->assertInstanceOf(RouteDiscovery::class, $this->discovery);
    }
    
    public function test_scan_directory_finds_controllers(): void
    {
        $path = __DIR__ . '/fixtures/Controllers';
        $namespace = 'AttributeRouter\\Tests\\Fixtures\\Controllers';
        
        if (!is_dir($path)) {
            $this->markTestSkipped('Fixtures directory not found');
        }
        
        $this->discovery->scanDirectory($path, $namespace);
        
        $routes = $this->routes->all();
        $this->assertGreaterThan(0, count($routes));
    }
    
    public function test_route_attribute_is_parsed(): void
    {
        $this->createTestController();
        
        $this->discovery->scanClass(TestController::class);
        
        $routes = $this->routes->all();
        $this->assertCount(1, $routes);
        $this->assertEquals('GET', $routes[0]['method']);
        $this->assertEquals('/test', $routes[0]['uri']);
    }
    
    public function test_multiple_routes_on_same_method(): void
    {
        $this->createMultiRouteController();
        
        $this->discovery->scanClass(MultiRouteController::class);
        
        $routes = $this->routes->all();
        $this->assertGreaterThan(1, count($routes));
    }
    
    public function test_group_attribute_is_applied(): void
    {
        $this->createGroupedController();
        
        $this->discovery->scanClass(GroupedController::class);
        
        $routes = $this->routes->all();
        $this->assertStringStartsWith('/api', $routes[0]['uri']);
    }
    
    public function test_middleware_is_extracted(): void
    {
        $this->createMiddlewareController();
        
        $this->discovery->scanClass(MiddlewareController::class);
        
        $routes = $this->routes->all();
        $this->assertArrayHasKey('middleware', $routes[0]);
        $this->assertContains('auth', $routes[0]['middleware']);
    }
    
    public function test_named_route_is_registered(): void
    {
        $this->createNamedRouteController();
        
        $this->discovery->scanClass(NamedRouteController::class);
        
        $route = $this->routes->findByName('test.route');
        $this->assertNotNull($route);
    }
    
    public function test_dynamic_route_pattern_generated(): void
    {
        $this->createDynamicController();
        
        $this->discovery->scanClass(DynamicController::class);
        
        $routes = $this->routes->all();
        $this->assertNotNull($routes[0]['pattern']);
        $this->assertStringContainsString('(?<id>', $routes[0]['pattern']);
    }
    
    public function test_route_constraints_are_preserved(): void
    {
        $this->createConstrainedController();
        
        $this->discovery->scanClass(ConstrainedController::class);
        
        $routes = $this->routes->all();
        $pattern = $routes[0]['pattern'];
        $this->assertStringContainsString('\d+', $pattern);
    }
    
    public function test_class_level_middleware_inherited(): void
    {
        $this->createClassMiddlewareController();
        
        $this->discovery->scanClass(ClassMiddlewareController::class);
        
        $routes = $this->routes->all();
        $this->assertContains('cors', $routes[0]['middleware']);
    }
    
    public function test_rate_limit_is_detected(): void
    {
        $this->createRateLimitedController();
        
        $this->discovery->scanClass(RateLimitedController::class);
        
        $routes = $this->routes->all();
        $this->assertArrayHasKey('rateLimit', $routes[0]);
    }
    
    public function test_cache_attribute_is_detected(): void
    {
        $this->createCachedController();
        
        $this->discovery->scanClass(CachedController::class);
        
        $routes = $this->routes->all();
        $this->assertArrayHasKey('cache', $routes[0]);
    }
    
    // ========== HELPER METHODS (Create Test Controllers) ==========
    
    private function createTestController(): void
    {
        eval('
        namespace AttributeRouter\Tests;
        use AttributeRouter\Attributes\Route;
        
        class TestController {
            #[Route("GET", "/test")]
            public function index() {}
        }
        ');
    }
    
    private function createMultiRouteController(): void
    {
        eval('
        namespace AttributeRouter\Tests;
        use AttributeRouter\Attributes\Route;
        
        class MultiRouteController {
            #[Route("GET", "/users")]
            #[Route("POST", "/users")]
            public function users() {}
        }
        ');
    }
    
    private function createGroupedController(): void
    {
        eval('
        namespace AttributeRouter\Tests;
        use AttributeRouter\Attributes\{Route, Group};
        
        #[Group(prefix: "/api")]
        class GroupedController {
            #[Route("GET", "/users")]
            public function users() {}
        }
        ');
    }
    
    private function createMiddlewareController(): void
    {
        eval('
        namespace AttributeRouter\Tests;
        use AttributeRouter\Attributes\{Route, Middleware};
        
        class MiddlewareController {
            #[Route("GET", "/secure")]
            #[Middleware("auth")]
            public function secure() {}
        }
        ');
    }
    
    private function createNamedRouteController(): void
    {
        eval('
        namespace AttributeRouter\Tests;
        use AttributeRouter\Attributes\Route;
        
        class NamedRouteController {
            #[Route("GET", "/test", name: "test.route")]
            public function test() {}
        }
        ');
    }
    
    private function createDynamicController(): void
    {
        eval('
        namespace AttributeRouter\Tests;
        use AttributeRouter\Attributes\Route;
        
        class DynamicController {
            #[Route("GET", "/users/{id}")]
            public function show(int $id) {}
        }
        ');
    }
    
    private function createConstrainedController(): void
    {
        eval('
        namespace AttributeRouter\Tests;
        use AttributeRouter\Attributes\Route;
        
        class ConstrainedController {
            #[Route("GET", "/users/{id}", where: ["id" => "\d+"])]
            public function show(int $id) {}
        }
        ');
    }
    
    private function createClassMiddlewareController(): void
    {
        eval('
        namespace AttributeRouter\Tests;
        use AttributeRouter\Attributes\{Route, Middleware};
        
        #[Middleware("cors")]
        class ClassMiddlewareController {
            #[Route("GET", "/api/data")]
            public function data() {}
        }
        ');
    }
    
    private function createRateLimitedController(): void
    {
        eval('
        namespace AttributeRouter\Tests;
        use AttributeRouter\Attributes\{Route, RateLimit};
        
        class RateLimitedController {
            #[Route("POST", "/api/data")]
            #[RateLimit(60)]
            public function store() {}
        }
        ');
    }
    
    private function createCachedController(): void
    {
        eval('
        namespace AttributeRouter\Tests;
        use AttributeRouter\Attributes\{Route, Cache};
        
        class CachedController {
            #[Route("GET", "/cached")]
            #[Cache(300)]
            public function cached() {}
        }
        ');
    }
}