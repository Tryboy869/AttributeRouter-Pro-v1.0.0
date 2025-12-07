<?php

declare(strict_types=1);

namespace AttributeRouter;

/**
 * Dispatches matched routes to controller methods
 * Handles dependency injection and parameter binding
 */
class RouteDispatcher
{
    private array $dependencies = [];
    
    /**
     * Register dependency for injection
     */
    public function bind(string $class, callable|object $resolver): void
    {
        $this->dependencies[$class] = $resolver;
    }
    
    /**
     * Dispatch route to controller method
     */
    public function dispatch(
        string $controller,
        string $method,
        array $params = []
    ): mixed {
        // Instantiate controller
        $instance = $this->makeController($controller);
        
        if (!method_exists($instance, $method)) {
            throw new \RuntimeException(
                "Method {$method} not found on {$controller}"
            );
        }
        
        // Resolve method parameters
        $reflection = new \ReflectionMethod($instance, $method);
        $args = $this->resolveParameters($reflection, $params);
        
        // Call method
        return $reflection->invokeArgs($instance, $args);
    }
    
    /**
     * Instantiate controller with dependency injection
     */
    private function makeController(string $class): object
    {
        if (!class_exists($class)) {
            throw new \RuntimeException("Controller not found: {$class}");
        }
        
        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        
        if (!$constructor) {
            return new $class();
        }
        
        $args = $this->resolveParameters($constructor);
        return $reflection->newInstanceArgs($args);
    }
    
    /**
     * Resolve method/constructor parameters
     */
    private function resolveParameters(
        \ReflectionMethod|\ReflectionFunction $reflection,
        array $routeParams = []
    ): array {
        $args = [];
        
        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();
            
            // Try route parameters first
            if (isset($routeParams[$name])) {
                $args[] = $this->castParameter($routeParams[$name], $type);
                continue;
            }
            
            // Try dependency injection
            if ($type && !$type->isBuiltin()) {
                $className = $type->getName();
                $args[] = $this->resolveDependency($className);
                continue;
            }
            
            // Try default value
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
                continue;
            }
            
            // Try superglobals
            $value = $this->getFromSuperglobals($name);
            if ($value !== null) {
                $args[] = $this->castParameter($value, $type);
                continue;
            }
            
            throw new \RuntimeException(
                "Cannot resolve parameter: {$name}"
            );
        }
        
        return $args;
    }
    
    /**
     * Resolve dependency from container
     */
    private function resolveDependency(string $class): object
    {
        if (isset($this->dependencies[$class])) {
            $resolver = $this->dependencies[$class];
            
            if (is_callable($resolver)) {
                return $resolver();
            }
            
            return $resolver;
        }
        
        // Auto-resolve if possible
        if (class_exists($class)) {
            return $this->makeController($class);
        }
        
        throw new \RuntimeException(
            "Cannot resolve dependency: {$class}"
        );
    }
    
    /**
     * Cast parameter to correct type
     */
    private function castParameter(mixed $value, ?\ReflectionType $type): mixed
    {
        if (!$type || $type instanceof \ReflectionUnionType) {
            return $value;
        }
        
        $typeName = $type->getName();
        
        return match($typeName) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string) $value,
            'array' => is_array($value) ? $value : [$value],
            default => $value
        };
    }
    
    /**
     * Get parameter from superglobals
     */
    private function getFromSuperglobals(string $name): mixed
    {
        return $_GET[$name] 
            ?? $_POST[$name]
            ?? $_REQUEST[$name]
            ?? null;
    }
}