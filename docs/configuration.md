# ⚙️ Configuration Guide

JUZDY's configuration system is designed to be simple yet powerful. Everything is configured through PHP files, giving you the full power of PHP when you need it.

---

## 📁 Configuration File Location

All configuration files live in:

```
etc/config/
├── config.php          # Main application configuration
├── middleware.php      # Middleware pipeline setup
├── layout.php          # View and layout settings
└── db.php             # Database configuration
```

---

## 🔄 How Configuration Loading Works

### Automatic Loading

Configuration files are automatically loaded when the application boots:

```php
// In pub/index.php
\Juzdy\Config::init(__DIR__ . '/../etc/config/*.php');
```

### Loading Order

Files are loaded in **glob order**. To control loading sequence, use prefixes:

```
etc/config/
├── 01-database.php     # Loads first
├── 02-cache.php        # Loads second
└── 99-overrides.php    # Loads last
```

### Configuration Merging

Later files can override earlier ones:

```php
// 01-database.php
return ['db' => ['host' => 'localhost']];

// 02-database-prod.php
return ['db' => ['host' => 'prod-server']];

// Result: ['db' => ['host' => 'prod-server']]
```

---

## 🎯 Main Configuration (`config.php`)

The heart of your application configuration.

### Full Example

```php
<?php
return [
    // Application root directory
    'root' => realpath(__DIR__ . '/../../'),
    
    // Bootstrap configuration
    'bootstrap' => [
        'discover' => true, // Auto-discover bootstrap classes
    ],
    
    // HTTP configuration
    'http' => [
        // Query parameter for routing (internal use)
        'htaccess_handler_rewrite_param' => 'q',
        
        // Where to find request handlers
        'request_handlers_namespace' => [
            '{namespace}\Http\Handler',
        ],
        
        // Default handler (optional)
        // 'default_handler' => 'Index',
    ],
    
    // Path configuration
    'path' => [
        'pub' => '@{root}/pub',
        'var' => '@{root}/var',
        'logs' => '@{path.var}/logs',
        'cache' => '@{path.var}/cache',
        'sessions' => '@{path.var}/sessions',
    ],
    
    // Custom application settings
    'app' => [
        'name' => 'My Awesome App',
        'version' => '1.0.0',
        'debug' => true,
        'timezone' => 'UTC',
    ],
];
```

### Configuration Options Explained

#### `root`

Your application's base directory. All other paths reference this.

```php
'root' => realpath(__DIR__ . '/../../'),
```

#### `bootstrap.discover`

Enable automatic discovery of bootstrap classes:

```php
'bootstrap' => [
    'discover' => true, // Recommended: auto-load core components
],
```

#### `http.request_handlers_namespace`

Namespaces where JUZDY looks for handlers:

```php
'http' => [
    'request_handlers_namespace' => [
        '{namespace}\Http\Handler',    // Default
        'MyApp\Controllers',           // Add custom namespace
    ],
],
```

#### `path.*`

Define directory shortcuts with variable interpolation:

```php
'path' => [
    'pub' => '@{root}/pub',           // Uses {root}
    'var' => '@{root}/var',           // Uses {root}
    'logs' => '@{path.var}/logs',     // Uses {path.var}
],
```

**Access in code:**

```php
$logsPath = \Juzdy\Config::get('path.logs');
```

---

## 🔗 Middleware Configuration (`middleware.php`)

Configure your middleware pipeline.

### Example

```php
<?php
return [
    'middleware' => [
        
        // Global middleware (runs for ALL requests)
        'global' => [
            \Juzdy\Http\Middleware\CorsMiddleware::class,
            \App\Middleware\LoggingMiddleware::class,
            \App\Middleware\AuthMiddleware::class,
            
            // Router must always be last!
            \Juzdy\Http\Router::class,
        ],
        
        // Middleware groups (conditional)
        'groups' => [
            // Middleware for specific handler interfaces
            \App\Http\AuthenticatedInterface::class => [
                \App\Middleware\BasicAuthMiddleware::class,
            ],
            \App\Http\LoggableInterface::class => [
                \App\Middleware\RequestLoggingMiddleware::class,
            ],
        ],
    ],
];
```

### Global Middleware

Runs for every request in the specified order:

```php
'global' => [
    \Juzdy\Http\Middleware\CorsMiddleware::class,  // 1st
    \App\Middleware\SecurityMiddleware::class,     // 2nd
    \Juzdy\Http\Router::class,                     // Last!
],
```

⚠️ **Important:** Router middleware must always be last in the global stack!

### Middleware Groups

Conditional middleware based on handler interfaces:

```php
// Define the interface
interface ApiHandlerInterface {}

// Configure middleware
'groups' => [
    \App\Http\ApiHandlerInterface::class => [
        \App\Middleware\ApiAuthMiddleware::class,
        \App\Middleware\RateLimitMiddleware::class,
    ],
],

// Handler implements interface
class Products extends Handler implements ApiHandlerInterface
{
    // ApiAuthMiddleware and RateLimitMiddleware run automatically!
}
```

---

## 🎨 Layout Configuration (`layout.php`)

Configure view templates and layouts.

### Example

```php
<?php
return [
    'layout' => [
        // Default layout
        'default' => 'default',
        
        // Layout directory
        'path' => '@{root}/app/layout',
        
        // Template file extension
        'extension' => '.phtml',
        
        // Layout-specific settings
        'layouts' => [
            'default' => [
                'template' => 'layout',
            ],
            'admin' => [
                'template' => 'admin-layout',
            ],
        ],
    ],
];
```

### Using Layouts in Handlers

```php
class Products extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        return $this->response()
            ->body($this->renderTemplate('products', [
                'products' => $this->getProducts(),
            ]));
    }
}
```

---

## 💾 Database Configuration (`db.php`)

