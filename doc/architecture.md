# Architecture Overview

This document explains the architecture and design principles of the Juzdy framework and project template.

## Framework Philosophy

Juzdy is built on these core principles:

1. **PSR Compliance**: Follows PHP-FIG standards for interoperability
2. **Dependency Injection**: Everything is resolved through the DI container
3. **Event-Driven**: Uses events for extensibility
4. **Middleware-Based**: HTTP requests flow through a middleware pipeline
5. **Convention over Configuration**: Sensible defaults with flexibility to customize

## High-Level Architecture

```
┌─────────────────────────────────────────────────────┐
│                   HTTP Request                       │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│              pub/index.php (Entry Point)            │
│  - Session start                                     │
│  - Error reporting                                   │
│  - Autoload                                          │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│          Configuration Loading (Config)              │
│  - Loads all etc/config/*.php files                 │
│  - Merges configurations                            │
│  - Resolves dynamic references (@{...})             │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│            Error Handler Initialization              │
│  - Sets up custom error handling                    │
│  - Configures error pages                           │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│       Bootstrap & Dependency Injection              │
│  - Container initialization                         │
│  - Dependency resolution                            │
│  - Application boot                                 │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│          Middleware Pipeline (PSR-15)               │
│  ┌───────────────────────────────────────┐          │
│  │  1. CorsMiddleware                     │          │
│  │  2. Custom Global Middleware           │          │
│  │  3. Router (last in global)            │          │
│  │     - Determines handler               │          │
│  │     - Applies group middleware         │          │
│  └───────────────────────────────────────┘          │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│           Request Handler (Your Code)               │
│  - Process request                                   │
│  - Access services via DI                           │
│  - Generate response                                │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│                   HTTP Response                      │
└─────────────────────────────────────────────────────┘
```

## Core Components

### 1. Bootstrap (`Juzdy\Bootstrap`)

The Bootstrap class is responsible for initializing and starting the application. It:

- Discovers and loads bootstrap classes from installed packages
- Initializes the HTTP application
- Handles both HTTP and CLI contexts

### 2. Dependency Injection Container

The container (`Juzdy\Container\Container`) provides:

- **Automatic Resolution**: Automatically resolves dependencies based on type hints
- **Attribute-Based Configuration**: Use `#[Preference]`, `#[Shared]`, `#[Using]` attributes
- **Plugin Architecture**: Extensible through plugin managers
- **Lazy Loading**: Support for lazy initialization with Ghost Factory
- **PSR-11 Compliance**: Implements ContainerInterface

#### Example of DI in Action

```php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use App\Service\UserService;
use App\Repository\UserRepository;

class UserHandler extends Handler
{
    // Dependencies are automatically injected
    public function __construct(
        private UserService $userService,
        private UserRepository $userRepository
    ) {}
    
    public function handle(RequestInterface $request): ResponseInterface
    {
        $users = $this->userService->getAllUsers();
        return $this->response($users);
    }
}
```

### 3. Configuration System (`Juzdy\Config`)

Configuration management features:

- **File-Based**: Multiple PHP files in `etc/config/`
- **Merge Strategy**: All configs are merged into a single array
- **Dynamic References**: Use `@{key}` syntax to reference other config values
- **Namespace Support**: Use `{namespace}` placeholder

Example:

```php
// etc/config/config.php
return [
    'root' => '/var/www/app',
    'path' => [
        'var' => '@{root}/var',           // Resolved to: /var/www/app/var
        'logs' => '@{path.var}/logs',     // Resolved to: /var/www/app/var/logs
    ],
];
```

### 4. HTTP Layer

#### Router (`Juzdy\Http\Router`)

The router:

- Determines which handler to invoke based on the URL
- Supports handler namespace configuration
- Applies group middleware based on handler interfaces
- Is itself a middleware component

**URL to Handler Mapping:**

```
http://example.com/Index      → App\Http\Handler\Index
http://example.com/User/List  → App\Http\Handler\User\List
http://example.com/api/Users  → App\Http\Handler\Api\Users
```

#### Handlers (`Juzdy\Http\Handler`)

Base class for all request handlers providing:

- Response helpers (`response()`, `redirect()`, `json()`)
- Access to layout rendering
- Dependency injection support

### 5. Middleware System (PSR-15)

Middleware implements the chain of responsibility pattern:

**Types of Middleware:**

1. **Global Middleware**: Runs for all requests
2. **Group Middleware**: Applied based on handler interfaces

**Built-in Middleware:**

- `CorsMiddleware`: Handles CORS headers
- `RateLimitMiddleware`: Rate limiting
- `SecurityHeadersMiddleware`: Security headers
- `Router`: Routes requests to handlers (always last in global)

### 6. Event System (PSR-14)

The framework uses an event-driven architecture:

- **EventDispatcher**: Dispatches events to listeners
- **ListenerProvider**: Manages event listeners
- **Stoppable Events**: Support for event propagation control

Events are used throughout the framework for:
- Pre/post bootstrap hooks
- HTTP request/response events
- Model lifecycle events (before/after save, delete, etc.)

