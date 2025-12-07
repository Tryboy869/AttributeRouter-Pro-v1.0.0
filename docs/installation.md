# Installation Guide

## Requirements

- PHP 8.1 or higher
- Web server (Apache, Nginx, or PHP built-in server)
- mod_rewrite enabled (for Apache)

## Installation Steps

### Step 1: Extract Files

Extract the package to your project directory.

Recommended structure:
```
/your-project
  /src               <- AttributeRouter core files
  /app
    /Controllers     <- Your controllers here
  /cache             <- Route cache (auto-created)
  index.php          <- Bootstrap file
  .htaccess          <- Apache rewrite rules
```

### Step 2: Autoloading

**Option A - With Composer:**
```bash
composer install
```

Then include:
```php
require 'vendor/autoload.php';
```

**Option B - Manual (without Composer):**
```php
require 'src/autoload.php';
```

### Step 3: Create Bootstrap File

Create `index.php` in your project root:

```php
<?php

require 'vendor/autoload.php';

use AttributeRouter\Router;

$router = new Router([
    'controllers_path' => __DIR__ . '/app/Controllers',
    'base_namespace' => 'App\\Controllers',
    'cache_enabled' => true,
]);

$router->discoverRoutes();

try {
    $response = $router->run();
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
```

### Step 4: Configure Web Server

#### Apache

Create `.htaccess` in project root:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

Enable mod_rewrite:
```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

#### Nginx

Add to your server block:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

#### PHP Built-in Server (Development)

```bash
php -S localhost:8000 index.php
```

### Step 5: Create Your First Controller

Create `app/Controllers/HomeController.php`:

```php
<?php

namespace App\Controllers;

use AttributeRouter\Attributes\Route;

class HomeController
{
    #[Route('GET', '/')]
    public function index(): array
    {
        return [
            'message' => 'Welcome to AttributeRouter Pro!',
            'version' => '1.0.0'
        ];
    }
    
    #[Route('GET', '/hello/{name}')]
    public function hello(string $name): array
    {
        return ['message' => "Hello, {$name}!"];
    }
}
```

### Step 6: Test Installation

1. Visit: `http://localhost/`
   - Expected: `{"message":"Welcome to AttributeRouter Pro!","version":"1.0.0"}`

2. Visit: `http://localhost/hello/World`
   - Expected: `{"message":"Hello, World!"}`

3. Run CLI tool:
   ```bash
   php router.php scan
   ```
   - Expected: Table of discovered routes

## Troubleshooting

### Problem: 404 errors on all routes

**Solution:**
- Apache: Enable mod_rewrite, check .htaccess exists
- Nginx: Check try_files configuration
- Permissions: Ensure web server can read files

### Problem: "Class not found" errors

**Solution:**
- Check namespace matches 'base_namespace' in config
- Run: `composer dump-autoload`
- Verify autoload.php is included

### Problem: Routes not discovered

**Solution:**
- Check 'controllers_path' points to correct directory
- Verify controller files have .php extension
- Run: `php router.php scan`
- Check PHP version >= 8.1

### Problem: Slow performance

**Solution:**
- Enable `cache_enabled => true` in production
- Enable PHP opcache
- Run: `php router.php cache`

## Configuration Options

```php
$router = new Router([
    'controllers_path' => __DIR__ . '/app/Controllers',  // Required
    'base_namespace' => 'App\\Controllers',              // Required
    'cache_enabled' => false,                            // true in production
    'cache_path' => __DIR__ . '/cache',                 // Cache directory
    'debug' => false,                                    // true in development
]);
```

### Production Settings
- Set `cache_enabled => true`
- Set `debug => false`
- Enable opcache in php.ini
- Run: `php router.php cache`

### Development Settings
- Set `cache_enabled => false`
- Set `debug => true`
- Use PHP built-in server

## Next Steps

1. Read the [Quick Start Guide](quick-start.md)
2. Check [Advanced Usage](advanced-usage.md)
3. Review [API Reference](api-reference.md)
4. Explore examples in `/examples` directory

## Support

Need help? Contact us at nexusstudio100@gmail.com