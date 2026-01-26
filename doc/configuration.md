# Configuration Management

Juzdy provides a flexible configuration system that loads PHP files from the `etc/config/` directory and merges them into a single configuration array.

## Configuration Files

### Location

All configuration files are located in:
```
etc/config/
├── config.php          # Main application configuration
├── db.php             # Database configuration
├── layout.php         # Layout system configuration
└── middleware.php     # Middleware configuration
```

### Loading Configuration

Configuration is automatically loaded in `pub/index.php`:

```php
\Juzdy\Config::init(__DIR__ . '/../etc/config/*.php');
```

This loads all `.php` files matching the glob pattern and merges them into one configuration array.

## Main Configuration File

**File:** `etc/config/config.php`

```php
<?php
return [
    'root' => realpath(__DIR__ . '/../../'),

    'bootstrap' => [
        'discover' => true, // Auto-discover bootstrap classes from packages
    ],

    'http' => [
        'htaccess_handler_rewrite_param' => 'q',
        'request_handlers_namespace' => [
            '{namespace}\Http\Handler',
        ],
    ],

    'path' => [
        'pub' => '@{root}/pub',
        'var' => '@{root}/var',
        'logs' => '@{path.var}/logs',
    ],
];
```

### Key Configuration Options

#### Root Path
```php
'root' => realpath(__DIR__ . '/../../')
```
The root directory of your application. All other paths reference this.

#### Bootstrap Configuration
```php
'bootstrap' => [
    'discover' => true,  // Automatically discover and load bootstrap classes
]
```

#### HTTP Configuration
```php
'http' => [
    // Query parameter used for routing (when using .htaccess)
    'htaccess_handler_rewrite_param' => 'q',
    
    // Namespaces where handlers are located
    'request_handlers_namespace' => [
        '{namespace}\Http\Handler',
    ],
    
    // Optional: Default handler when none specified
    // 'default_handler' => 'Index',
]
```

#### Path Configuration
```php
'path' => [
    'pub' => '@{root}/pub',
    'var' => '@{root}/var',
    'logs' => '@{path.var}/logs',
]
```

## Dynamic References

Juzdy supports dynamic references using the `@{key}` syntax:

```php
'path' => [
    'root' => '/var/www/app',
    'var' => '@{path.root}/var',        // Resolves to: /var/www/app/var
    'logs' => '@{path.var}/logs',       // Resolves to: /var/www/app/var/logs
    'cache' => '@{path.var}/cache',     // Resolves to: /var/www/app/var/cache
]
```

You can reference any configuration key using dot notation:
- `@{root}` - References the root key
- `@{path.var}` - References path.var
- `@{db.host}` - References db.host

## Database Configuration

**File:** `etc/config/db.php`

```php
<?php
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'user' => 'your_username',
        'password' => 'your_password',
        'database' => 'your_database',
        'charset' => 'utf8mb4',
    ],
];
```

### Environment-Specific Configuration

For different environments (development, staging, production):

```php
<?php
$env = getenv('APP_ENV') ?: 'development';

$config = [
    'development' => [
        'host' => 'localhost',
        'user' => 'dev_user',
        'password' => 'dev_password',
        'database' => 'dev_database',
    ],
    'production' => [
        'host' => 'prod-db.example.com',
        'user' => 'prod_user',
        'password' => getenv('DB_PASSWORD'),
        'database' => 'prod_database',
    ],
];

return [
    'db' => $config[$env] ?? $config['development'],
];
```

## Layout Configuration

**File:** `etc/config/layout.php`

```php
<?php
return [
    'layout' => [
        'path' => '@{root}/app/layout',  // Layout directory
        'main' => 'layout.phtml',        // Main layout file
        'default' => 'default',          // Default layout folder
    ],
];
```

## Middleware Configuration

**File:** `etc/config/middleware.php`

```php
<?php
return [
    'middleware' => [
        'global' => [
            \Juzdy\Http\Middleware\CorsMiddleware::class,
            \Juzdy\Http\Router::class, // Must be last
        ],
        
        'groups' => [
            // Interface-based middleware groups
            \App\Interface\AuthenticatableInterface::class => [
                \App\Middleware\AuthMiddleware::class,
            ],
        ],
    ],
];
```

## Accessing Configuration

### In Your Code

```php
use Juzdy\Config;

// Get a configuration value
$dbHost = Config::get('db.host');
$root = Config::get('root');

// Get with default value
$timeout = Config::get('http.timeout', 30);

// Get entire section
$dbConfig = Config::get('db');
```

### In Handlers

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use Juzdy\Config;

