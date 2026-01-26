# Juzdy - PHP Project Template

A modern, lightweight PHP project template built on the [Juzdy Core](https://github.com/juzdy/core) framework. Get started quickly with a clean architecture, dependency injection, PSR-compliance, and event-driven design.

## 🚀 Quick Start

### Prerequisites
- PHP >= 8.0
- Composer
- MySQL (optional, for database functionality)

### Installation

1. **Clone or use this template:**
   ```bash
   composer create-project juzdy/juzdy my-project
   cd my-project
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Start the development server:**
   ```bash
   php -S localhost:8000 -t pub
   ```

4. **Visit your application:**
   Open http://localhost:8000 in your browser. You should see "Hello from Index Handler!"

### Using Docker

For a complete development environment with PHP and MySQL:

```bash
./bin/docker-start.sh
```

Visit http://localhost:8080 to see your application. See [DOCKER.md](DOCKER.md) for more details.

## 📖 Documentation

Complete documentation is available in the [doc/](doc/) directory:

- **[Getting Started](doc/getting-started.md)** - Installation and first steps
- **[Architecture](doc/architecture.md)** - Understanding the framework structure
- **[Configuration](doc/configuration.md)** - Managing application configuration
- **[HTTP Handlers](doc/http-handlers.md)** - Creating request handlers and routes
- **[Middleware](doc/middleware.md)** - Using and creating middleware
- **[Database](doc/database.md)** - Working with models and database
- **[Layouts & Views](doc/layouts.md)** - Template rendering and assets
- **[Docker Development](DOCKER.md)** - Docker setup and workflow
- **[Deployment](doc/deployment.md)** - Deploying to production
- **[Examples](doc/examples.md)** - Code examples and patterns

## ✨ What's Included

This template provides a ready-to-use structure with:

- ✅ **HTTP Request Handling** - PSR-15 middleware and routing
- ✅ **Dependency Injection** - Advanced DI container with attributes
- ✅ **Configuration System** - Flexible, file-based configuration
- ✅ **Error Handling** - Built-in error handler with custom pages
- ✅ **Layout System** - Template rendering with asset management
- ✅ **Docker Support** - Complete Docker development environment
- ✅ **PSR Compliance** - Following PHP standards (PSR-4, PSR-11, PSR-14, PSR-15)

## 📁 Project Structure

```
juzdy/
├── app/                      # Application code
│   ├── layout/              # View templates
│   │   ├── default/         # Default layout templates
│   │   └── errors/          # Error page templates
│   └── src/                 # Source code
│       └── Http/            # HTTP layer
│           └── Handler/     # Request handlers
├── bin/                     # Executable scripts
├── doc/                     # Documentation
├── etc/                     # Configuration
│   └── config/             # Config files
│       ├── config.php      # Main configuration
│       ├── db.php          # Database configuration
│       ├── layout.php      # Layout configuration
│       └── middleware.php  # Middleware configuration
├── pub/                     # Public directory (web root)
│   ├── .htaccess           # Apache rewrite rules
│   └── index.php           # Application entry point
├── var/                     # Variable data (logs, cache)
├── composer.json            # Dependencies
└── README.md               # This file
```

## 🎯 Core Features

### Request Handlers

Create HTTP handlers by extending the base `Handler` class:

```php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;

class MyHandler extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        return $this->response()
            ->header('Content-Type', 'application/json')
            ->body(json_encode(['message' => 'Hello World']));
    }
}
```

Access via: `http://localhost:8000/MyHandler`

### Middleware Pipeline

Configure middleware in `etc/config/middleware.php`:

```php
return [
    'middleware' => [
        'global' => [
            \Juzdy\Http\Middleware\CorsMiddleware::class,
            \Juzdy\Http\Router::class
        ],
    ],
];
```

### Dependency Injection

Dependencies are automatically resolved:

```php
class MyHandler extends Handler
{
    public function __construct(
        private MyService $service,
        private DatabaseConnection $db
    ) {}
}
```

## 🔗 Links

- **Core Framework:** [juzdy/core](https://github.com/juzdy/core)
- **Example Project:** [skibimad/http](https://github.com/skibimad/http)
- **Full Documentation:** [doc/](doc/)

## 📝 License

This project is open-sourced software licensed under the MIT license.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 💡 Getting Help

- Read the [documentation](doc/)
- Check the [examples](doc/examples.md)
- Review the [juzdy/core documentation](https://github.com/juzdy/core)
- Open an issue on GitHub

---

Built with ❤️ using [Juzdy Core](https://github.com/juzdy/core)
