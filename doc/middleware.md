# Middleware

Middleware provides a convenient mechanism for filtering HTTP requests entering your application. Juzdy implements the PSR-15 middleware standard.

## Overview

Middleware is code that runs:
- **Before** your handler processes the request
- **After** your handler generates a response
- Both before and after

Common use cases:
- Authentication
- CORS handling
- Rate limiting
- Request logging
- Response modification
- Security headers

## How Middleware Works

```
Request → Middleware 1 → Middleware 2 → Handler → Response
            ↓                ↓                        ↑
            └────────────────┴────────────────────────┘
           (Can modify request/response)
```

Each middleware can:
1. Inspect/modify the request
2. Pass control to the next middleware
3. Inspect/modify the response
4. Short-circuit the pipeline and return early

## Configuration

Middleware is configured in `etc/config/middleware.php`:

```php
<?php
return [
    'middleware' => [
        // Global middleware (runs for all requests)
        'global' => [
            \Juzdy\Http\Middleware\CorsMiddleware::class,
            \App\Middleware\LoggingMiddleware::class,
            \Juzdy\Http\Router::class,  // Must be last
        ],
        
        // Group middleware (applies to handlers implementing specific interfaces)
        'groups' => [
            \App\Interface\AuthenticatableInterface::class => [
                \App\Middleware\AuthMiddleware::class,
            ],
            \App\Interface\AdminInterface::class => [
                \App\Middleware\AdminMiddleware::class,
            ],
        ],
    ],
];
```

### Global Middleware

Runs for **all requests**. Order matters - middleware executes in the order defined.

```php
'global' => [
    \Juzdy\Http\Middleware\CorsMiddleware::class,       // First
    \App\Middleware\LoggingMiddleware::class,           // Second
    \Juzdy\Http\Router::class,                          // Last (required)
]
```

**Important:** The `Router` middleware must always be last in the global stack!

### Group Middleware

Applies to handlers that implement specific interfaces:

```php
'groups' => [
    \App\Interface\AuthenticatableInterface::class => [
        \App\Middleware\AuthMiddleware::class,
    ],
]
```

Then in your handler:

```php
use App\Interface\AuthenticatableInterface;

class AdminDashboard extends Handler implements AuthenticatableInterface
{
    // This handler will have AuthMiddleware applied
}
```

## Creating Middleware

### Basic Middleware

Implement `Psr\Http\Server\MiddlewareInterface`:

```php
<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LoggingMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Before handler
        $startTime = microtime(true);
        error_log("Request: " . $request->getMethod() . " " . $request->getUri());
        
        // Call next middleware/handler
        $response = $handler->handle($request);
        
        // After handler
        $duration = microtime(true) - $startTime;
        error_log("Response: " . $response->getStatusCode() . " ({$duration}s)");
        
        return $response;
    }
}
```

### Middleware with Dependency Injection

```php
<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Service\AuthService;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $token = $request->getHeader('Authorization')[0] ?? null;
        
        if (!$token || !$this->authService->validateToken($token)) {
            // Return 401 Unauthorized
            return new \Juzdy\Http\Response(
                401,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => 'Unauthorized'])
            );
        }
        
        // Add user to request attributes
        $user = $this->authService->getUserFromToken($token);
        $request = $request->withAttribute('user', $user);
        
        return $handler->handle($request);
    }
}
```

### Middleware that Modifies Response

```php
<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);
        
        return $response
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-XSS-Protection', '1; mode=block')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Content-Security-Policy', "default-src 'self'");
    }
}
```

## Built-in Middleware

### CorsMiddleware

Handles Cross-Origin Resource Sharing (CORS):

```php
\Juzdy\Http\Middleware\CorsMiddleware::class
```

Adds appropriate CORS headers to responses.

### Router Middleware

Routes requests to handlers:

```php
\Juzdy\Http\Router::class
```

**Must always be the last middleware in the global stack!**

### Other Available Middleware

