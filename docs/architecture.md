# 🏗️ JUZDY Architecture Overview

Understanding JUZDY's architecture will help you build better applications faster. This guide explains how everything fits together.

---

## 🎯 The Big Picture

JUZDY is built on a simple yet powerful principle: **Convention over Configuration**. The framework handles the plumbing so you can focus on building features.

```
┌─────────────────────────────────────────────────────────────┐
│                      HTTP Request                            │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                   index.php (Entry Point)                    │
│  • Initialize Configuration                                  │
│  • Setup Error Handler                                       │
│  • Bootstrap Application                                     │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│              Dependency Injection Container                  │
│  • Manages object lifecycle                                  │
│  • Resolves dependencies                                     │
│  • Provides services                                         │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                  Middleware Pipeline                         │
│  ┌───────────────────────────────────────────────┐          │
│  │  1. CORS Middleware                           │          │
│  │  2. Custom Middleware (if any)                │          │
│  │  3. Router (Matches URL to Handler)           │          │
│  └───────────────────────────────────────────────┘          │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    Request Handler                           │
│  • Process request                                           │
│  • Execute business logic                                    │
│  • Generate response                                         │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                      HTTP Response                           │
└─────────────────────────────────────────────────────────────┘
```

---

## 🧩 Core Components

### 1. **Entry Point** (`pub/index.php`)

The application starts here. Every request flows through this single file.

**What it does:**
- Initializes the autoloader
- Loads configuration
- Sets up error handling
- Bootstraps the application

```php
// Simplified view of pub/index.php
require_once __DIR__ . '/../vendor/autoload.php';

// Load all config files
\Juzdy\Config::init(__DIR__ . '/../etc/config/*.php');

// Initialize error handling
\Juzdy\Error\ErrorHandler::init();

// Bootstrap and run
(new \Juzdy\Container\Container())
    ->get(Bootstrap::class)
    ->boot();
```

---

### 2. **Configuration System** (`etc/config/`)

All configuration lives in simple PHP files. JUZDY loads them automatically on startup.

**Key configuration files:**

| File | Purpose |
|------|---------|
| `config.php` | Main application settings |
| `middleware.php` | Middleware pipeline setup |
| `layout.php` | View and template settings |
| `db.php` | Database configuration |

**Configuration Merging:**

Files are loaded in glob order and merged. Use prefixes to control load order:
- `01-database.php` loads before `02-cache.php`

---

### 3. **Dependency Injection Container**

JUZDY includes a PSR-11 compliant DI container for managing object dependencies.

**Features:**
- ✅ Auto-wiring of constructor dependencies
- ✅ Service registration
- ✅ Singleton support
- ✅ Interface binding

**Example:**

```php
// Container automatically resolves dependencies
$container = new \Juzdy\Container\Container();

// Get a service (auto-wired)
$handler = $container->get(MyHandler::class);

// Manual registration
$container->set(DatabaseInterface::class, function() {
    return new MySQLDatabase();
});
```

---

### 4. **Middleware Pipeline**

Middleware processes requests before they reach your handlers and responses after they leave.

**The Pipeline Flow:**

```
Request → [CORS] → [Custom MW] → [Router] → Handler
                                               ↓
Response ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ←
```

**Global Middleware** (`etc/config/middleware.php`):

```php
'middleware' => [
    'global' => [
        \Juzdy\Http\Middleware\CorsMiddleware::class,
        \Juzdy\Http\Router::class, // Always last!
    ],
]
```

---

### 5. **Router**

The router automatically maps URLs to handler classes. No manual route definitions needed!

**URL Pattern:**

```
http://example.com/{HandlerName}/{SubHandler}?params=value
```

**Mapping Examples:**

| URL | Handler Class |
|-----|---------------|
| `/index` | `App\Http\Handler\Index` |
| `/user` | `App\Http\Handler\User` |
| `/api/products` | `App\Http\Handler\Api\Products` |
| `/blog/post` | `App\Http\Handler\Blog\Post` |

**Configuration:**

```php
'http' => [
    'request_handlers_namespace' => [
        '{namespace}\Http\Handler', // Default namespace
    ],
],
```

---

### 6. **Request Handlers**

Handlers contain your application logic. Each handler handles a specific request.

**Handler Anatomy:**

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;

class MyHandler extends Handler
{
    // Inject dependencies via constructor
    public function __construct(
        private DatabaseService $db,
        private CacheService $cache
    ) {}