class Settings extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        $appConfig = [
            'root' => Config::get('root'),
            'environment' => Config::get('app.environment', 'production'),
        ];
        
        return $this->response($appConfig);
    }
}
```

## Custom Configuration Files

You can add your own configuration files to `etc/config/`:

**File:** `etc/config/app.php`

```php
<?php
return [
    'app' => [
        'name' => 'My Application',
        'version' => '1.0.0',
        'environment' => 'production',
        'debug' => false,
        'timezone' => 'UTC',
    ],
];
```

**File:** `etc/config/services.php`

```php
<?php
return [
    'services' => [
        'email' => [
            'driver' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'user@example.com',
            'password' => getenv('EMAIL_PASSWORD'),
        ],
        'storage' => [
            'driver' => 's3',
            'bucket' => 'my-bucket',
            'region' => 'us-east-1',
        ],
    ],
];
```

## Environment Variables

Use `getenv()` to read environment variables:

```php
<?php
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'user' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'database' => getenv('DB_NAME') ?: 'app',
    ],
];
```

### Using .env Files

While PHP doesn't natively support `.env` files, you can use libraries like `vlucas/phpdotenv`:

```bash
composer require vlucas/phpdotenv
```

Then in `pub/index.php`, before loading config:

```php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
```

## Configuration Namespaces

Use the `{namespace}` placeholder for flexible namespace configuration:

```php
'http' => [
    'request_handlers_namespace' => [
        '{namespace}\Http\Handler',  // Resolves to App\Http\Handler by default
    ],
]
```

## Best Practices

### 1. Don't Store Secrets in Config Files

❌ **Bad:**
```php
'api_key' => 'sk_live_abc123xyz',
```

✅ **Good:**
```php
'api_key' => getenv('API_KEY'),
```

### 2. Use Dynamic References

❌ **Bad:**
```php
'paths' => [
    'logs' => '/var/www/app/var/logs',
    'cache' => '/var/www/app/var/cache',
]
```

✅ **Good:**
```php
'paths' => [
    'var' => '@{root}/var',
    'logs' => '@{paths.var}/logs',
    'cache' => '@{paths.var}/cache',
]
```

### 3. Organize by Feature

Create separate config files for different features:
- `app.php` - Application settings
- `db.php` - Database settings
- `cache.php` - Cache settings
- `email.php` - Email settings
- `services.php` - Third-party services

### 4. Use Type-Safe Access

```php
// Add type hints when retrieving config
private function getDbConfig(): array
{
    return Config::get('db') ?? [];
}

private function getAppName(): string
{
    return Config::get('app.name') ?? 'My App';
}
```

### 5. Document Your Configuration

Add comments to explain complex or important settings:

```php
<?php
return [
    'cache' => [
        // Cache driver: 'file', 'redis', 'memcached'
        'driver' => 'file',
        
        // Cache lifetime in seconds (0 = forever)
        'lifetime' => 3600,
        
        // Cache key prefix to avoid collisions
        'prefix' => 'app_',
    ],
];
```

## Advanced Usage

### Conditional Configuration

```php
<?php
$isProduction = getenv('APP_ENV') === 'production';

return [
    'app' => [
        'debug' => !$isProduction,
        'cache' => $isProduction,
        'log_level' => $isProduction ? 'error' : 'debug',
    ],
];
```

### Merging Configurations

Since all config files are merged, you can override defaults:

**File:** `etc/config/defaults.php`
```php
return [
    'app' => [
        'name' => 'Default App',
        'timezone' => 'UTC',
    ],
];
```

**File:** `etc/config/overrides.php`
```php
return [
    'app' => [
        'name' => 'My Custom App', // Overrides default
        // timezone remains 'UTC'
    ],
];
```

### Configuration Factories

Create factory classes for complex configuration:

```php
<?php
namespace App\Config;

class EmailConfigFactory
{
    public static function create(): array
    {
        $driver = getenv('EMAIL_DRIVER') ?: 'smtp';
        
        return match($driver) {
            'smtp' => self::smtpConfig(),
            'sendmail' => self::sendmailConfig(),
            'log' => self::logConfig(),
            default => throw new \Exception("Unknown email driver: {$driver}")
        };
    }
    
    private static function smtpConfig(): array
    {
        return [
            'driver' => 'smtp',
            'host' => getenv('SMTP_HOST'),
            'port' => getenv('SMTP_PORT'),
            'username' => getenv('SMTP_USERNAME'),
            'password' => getenv('SMTP_PASSWORD'),
        ];
    }
    
    // ... other config methods
}
```

## Troubleshooting

### Configuration Not Loading

Check that:
1. File is in `etc/config/` directory
2. File ends with `.php`
3. File returns an array
4. No syntax errors in the file

### Values Not Resolving

If dynamic references aren't working:
1. Check the key exists: `Config::get('key')`
2. Verify syntax: `@{key.subkey}`
3. Ensure no circular references

### Environment Variables Not Available

Make sure environment variables are set before config loading:
```bash
export DB_HOST=localhost
php -S localhost:8000 -t pub
```

Or use a `.env` file with `vlucas/phpdotenv`.

## Next Steps

- Learn about [HTTP Handlers](http-handlers.md)
- Configure [Middleware](middleware.md)
- Set up [Database](database.md)
- Understand [Architecture](architecture.md)
