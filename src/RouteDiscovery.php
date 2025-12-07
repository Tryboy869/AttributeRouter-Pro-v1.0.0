<?php

declare(strict_types=1);

namespace AttributeRouter;

use ReflectionClass;
use ReflectionMethod;
use AttributeRouter\Attributes\Route;
use AttributeRouter\Attributes\Middleware;
use AttributeRouter\Attributes\RateLimit;
use AttributeRouter\Attributes\Cache;
use AttributeRouter\Attributes\Group;

/**
 * Automatic route discovery using Reflection API
 * Scans controller classes for Route attributes
 */
class RouteDiscovery
{
    private RouteCollection $routes;
    
    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }
    
    /**
     * Scan directory for controller files
     */
    public function scanDirectory(string $path, string $namespace): void
    {
        if (!is_dir($path)) {
            throw new \RuntimeException("Controllers path not found: $path");
        }
        
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );
        
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->scanFile($file->getPathname(), $namespace);
            }
        }
    }
    
    /**
     * Scan single file for routes
     */
    public function scanFile(string $filepath, string $namespace): void
    {
        require_once $filepath;
        
        $className = $this->extractClassName($filepath, $namespace);
        if (!$className || !class_exists($className)) {
            return;
        }
        
        $this->scanClass($className);
    }
    
    /**
     * Scan class for route attributes
     */
    public function scanClass(string $className): void
    {
        $reflection = new ReflectionClass($className);
        
        // Get class-level attributes (Group)
        $groupAttr = $this->getClassGroup($reflection);
        $classMiddleware = $this->getClassMiddleware($reflection);
        
        // Scan methods
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $this->scanMethod($method, $className, $groupAttr, $classMiddleware);
        }
    }
    
    /**
     * Scan method for route attributes
     */
    private function scanMethod(
        ReflectionMethod $method,
        string $className,
        ?Group $group,
        array $classMiddleware
    ): void {
        $routeAttrs = $method->getAttributes(Route::class);
        
        foreach ($routeAttrs as $attr) {
            /** @var Route $route */
            $route = $attr->newInstance();
            
            $routeData = [
                'method' => $route->method,
                'uri' => $route->getFullUri(),
                'controller' => $className,
                'method_name' => $method->getName(),
                'name' => $route->name,
                'pattern' => $route->toPattern(),
                'middleware' => array_merge(
                    $classMiddleware,
                    $this->extractMiddleware($method)
                ),
            ];
            
            // Apply group settings
            if ($group) {
                $group->applyToRoute($routeData);
            }
            
            // Extract rate limit
            $rateLimit = $this->extractRateLimit($method);
            if ($rateLimit) {
                $routeData['rateLimit'] = $rateLimit;
            }
            
            // Extract cache
            $cache = $this->extractCache($method);
            if ($cache) {
                $routeData['cache'] = $cache;
            }
            
            $this->routes->add($routeData);
        }
    }
    
    private function getClassGroup(ReflectionClass $class): ?Group
    {
        $attrs = $class->getAttributes(Group::class);
        return $attrs[0]->newInstance() ?? null;
    }
    
    private function getClassMiddleware(ReflectionClass $class): array
    {
        $middleware = [];
        $attrs = $class->getAttributes(Middleware::class);
        
        foreach ($attrs as $attr) {
            /** @var Middleware $mw */
            $mw = $attr->newInstance();
            $middleware = array_merge($middleware, $mw->getMiddleware());
        }
        
        return $middleware;
    }
    
    private function extractMiddleware(ReflectionMethod $method): array
    {
        $middleware = [];
        $attrs = $method->getAttributes(Middleware::class);
        
        foreach ($attrs as $attr) {
            /** @var Middleware $mw */
            $mw = $attr->newInstance();
            $middleware = array_merge($middleware, $mw->getMiddleware());
        }
        
        return $middleware;
    }
    
    private function extractRateLimit(ReflectionMethod $method): ?RateLimit
    {
        $attrs = $method->getAttributes(RateLimit::class);
        return $attrs ? $attrs[0]->newInstance() : null;
    }
    
    private function extractCache(ReflectionMethod $method): ?Cache
    {
        $attrs = $method->getAttributes(Cache::class);
        return $attrs ? $attrs[0]->newInstance() : null;
    }
    
    private function extractClassName(string $filepath, string $namespace): ?string
    {
        $content = file_get_contents($filepath);
        
        // Extract class name from file
        if (preg_match('/class\s+([a-zA-Z_][a-zA-Z0-9_]*)/', $content, $matches)) {
            return $namespace . '\\' . $matches[1];
        }
        
        return null;
    }
}