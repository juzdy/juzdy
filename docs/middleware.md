# 🔗 Middleware Guide

Middleware in JUZDY provides a powerful mechanism for filtering and modifying HTTP requests and responses. Think of middleware as layers that wrap your application, processing requests on the way in and responses on the way out.

---

## 🎯 What is Middleware?

Middleware sits between the client and your request handlers, allowing you to:

- ✅ Authenticate requests
- ✅ Log requests and responses
- ✅ Add CORS headers
- ✅ Rate limit API calls
- ✅ Modify requests before they reach handlers
- ✅ Modify responses before they reach clients
- ✅ Handle errors globally

---

## 🔄 How Middleware Works

### The Pipeline

```
Request
   ↓
[Middleware 1] ──→ Process Request
   ↓
[Middleware 2] ──→ Process Request
   ↓
[Middleware 3] ──→ Process Request
   ↓
[Handler] ──────→ Generate Response
   ↓
[Middleware 3] ──→ Process Response
   ↓
[Middleware 2] ──→ Process Response
   ↓
[Middleware 1] ──→ Process Response
   ↓
Response to Client
```

Each middleware can:
1. Process the request
2. Pass it to the next middleware
3. Process the response
4. Return the modified response

---

## 🚀 Quick Start

### Creating Your First Middleware

Create `app/src/Http/Middleware/LoggingMiddleware.php`:

```php
<?php
namespace App\Http\Middleware;

use Juzdy\Http\MiddlewareInterface;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use Juzdy\Http\HandlerInterface;

class LoggingMiddleware implements MiddlewareInterface
{
    public function process(
        RequestInterface $request,
        HandlerInterface $handler
    ): ResponseInterface {
        // Before handler
        $startTime = microtime(true);
        $method = $request->method();
        $uri = $request->uri();
        
        // Call next middleware/handler
        $response = $handler->handle($request);
        
        // After handler
        $duration = microtime(true) - $startTime;
        error_log("$method $uri - {$response->status()} ({$duration}s)");
        
        return $response;
    }
}
```

### Registering Middleware

Add to `etc/config/middleware.php`:

```php
return [
    'middleware' => [
        'global' => [
            \App\Http\Middleware\LoggingMiddleware::class,
            \Juzdy\Http\Middleware\CorsMiddleware::class,
            \Juzdy\Http\Router::class, // Always last!
        ],
    ],
];
```

---

## 📖 Middleware Types

### 1. Global Middleware

Runs for **every** request:

```php
'middleware' => [
    'global' => [
        \Juzdy\Http\Middleware\CorsMiddleware::class,
        \App\Middleware\SecurityHeadersMiddleware::class,
        \App\Middleware\LoggingMiddleware::class,
        \Juzdy\Http\Router::class, // Must be last!
    ],
]
```

### 2. Group Middleware

Runs for handlers implementing specific interfaces:

```php
'middleware' => [
    'groups' => [
        // Middleware for authenticated handlers
        \App\Http\AuthenticatedInterface::class => [
            \App\Middleware\AuthMiddleware::class,
        ],
        
        // Middleware for API handlers
        \App\Http\ApiHandlerInterface::class => [
            \App\Middleware\RateLimitMiddleware::class,
            \App\Middleware\ApiAuthMiddleware::class,
        ],
    ],
]
```

**Using groups:**

```php
// Handler implements interface to trigger middleware
class AdminDashboard extends Handler implements AuthenticatedInterface
{
    // AuthMiddleware runs automatically!
    public function handle(RequestInterface $request): ResponseInterface
    {
        // User is already authenticated here
        return $this->response()->body('Welcome, admin!');
    }
}
```

---

## 💡 Common Middleware Patterns

### Authentication Middleware

```php
<?php
namespace App\Http\Middleware;

use Juzdy\Http\MiddlewareInterface;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use Juzdy\Http\HandlerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SessionService $session
    ) {}

    public function process(
        RequestInterface $request,
        HandlerInterface $handler
    ): ResponseInterface {
        // Check if user is authenticated
        if (!$this->session->isAuthenticated()) {
            return $this->redirectToLogin();
        }
        
        // User is authenticated, proceed
        return $handler->handle($request);
    }

    private function redirectToLogin(): ResponseInterface
    {
        return (new Response())
            ->status(302)
            ->header('Location', '/login');
    }
}
```

### CORS Middleware (Included)

```php
<?php
namespace Juzdy\Http\Middleware;

use Juzdy\Http\MiddlewareInterface;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use Juzdy\Http\HandlerInterface;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(
        RequestInterface $request,
        HandlerInterface $handler
    ): ResponseInterface {
        // Handle preflight
        if ($request->method() === 'OPTIONS') {
            return $this->preflightResponse();
        }
        
        // Add CORS headers to response
        $response = $handler->handle($request);
        
        return $response
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    private function preflightResponse(): ResponseInterface
    {
        return (new Response())
            ->status(204)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->header('Access-Control-Max-Age', '3600');
    }
}
```