    // Handle the request
    public function handle(RequestInterface $request): ResponseInterface
    {
        // 1. Get input
        $id = $request->query('id');
        
        // 2. Process
        $data = $this->db->find($id);
        
        // 3. Return response
        return $this->response()
            ->header('Content-Type', 'application/json')
            ->body(json_encode($data));
    }
}
```

---

## 🔄 Request/Response Cycle

Let's trace a complete request:

### Example: User visits `/products?category=electronics`

**Step 1: Entry Point**
- Request hits `pub/index.php`
- Configuration loaded
- Container initialized

**Step 2: Middleware Pipeline**
- CORS middleware adds headers
- Router extracts "products" from URL

**Step 3: Handler Resolution**
- Router looks for `App\Http\Handler\Products`
- Container creates handler instance
- Dependencies auto-injected

**Step 4: Handler Execution**
```php
class Products extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        $category = $request->query('category'); // "electronics"
        $products = $this->productRepo->findByCategory($category);
        
        return $this->response()
            ->body($this->renderTemplate('products', [
                'products' => $products
            ]));
    }
}
```

**Step 5: Response**
- Handler returns ResponseInterface
- Middleware can modify response
- Browser receives HTML/JSON

---

## 🌳 Directory Structure in Detail

### `app/` - Your Application Code

This is YOUR territory. All application-specific code lives here.

```
app/
├── src/
│   ├── Http/
│   │   └── Handler/          # HTTP request handlers
│   ├── Model/                # Your domain models (optional)
│   ├── Service/              # Business logic services (optional)
│   └── Repository/           # Data access (optional)
└── layout/
    ├── default/              # Default template
    └── errors/               # Error pages
```

### `etc/config/` - Configuration

All configuration in one place, versioned with your code.

```
etc/config/
├── config.php                # Main settings
├── middleware.php            # Middleware pipeline
├── layout.php                # View configuration
└── db.php                    # Database settings
```

### `pub/` - Public Web Root

Only files here are accessible via HTTP. This is your DocumentRoot.

```
pub/
├── .htaccess                 # Apache rules
├── index.php                 # Entry point
├── assets/                   # Optional: CSS, JS, images
└── uploads/                  # Optional: User uploads
```

### `var/` - Runtime Files

Generated files, caches, logs. Never commit these to version control.

```
var/
├── cache/                    # Application cache
├── logs/                     # Log files
└── sessions/                 # Session storage
```

---

## 🔌 Integration with juzdy/core

JUZDY is built on top of [juzdy/core](https://github.com/juzdy/core), which provides:

| Component | Purpose |
|-----------|---------|
| **PSR-11 Container** | Dependency injection |
| **PSR-14 Event Dispatcher** | Event system |
| **PSR-7 HTTP Messages** | Request/Response objects |
| **PSR-15 Middleware** | Request processing pipeline |
| **PSR-16 Simple Cache** | Caching interface |

**Why this matters:**

✅ **Standards-based**: Easy to swap implementations  
✅ **Interoperable**: Works with any PSR-compliant library  
✅ **Future-proof**: Based on widely-adopted standards  
✅ **Testable**: PSR interfaces make mocking easy

---

## 🎯 Key Design Principles

### 1. **Convention over Configuration**

File: `app/src/Http/Handler/Blog/Post.php`  
URL: `/blog/post`  
No configuration needed!

### 2. **Dependency Injection**

Dependencies declared in constructors, automatically resolved:

```php
public function __construct(
    private LoggerInterface $logger,
    private CacheInterface $cache
) {}
```

### 3. **Middleware Pipeline**

Chain of responsibility pattern for request processing:

```php
$request → Middleware1 → Middleware2 → Handler → Response
```

### 4. **Separation of Concerns**

- **Handlers**: Handle HTTP requests
- **Services**: Business logic
- **Repositories**: Data access
- **Models**: Domain entities

---

## 🚀 Performance Considerations

### Autoloading

JUZDY uses Composer's optimized autoloader:

```bash
composer dump-autoload -o  # Optimize for production
```

### Configuration Caching

Configuration is loaded once per request. For even better performance, implement config caching in production.

### Middleware Order

Middleware runs in order. Put expensive middleware later in the stack:

```php
'global' => [
    \Juzdy\Http\Middleware\CorsMiddleware::class,  // Fast
    \App\Middleware\AuthMiddleware::class,         // May hit DB
    \Juzdy\Http\Router::class,                     // Always last
]
```

---

## 🎓 Learning Path

Now that you understand the architecture:

1. ✅ You are here: **Architecture Overview**
2. 📖 Next: [Request Handlers](handlers.md)
3. 📖 Then: [Middleware](middleware.md)
4. 📖 Advanced: [Configuration](configuration.md)

---

## 💡 Pro Tips

1. **Keep handlers thin**: Move complex logic to services
2. **Use dependency injection**: Avoid manual instantiation
3. **Follow PSR standards**: Your code will thank you later
4. **Leverage middleware**: Don't repeat cross-cutting concerns
5. **Organize by feature**: Group related handlers in subdirectories

---

**Ready to build handlers?** Continue to [Request Handlers Guide](handlers.md) →