Check the [Juzdy Core documentation](https://github.com/juzdy/core) for:
- `RateLimitMiddleware` - Rate limiting
- `SecurityHeadersMiddleware` - Security headers
- `CompressionMiddleware` - Response compression

## Advanced Patterns

### Conditional Middleware

```php
<?php
namespace App\Middleware;

class ConditionalMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Only apply to API routes
        if (str_starts_with($request->getUri()->getPath(), '/api')) {
            // API-specific logic
            $request = $request->withHeader('X-Api-Request', 'true');
        }
        
        return $handler->handle($request);
    }
}
```

### Middleware with Configuration

```php
<?php
namespace App\Middleware;

use Juzdy\Config;

class RateLimitMiddleware implements MiddlewareInterface
{
    private int $maxRequests;
    private int $perSeconds;
    
    public function __construct()
    {
        $this->maxRequests = Config::get('rate_limit.max_requests', 60);
        $this->perSeconds = Config::get('rate_limit.per_seconds', 60);
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        
        // Check rate limit
        if ($this->isRateLimited($ip)) {
            return new \Juzdy\Http\Response(
                429,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => 'Too many requests'])
            );
        }
        
        return $handler->handle($request);
    }
    
    private function isRateLimited(string $ip): bool
    {
        // Implement rate limiting logic
        // (use cache, Redis, etc.)
        return false;
    }
}
```

### Request Transformation Middleware

```php
<?php
namespace App\Middleware;

class JsonBodyParserMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $contentType = $request->getHeader('Content-Type')[0] ?? '';
        
        if (str_contains($contentType, 'application/json')) {
            $body = $request->getBody()->getContents();
            $data = json_decode($body, true);
            
            if ($data !== null) {
                $request = $request->withParsedBody($data);
            }
        }
        
        return $handler->handle($request);
    }
}
```

### Exception Handling Middleware

```php
<?php
namespace App\Middleware;

class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        try {
            return $handler->handle($request);
        } catch (\Exception $e) {
            error_log("Error: " . $e->getMessage());
            
            $statusCode = $e->getCode() ?: 500;
            
            return new \Juzdy\Http\Response(
                $statusCode,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'error' => 'Internal Server Error',
                    'message' => $e->getMessage(),
                ])
            );
        }
    }
}
```

## Middleware Order

The order of middleware matters! They execute like nested functions:

```php
'global' => [
    A::class,  // First in, last out
    B::class,  // Second in, second to last out
    C::class,  // Third in, third to last out
    Router::class,  // Last (required)
]
```

Execution flow:
```
Request
  → A (before)
    → B (before)
      → C (before)
        → Handler
      ← C (after)
    ← B (after)
  ← A (after)
Response
```

### Best Order for Common Middleware

```php
'global' => [
    \App\Middleware\ErrorHandlerMiddleware::class,      // Catch all errors
    \App\Middleware\LoggingMiddleware::class,           // Log requests/responses
    \Juzdy\Http\Middleware\CorsMiddleware::class,       // CORS
    \App\Middleware\SecurityHeadersMiddleware::class,   // Security headers
    \App\Middleware\JsonBodyParserMiddleware::class,    // Parse JSON bodies
    \App\Middleware\RateLimitMiddleware::class,         // Rate limiting
    \Juzdy\Http\Router::class,                          // Router (must be last)
]
```

## Using Request Attributes

Pass data between middleware and handlers using request attributes:

**In Middleware:**
```php
$user = $this->authService->getUser($token);
$request = $request->withAttribute('user', $user);
return $handler->handle($request);
```

**In Handler:**
```php
public function handle(RequestInterface $request): ResponseInterface
{
    $user = $request->getAttribute('user');
    // Use $user...
}
```

## Short-Circuiting

Middleware can return a response without calling the next handler:

```php
public function process(
    ServerRequestInterface $request,
    RequestHandlerInterface $handler
): ResponseInterface {
    if ($this->shouldBlock($request)) {
        // Don't call $handler->handle()
        return new Response(403, [], 'Forbidden');
    }
    
    return $handler->handle($request);
}
```

## Testing Middleware

### Unit Test Example

```php
<?php
namespace Tests\Middleware;

use PHPUnit\Framework\TestCase;
use App\Middleware\AuthMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddlewareTest extends TestCase
{
    public function testAuthenticationFails()
    {
        $middleware = new AuthMiddleware();
        
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeader')->willReturn([]);
        
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');
        
        $response = $middleware->process($request, $handler);
        
        $this->assertEquals(401, $response->getStatusCode());
    }
}
```

## Best Practices

1. **Keep Middleware Focused**: Each middleware should have a single responsibility
2. **Order Matters**: Think carefully about execution order
3. **Don't Block**: Avoid heavy operations in middleware
4. **Use Attributes**: Pass data via request attributes, not global state
5. **Handle Errors**: Consider error cases
6. **Document Behavior**: Explain what the middleware does
7. **Test Thoroughly**: Write tests for middleware logic

## Common Use Cases

### 1. API Authentication

```php
class ApiAuthMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $apiKey = $request->getHeader('X-Api-Key')[0] ?? null;
        
        if (!$this->validateApiKey($apiKey)) {
            return new Response(401, [], 'Invalid API Key');
        }
        
        return $handler->handle($request);
    }
}
```

### 2. Request ID Tracking

```php
class RequestIdMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $requestId = uniqid('req_', true);
        $request = $request->withAttribute('request_id', $requestId);
        
        $response = $handler->handle($request);
        
        return $response->withHeader('X-Request-Id', $requestId);
    }
}
```

### 3. Content Negotiation

```php
class ContentNegotiationMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $accept = $request->getHeader('Accept')[0] ?? 'application/json';
        $request = $request->withAttribute('response_format', $accept);
        
        return $handler->handle($request);
    }
}
```

## Next Steps

- Learn about [HTTP Handlers](http-handlers.md)
- Understand [Architecture](architecture.md)
- Explore [Examples](examples.md)
- Configure [Database](database.md)