### Rate Limiting Middleware

```php
<?php
namespace App\Http\Middleware;

use Juzdy\Http\MiddlewareInterface;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use Juzdy\Http\HandlerInterface;
use Psr\SimpleCache\CacheInterface;

class RateLimitMiddleware implements MiddlewareInterface
{
    private const MAX_REQUESTS = 100;
    private const WINDOW = 3600; // 1 hour

    public function __construct(
        private CacheInterface $cache
    ) {}

    public function process(
        RequestInterface $request,
        HandlerInterface $handler
    ): ResponseInterface {
        $ip = $this->getClientIp($request);
        $key = "rate_limit:{$ip}";
        
        $requests = (int)$this->cache->get($key, 0);
        
        if ($requests >= self::MAX_REQUESTS) {
            return $this->rateLimitExceeded();
        }
        
        // Increment counter
        $this->cache->set($key, $requests + 1, self::WINDOW);
        
        // Add rate limit headers
        $response = $handler->handle($request);
        
        return $response
            ->header('X-RateLimit-Limit', (string)self::MAX_REQUESTS)
            ->header('X-RateLimit-Remaining', (string)(self::MAX_REQUESTS - $requests - 1));
    }

    private function getClientIp(RequestInterface $request): string
    {
        // Note: Validate and sanitize IP addresses in production
        return $request->header('X-Forwarded-For') 
            ?? $request->header('X-Real-IP') 
            ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function rateLimitExceeded(): ResponseInterface
    {
        return (new Response())
            ->status(429)
            ->header('Content-Type', 'application/json')
            ->body(json_encode([
                'error' => 'Rate limit exceeded',
                'message' => 'Too many requests. Please try again later.',
            ]));
    }
}
```

### Request ID Middleware

```php
<?php
namespace App\Http\Middleware;

use Juzdy\Http\MiddlewareInterface;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use Juzdy\Http\HandlerInterface;

class RequestIdMiddleware implements MiddlewareInterface
{
    public function process(
        RequestInterface $request,
        HandlerInterface $handler
    ): ResponseInterface {
        // Generate unique request ID
        $requestId = $this->generateRequestId();
        
        // Store in request for later use
        $request->setAttribute('request_id', $requestId);
        
        // Process request
        $response = $handler->handle($request);
        
        // Add request ID to response
        return $response->header('X-Request-ID', $requestId);
    }

    private function generateRequestId(): string
    {
        return uniqid('req_', true);
    }
}
```

### JSON Response Middleware

```php
<?php
namespace App\Http\Middleware;

use Juzdy\Http\MiddlewareInterface;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use Juzdy\Http\HandlerInterface;

class JsonResponseMiddleware implements MiddlewareInterface
{
    public function process(
        RequestInterface $request,
        HandlerInterface $handler
    ): ResponseInterface {
        try {
            $response = $handler->handle($request);
            return $response;
            
        } catch (\Throwable $e) {
            // Convert exceptions to JSON responses
            return $this->jsonError($e);
        }
    }

    private function jsonError(\Throwable $e): ResponseInterface
    {
        $status = $e->getCode() ?: 500;
        
        return (new Response())
            ->status($status)
            ->header('Content-Type', 'application/json')
            ->body(json_encode([
                'error' => $e->getMessage(),
                'code' => $status,
            ]));
    }
}
```

### Security Headers Middleware

```php
<?php
namespace App\Http\Middleware;

use Juzdy\Http\MiddlewareInterface;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use Juzdy\Http\HandlerInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(
        RequestInterface $request,
        HandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);
        
        return $response
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-Frame-Options', 'DENY')
            ->header('X-XSS-Protection', '1; mode=block')
            ->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains')
            ->header('Referrer-Policy', 'no-referrer-when-downgrade')
            ->header('Content-Security-Policy', "default-src 'self'");
    }
}
```

### Cache Control Middleware

```php
<?php
namespace App\Http\Middleware;

use Juzdy\Http\MiddlewareInterface;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use Juzdy\Http\HandlerInterface;

class CacheControlMiddleware implements MiddlewareInterface
{
    public function process(
        RequestInterface $request,
        HandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);
        
        // Add cache headers based on response status
        if ($response->status() === 200) {
            return $response->header('Cache-Control', 'public, max-age=3600');
        }
        
        // Don't cache errors
        return $response->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
```

---

## 🔧 Advanced Techniques

### Conditional Middleware Execution

