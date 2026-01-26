# Getting Started with Juzdy

This guide will help you set up and start developing with the Juzdy project template.

## Installation

### Requirements

- **PHP**: Version 8.0 or higher
- **Composer**: For dependency management
- **Web Server**: Apache, Nginx, or PHP's built-in server
- **MySQL**: (Optional) For database functionality

### Create a New Project

#### Option 1: Using Composer (Recommended)

```bash
composer create-project juzdy/juzdy my-project
cd my-project
```

#### Option 2: Clone the Repository

```bash
git clone https://github.com/juzdy/juzdy.git my-project
cd my-project
composer install
```

## Development Setup

### Using PHP Built-in Server

The quickest way to get started:

```bash
php -S localhost:8000 -t pub
```

Your application will be available at http://localhost:8000

### Using Docker

For a complete environment with PHP and MySQL:

```bash
./bin/docker-start.sh
```

Access your application at http://localhost:8080. See [DOCKER.md](../DOCKER.md) for more details.

### Using Apache/Nginx

Configure your web server to point to the `pub/` directory as the document root.

#### Apache Configuration Example

```apache
<VirtualHost *:80>
    ServerName myproject.local
    DocumentRoot /path/to/juzdy/pub
    
    <Directory /path/to/juzdy/pub>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx Configuration Example

```nginx
server {
    listen 80;
    server_name myproject.local;
    root /path/to/juzdy/pub;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?q=$uri&$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## Your First Request Handler

### 1. Create a Handler

Create a new file `app/src/Http/Handler/Hello.php`:

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;

class Hello extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        $name = $request->getParam('name') ?? 'World';
        
        return $this->response()
            ->header('Content-Type', 'text/plain')
            ->body("Hello, {$name}!");
    }
}
```

### 2. Access Your Handler

Visit: http://localhost:8000/Hello

Or with a parameter: http://localhost:8000/Hello?name=John

### 3. Return JSON Response

Modify the handler to return JSON:

```php
public function handle(RequestInterface $request): ResponseInterface
{
    $name = $request->getParam('name') ?? 'World';
    
    return $this->response([
        'message' => "Hello, {$name}!",
        'timestamp' => time()
    ]);
}
```

## Configuration

### Basic Configuration

Configuration files are located in `etc/config/` directory. The framework automatically loads all `.php` files from this directory.

**Main configuration** (`etc/config/config.php`):

```php
<?php
return [
    'root' => realpath(__DIR__ . '/../../'),
    
    'bootstrap' => [
        'discover' => true,
    ],
    
    'http' => [
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

### Database Configuration

Configure database connection in `etc/config/db.php`:

```php
<?php
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'user' => 'your_username',
        'password' => 'your_password',
        'database' => 'your_database'
    ],
];
```

## Project Structure Overview

```
my-project/
├── app/                    # Your application code
│   ├── layout/            # View templates
│   └── src/               # PHP source code
│       └── Http/
│           └── Handler/   # Request handlers go here
├── etc/
│   └── config/           # Configuration files
├── pub/                   # Web root (public directory)
│   ├── index.php         # Entry point
│   └── .htaccess         # Apache rewrite rules
└── var/                   # Variable data (logs, cache)
```

## Understanding the Request Flow

1. **Entry Point**: All requests go through `pub/index.php`
2. **Bootstrap**: The application is bootstrapped using Juzdy\Bootstrap
3. **Configuration**: Config files are loaded from `etc/config/*.php`
4. **Middleware**: Request passes through middleware pipeline
5. **Routing**: Router determines which handler to use based on URL
6. **Handler**: Your handler processes the request
7. **Response**: Handler returns a response to the client

## Next Steps

- Learn about [HTTP Handlers](http-handlers.md) in detail
- Understand the [Architecture](architecture.md)
- Configure [Middleware](middleware.md)
- Work with [Database](database.md)
- Explore [Examples](examples.md)

## Common Issues

### "Handler not found" Error

Make sure:
1. Your handler class name matches the URL segment
2. The handler is in the correct namespace (`App\Http\Handler`)
3. The file name matches the class name

### Permission Issues

Ensure the `var/` directory is writable:

```bash
chmod -R 775 var/
```

### Composer Autoload Issues

If classes aren't being found, regenerate the autoloader:

```bash
composer dump-autoload
```

## Development Tips

1. **Use PHP Built-in Server** for quick development
2. **Enable Error Reporting** during development (already enabled in `pub/index.php`)
3. **Check Logs** in `var/logs/` for debugging
4. **Use Docker** for a consistent environment across team members

## Getting Help

- Check the [Examples](examples.md) for common patterns
- Review the [Juzdy Core Documentation](https://github.com/juzdy/core)
- Open an issue on GitHub