### 7. Model Layer (ORM)

The ORM provides:

- **Active Record Pattern**: Models represent database rows
- **Lifecycle Hooks**: Before/after save, delete callbacks
- **Query Builder**: Fluent interface for building queries
- **Relationships**: Support for associations between models

### 8. Layout/View System

Template rendering with:

- **Layouts**: Reusable page layouts
- **Blocks**: Template inheritance with blocks
- **Asset Management**: CSS/JS asset handling
- **View Helpers**: Helper functions for common tasks

## Request Lifecycle

Let's trace a complete request through the system:

### 1. Request Arrives

```
GET /User/Profile?id=123
```

### 2. Entry Point (`pub/index.php`)

```php
session_start();
error_reporting(E_ALL);
require_once realpath(__DIR__ . '/../vendor/autoload.php');
```

### 3. Configuration Loading

```php
\Juzdy\Config::init(__DIR__ . '/../etc/config/*.php');
// Loads: config.php, db.php, layout.php, middleware.php
```

### 4. Error Handler Setup

```php
\Juzdy\Error\ErrorHandler::init();
```

### 5. Bootstrap

```php
$container = new \Juzdy\Container\Container();
$bootstrap = $container->get(Bootstrap::class);
$bootstrap->boot();
```

### 6. Middleware Pipeline

```php
CorsMiddleware → Router → Handler
```

### 7. Router Determines Handler

```
URL: /User/Profile
Handler: App\Http\Handler\User\Profile
```

### 8. Handler Execution

```php
class Profile extends Handler
{
    public function __construct(private UserService $service) {}
    
    public function handle(RequestInterface $request): ResponseInterface
    {
        $id = $request->getParam('id');
        $user = $this->service->getUser($id);
        return $this->response(['user' => $user]);
    }
}
```

### 9. Response Sent

```json
{
  "user": {
    "id": 123,
    "name": "John Doe"
  }
}
```

## Directory Structure and Responsibilities

```
juzdy/
├── app/                          # Application Layer
│   ├── layout/                   # View templates
│   │   ├── default/             # Default layout
│   │   └── errors/              # Error pages
│   └── src/                     # Source code
│       ├── Http/
│       │   └── Handler/        # Request handlers
│       ├── Service/            # Business logic services
│       ├── Repository/         # Data access layer
│       └── Model/              # Domain models
│
├── bin/                         # Executable scripts
│   ├── docker-start.sh         # Docker utility
│   └── docker-stop.sh
│
├── etc/                         # Configuration Layer
│   └── config/
│       ├── config.php          # Core configuration
│       ├── db.php              # Database config
│       ├── layout.php          # Layout config
│       └── middleware.php      # Middleware config
│
├── pub/                         # Public Layer (Web Root)
│   ├── index.php               # Entry point
│   ├── .htaccess               # Apache rules
│   ├── css/                    # Static CSS
│   ├── js/                     # Static JavaScript
│   └── images/                 # Static images
│
├── var/                         # Variable Data
│   ├── logs/                   # Application logs
│   ├── cache/                  # Cache files
│   └── session/                # Session data
│
└── vendor/                      # Dependencies
    └── juzdy/core/             # Juzdy Core framework
```

## Design Patterns Used

1. **Dependency Injection**: Constructor injection throughout
2. **Front Controller**: Single entry point (`pub/index.php`)
3. **Chain of Responsibility**: Middleware pipeline
4. **Factory Pattern**: Container creates objects
5. **Active Record**: Model layer
6. **Template Method**: Handler base class
7. **Observer**: Event system
8. **Strategy**: Pluggable middleware

## Extending the Framework

### Add Custom Middleware

```php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CustomMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Before handler
        $response = $handler->handle($request);
        // After handler
        return $response;
    }
}
```

Register in `etc/config/middleware.php`:

```php
'middleware' => [
    'global' => [
        \App\Middleware\CustomMiddleware::class,
        \Juzdy\Http\Router::class,
    ],
]
```

### Add Event Listeners

```php
use Juzdy\EventBus\EventDispatcher;

$dispatcher->listen(MyEvent::class, function($event) {
    // Handle event
});
```

## Performance Considerations

1. **Autoloader Optimization**: Use `composer dump-autoload -o` in production
2. **Configuration Caching**: Consider caching merged configuration
3. **Lazy Loading**: Container supports lazy initialization
4. **Middleware Order**: Put fast middleware first
5. **Database Queries**: Use query builder for optimal queries

## Security Features

1. **Error Handling**: Custom error pages (no stack traces in production)
2. **CORS**: Built-in CORS middleware
3. **Security Headers**: Available middleware for security headers
4. **Rate Limiting**: Built-in rate limiting support
5. **Input Validation**: Validate all user input in handlers

## Next Steps

- Learn about [HTTP Handlers](http-handlers.md)
- Explore [Middleware](middleware.md)
- Understand [Configuration](configuration.md)
- Work with [Database](database.md)