```php
class MaintenanceModeMiddleware implements MiddlewareInterface
{
    public function process(
        RequestInterface $request,
        HandlerInterface $handler
    ): ResponseInterface {
        // Skip for admin IPs
        if ($this->isAdminIp($request)) {
            return $handler->handle($request);
        }
        
        // Check maintenance mode
        if ($this->isMaintenanceMode()) {
            return $this->maintenanceResponse();
        }
        
        return $handler->handle($request);
    }

    private function isMaintenanceMode(): bool
    {
        return file_exists(__DIR__ . '/../../../var/.maintenance');
    }

    private function isAdminIp(RequestInterface $request): bool
    {
        $adminIps = ['127.0.0.1', '::1'];
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        return in_array($clientIp, $adminIps);
    }

    private function maintenanceResponse(): ResponseInterface
    {
        return (new Response())
            ->status(503)
            ->header('Retry-After', '3600')
            ->body('Site is under maintenance. Please check back later.');
    }
}
```

### Modifying Request

```php
class ApiVersionMiddleware implements MiddlewareInterface
{
    public function process(
        RequestInterface $request,
        HandlerInterface $handler
    ): ResponseInterface {
        // Extract API version from header
        $version = $request->header('X-API-Version') ?? 'v1';
        
        // Store in request attribute for handlers to use
        $request->setAttribute('api_version', $version);
        
        return $handler->handle($request);
    }
}
```

---

## 🎯 Best Practices

### 1. Order Matters

Put fast, simple middleware first:

```php
'global' => [
    \App\Middleware\MaintenanceModeMiddleware::class,  // Fast check
    \Juzdy\Http\Middleware\CorsMiddleware::class,      // Simple headers
    \App\Middleware\AuthMiddleware::class,             // May hit DB/cache
    \App\Middleware\LoggingMiddleware::class,          // I/O operation
    \Juzdy\Http\Router::class,                         // Always last!
]
```

### 2. Single Responsibility

Each middleware should do ONE thing well:

❌ **Don't:**
```php
class MegaMiddleware implements MiddlewareInterface
{
    // Handles auth, logging, CORS, rate limiting, etc.
}
```

✅ **Do:**
```php
class AuthMiddleware implements MiddlewareInterface { }
class LoggingMiddleware implements MiddlewareInterface { }
class CorsMiddleware implements MiddlewareInterface { }
```

### 3. Early Returns

Return early for exceptional cases:

```php
public function process(
    RequestInterface $request,
    HandlerInterface $handler
): ResponseInterface {
    // Check condition
    if (!$this->isAllowed($request)) {
        return $this->forbiddenResponse();
    }
    
    // Normal flow
    return $handler->handle($request);
}
```

### 4. Use Dependency Injection

```php
class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private UserRepository $users,
        private SessionService $session
    ) {}
    
    public function process(...): ResponseInterface
    {
        // Use injected dependencies
        $user = $this->users->findById($this->session->getUserId());
    }
}
```

### 5. Don't Swallow Exceptions

```php
public function process(
    RequestInterface $request,
    HandlerInterface $handler
): ResponseInterface {
    try {
        return $handler->handle($request);
    } catch (SpecificException $e) {
        // Handle specific exceptions
        return $this->handleError($e);
    }
    // Let other exceptions bubble up!
}
```

---

## 🚨 Common Pitfalls

### ❌ Forgetting to Call Next Handler

```php
// BAD: Request never reaches handler
public function process(
    RequestInterface $request,
    HandlerInterface $handler
): ResponseInterface {
    $this->log($request);
    return new Response(); // ❌ Handler never called!
}
```

```php
// GOOD
public function process(
    RequestInterface $request,
    HandlerInterface $handler
): ResponseInterface {
    $this->log($request);
    return $handler->handle($request); // ✅ Handler called
}
```

### ❌ Modifying Response Incorrectly

```php
// BAD: Original response lost
public function process(
    RequestInterface $request,
    HandlerInterface $handler
): ResponseInterface {
    $handler->handle($request); // ❌ Response discarded
    return new Response()->body('Oops');
}
```

```php
// GOOD
public function process(
    RequestInterface $request,
    HandlerInterface $handler
): ResponseInterface {
    $response = $handler->handle($request); // ✅ Capture response
    return $response->header('X-Custom', 'value');
}
```

---

## 📊 Middleware Execution Order

### Example Configuration

```php
'middleware' => [
    'global' => [
        \App\Middleware\Middleware1::class,
        \App\Middleware\Middleware2::class,
        \Juzdy\Http\Router::class,
    ],
]
```

### Execution Flow

```
Request
  ↓
Middleware1::process() [before handler]
  ↓
Middleware2::process() [before handler]
  ↓
Router::process() [finds handler]
  ↓
Handler::handle() [generates response]
  ↓
Router::process() [after handler]
  ↓
Middleware2::process() [after handler]
  ↓
Middleware1::process() [after handler]
  ↓
Response
```

---

## 🎓 Next Steps

- 📖 [Configuration Guide](configuration.md)
- 📖 [Request Handlers](handlers.md)
- 📖 [Deployment Guide](deployment.md)

---

**Ready to deploy?** Continue to [Deployment Guide](deployment.md) →