Configure your database connections.

### Single Database

```php
<?php
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'user' => 'juzdy',
        'password' => 'secret',
        'database' => 'juzdy_db',
        'charset' => 'utf8mb4',
    ],
];
```

### Multiple Databases

```php
<?php
return [
    'db' => [
        'default' => 'mysql',
        
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'juzdy',
                'username' => 'root',
                'password' => 'secret',
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => 'localhost',
                'database' => 'juzdy_pg',
                'username' => 'postgres',
                'password' => 'secret',
            ],
        ],
    ],
];
```

### Docker Database Connection

When using Docker, use the service name as host:

```php
'db' => [
    'host' => 'db',  // Service name from docker-compose.yml
    'port' => 3306,
    'user' => 'juzdy',
    'password' => 'juzdy',
    'database' => 'juzdy',
],
```

---

## 🔐 Environment-Specific Configuration

### Using Environment Variables

```php
<?php
return [
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'database' => $_ENV['DB_NAME'] ?? 'juzdy',
    ],
    
    'app' => [
        'debug' => (bool)($_ENV['APP_DEBUG'] ?? false),
    ],
];
```

### Environment Files

Create a `.env` file in your project root:

```env
# .env
APP_DEBUG=true
APP_NAME="My App"

DB_HOST=localhost
DB_USER=juzdy
DB_PASSWORD=secret
DB_NAME=juzdy_db
```

**Load with vlucas/phpdotenv:**

```bash
composer require vlucas/phpdotenv
```

```php
// pub/index.php (before Config::init)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
```

### Environment-Based Config Files

```
etc/config/
├── config.php              # Base config
├── config.local.php        # Local overrides (gitignored)
├── config.production.php   # Production settings
└── config.development.php  # Development settings
```

**Load conditionally:**

```php
// In config.php
$env = $_ENV['APP_ENV'] ?? 'development';

return [
    'environment' => $env,
    // ... other settings
];

// In pub/index.php
$env = $_ENV['APP_ENV'] ?? 'development';
\Juzdy\Config::init(__DIR__ . "/../etc/config/config.php");
\Juzdy\Config::init(__DIR__ . "/../etc/config/config.{$env}.php");
```

---

## 🎯 Accessing Configuration

### In Handlers

```php
class MyHandler extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        // Get config value
        $appName = \Juzdy\Config::get('app.name');
        $debug = \Juzdy\Config::get('app.debug', false); // Default: false
        
        // Get nested values
        $dbHost = \Juzdy\Config::get('db.host');
        
        return $this->response()->body("App: {$appName}");
    }
}
```

### In Services

```php
class DatabaseService
{
    private $connection;
    
    public function __construct()
    {
        $config = \Juzdy\Config::get('db');
        $this->connection = new PDO(
            "mysql:host={$config['host']};dbname={$config['database']}",
            $config['user'],
            $config['password']
        );
    }
}
```

---

## 🚀 Best Practices

### 1. Use Environment Variables for Secrets

❌ **Don't:**
```php
'db' => [
    'password' => 'my-secret-password', // Hard-coded!
],
```

✅ **Do:**
```php
'db' => [
    'password' => $_ENV['DB_PASSWORD'], // From environment
],
```

### 2. Keep Sensitive Files Out of Version Control

```gitignore
# .gitignore
etc/config/*.local.php
etc/config/secrets.php
.env
```

### 3. Use Descriptive Names

❌ **Don't:**
```php
'x' => 'value',
```

✅ **Do:**
```php
'api_timeout_seconds' => 30,
```

### 4. Group Related Settings

```php
'api' => [
    'base_url' => 'https://api.example.com',
    'timeout' => 30,
    'retry_attempts' => 3,
],
```

### 5. Document Complex Settings

```php
return [
    // Maximum upload size in bytes (10MB)
    'max_upload_size' => 10 * 1024 * 1024,
    
    // Session lifetime in seconds (2 hours)
    'session_lifetime' => 2 * 60 * 60,
];
```

---

## 📝 Common Configuration Patterns

### Feature Flags

```php
'features' => [
    'new_dashboard' => true,
    'beta_api' => false,
    'maintenance_mode' => false,
],
```

```php
// In handler
if (\Juzdy\Config::get('features.new_dashboard')) {
    return $this->renderNewDashboard();
}
```

### API Configuration

```php
'api' => [
    'base_url' => 'https://api.example.com',
    'key' => $_ENV['API_KEY'],
    'secret' => $_ENV['API_SECRET'],
    'timeout' => 30,
    'retry_attempts' => 3,
    'endpoints' => [
        'users' => '/v1/users',
        'products' => '/v1/products',
    ],
],
```

### Cache Configuration

```php
'cache' => [
    'driver' => 'file', // file, redis, memcached
    'path' => '@{path.var}/cache',
    'ttl' => 3600, // 1 hour
    
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
    ],
],
```

---

## ❓ Troubleshooting

### Configuration Not Loading

**Problem:** Changes don't take effect  
**Solution:** Clear any opcode caches:

```bash
# Clear PHP opcache
php -r "opcache_reset();"

# Restart PHP-FPM
sudo systemctl restart php8.0-fpm
```

### Variable Interpolation Not Working

**Problem:** `@{root}` appears literally in paths  
**Solution:** Ensure you're using JUZDY's path resolver

### Environment Variables Not Available

**Problem:** `$_ENV['MY_VAR']` is empty  
**Solution:** Enable variables_order in php.ini:

```ini
variables_order = "EGPCS"
```

---

## 🎓 Next Steps

- 📖 [Middleware Configuration](middleware.md)
- 📖 [Request Handlers](handlers.md)
- 📖 [Examples](examples.md)

---

**Need more examples?** Check out the [Examples Guide](examples.md) →
