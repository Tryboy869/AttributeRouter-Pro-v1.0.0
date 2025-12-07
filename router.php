<?php

declare(strict_types=1);

/**
 * AttributeRouter Pro - CLI Tool
 * 
 * Usage:
 *   php router.php scan        - Scan and list all routes
 *   php router.php cache       - Generate route cache
 *   php router.php clear-cache - Clear route cache
 *   php router.php list        - List routes in table format
 *   php router.php validate    - Validate all routes
 */

require __DIR__ . '/vendor/autoload.php';

use AttributeRouter\Router;

class RouterCLI
{
    private Router $router;
    
    public function __construct()
    {
        $this->router = new Router([
            'controllers_path' => __DIR__ . '/app/Controllers',
            'base_namespace' => 'App\\Controllers',
            'cache_enabled' => false, // Don't use cache during scanning
            'debug' => true,
        ]);
    }
    
    public function run(array $argv): void
    {
        $command = $argv[1] ?? 'help';
        
        match($command) {
            'scan' => $this->scan(),
            'cache' => $this->cache(),
            'clear-cache' => $this->clearCache(),
            'list' => $this->listRoutes(),
            'validate' => $this->validate(),
            'help', '--help', '-h' => $this->help(),
            default => $this->error("Unknown command: {$command}")
        };
    }
    
    private function scan(): void
    {
        $this->info("Scanning controllers...");
        
        $this->router->discoverRoutes();
        $routes = $this->router->getRoutes();
        
        $this->success(sprintf("Found %d routes:", count($routes)));
        echo "\n";
        
        $this->printRouteTable($routes);
    }
    
    private function cache(): void
    {
        $this->info("Generating route cache...");
        
        $router = new Router([
            'controllers_path' => __DIR__ . '/app/Controllers',
            'base_namespace' => 'App\\Controllers',
            'cache_enabled' => true,
        ]);
        
        $router->discoverRoutes();
        
        $this->success("Route cache generated successfully!");
    }
    
    private function clearCache(): void
    {
        $this->info("Clearing route cache...");
        
        if ($this->router->clearCache()) {
            $this->success("Cache cleared successfully!");
        } else {
            $this->warning("No cache to clear.");
        }
    }
    
    private function listRoutes(): void
    {
        $this->router->discoverRoutes();
        $routes = $this->router->getRoutes();
        
        $this->printRouteTable($routes);
    }
    
    private function validate(): void
    {
        $this->info("Validating routes...");
        
        $this->router->discoverRoutes();
        $routes = $this->router->getRoutes();
        
        $errors = [];
        
        foreach ($routes as $route) {
            // Check if controller exists
            if (!class_exists($route['controller'])) {
                $errors[] = sprintf(
                    "Controller not found: %s",
                    $route['controller']
                );
            }
            
            // Check if method exists
            if (!method_exists($route['controller'], $route['method_name'])) {
                $errors[] = sprintf(
                    "Method %s::%s not found",
                    $route['controller'],
                    $route['method_name']
                );
            }
        }
        
        if (empty($errors)) {
            $this->success("All routes valid!");
        } else {
            $this->error("Validation errors:");
            foreach ($errors as $error) {
                echo "  - {$error}\n";
            }
        }
    }
    
    private function printRouteTable(array $routes): void
    {
        if (empty($routes)) {
            $this->warning("No routes found.");
            return;
        }
        
        // Calculate column widths
        $methodWidth = max(6, ...array_map(fn($r) => strlen($r['method']), $routes));
        $uriWidth = max(3, ...array_map(fn($r) => strlen($r['uri']), $routes));
        $nameWidth = max(4, ...array_map(fn($r) => strlen($r['name'] ?? ''), $routes));
        
        // Header
        $this->printLine($methodWidth, $uriWidth, $nameWidth);
        echo sprintf(
            "| %-{$methodWidth}s | %-{$uriWidth}s | %-{$nameWidth}s | %s\n",
            'Method',
            'URI',
            'Name',
            'Action'
        );
        $this->printLine($methodWidth, $uriWidth, $nameWidth);
        
        // Routes
        foreach ($routes as $route) {
            $action = sprintf(
                '%s::%s',
                basename(str_replace('\\', '/', $route['controller'])),
                $route['method_name']
            );
            
            $middleware = !empty($route['middleware']) 
                ? ' [' . implode(',', $route['middleware']) . ']'
                : '';
            
            echo sprintf(
                "| \033[33m%-{$methodWidth}s\033[0m | %-{$uriWidth}s | %-{$nameWidth}s | %s%s\n",
                $route['method'],
                $route['uri'],
                $route['name'] ?? '-',
                $action,
                $middleware
            );
        }
        
        $this->printLine($methodWidth, $uriWidth, $nameWidth);
    }
    
    private function printLine(int $methodWidth, int $uriWidth, int $nameWidth): void
    {
        echo '+' . str_repeat('-', $methodWidth + 2)
            . '+' . str_repeat('-', $uriWidth + 2)
            . '+' . str_repeat('-', $nameWidth + 2)
            . '+' . str_repeat('-', 50) . "+\n";
    }
    
    private function help(): void
    {
        echo <<<HELP
        \033[1mAttributeRouter Pro CLI\033[0m
        
        \033[33mUsage:\033[0m
          php router.php [command]
        
        \033[33mCommands:\033[0m
          \033[32mscan\033[0m          Scan and display all routes
          \033[32mcache\033[0m         Generate route cache for production
          \033[32mclear-cache\033[0m   Clear route cache
          \033[32mlist\033[0m          List all routes in table format
          \033[32mvalidate\033[0m      Validate all routes (check controllers/methods exist)
          \033[32mhelp\033[0m          Show this help message
        
        \033[33mExamples:\033[0m
          php router.php scan
          php router.php cache
          php router.php list
        
        HELP;
    }
    
    private function info(string $message): void
    {
        echo "\033[34m[INFO]\033[0m {$message}\n";
    }
    
    private function success(string $message): void
    {
        echo "\033[32m[SUCCESS]\033[0m {$message}\n";
    }
    
    private function warning(string $message): void
    {
        echo "\033[33m[WARNING]\033[0m {$message}\n";
    }
    
    private function error(string $message): void
    {
        echo "\033[31m[ERROR]\033[0m {$message}\n";
        exit(1);
    }
}

// Run CLI
$cli = new RouterCLI();
$cli->run($argv);