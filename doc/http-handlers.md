# HTTP Handlers

HTTP Handlers are the core of your application's request processing. They receive HTTP requests and return HTTP responses.

## Overview

In Juzdy, handlers are classes that:
- Extend `Juzdy\Http\Handler`
- Implement the `handle(RequestInterface $request): ResponseInterface` method
- Are automatically discovered based on URL structure
- Support dependency injection

## Creating a Handler

### Basic Handler

Create a file in `app/src/Http/Handler/`:

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;

class Welcome extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        return $this->response()
            ->header('Content-Type', 'text/plain')
            ->body('Welcome to Juzdy!');
    }
}
```

**Access:** `http://localhost:8000/Welcome`

### Handler with Dependency Injection

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use App\Service\ProductService;

class Products extends Handler
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function handle(RequestInterface $request): ResponseInterface
    {
        $products = $this->productService->getAll();
        return $this->response($products);
    }
}
```

Dependencies are automatically resolved by the container.

## URL Routing

### Default Routing Convention

The framework uses convention-based routing:

```
URL Pattern                    → Handler Class
─────────────────────────────────────────────────────────
/Index                        → App\Http\Handler\Index
/Welcome                      → App\Http\Handler\Welcome
/User/Profile                 → App\Http\Handler\User\Profile
/Admin/Dashboard              → App\Http\Handler\Admin\Dashboard
/api/v1/Products              → App\Http\Handler\Api\V1\Products
```

### Namespace Configuration

Configure handler namespaces in `etc/config/config.php`:

```php
'http' => [
    'request_handlers_namespace' => [
        '{namespace}\Http\Handler',  // {namespace} = App by default
    ],
],
```

To add multiple namespaces:

```php
'http' => [
    'request_handlers_namespace' => [
        'App\Http\Handler',
        'MyCompany\Handlers',
    ],
],
```

### Nested Handlers

Create directory structures for organized routing:

```
app/src/Http/Handler/
├── Index.php                  # /Index
├── User/
│   ├── Profile.php           # /User/Profile
│   ├── Settings.php          # /User/Settings
│   └── Dashboard.php         # /User/Dashboard
└── Api/
    ├── Users.php             # /Api/Users
    └── Products.php          # /Api/Products
```

## Request Handling

### Accessing Request Data

```php
public function handle(RequestInterface $request): ResponseInterface
{
    // Query parameters (?name=value)
    $name = $request->getParam('name');
    $id = $request->getParam('id', 'default_value');
    
    // All query parameters
    $allParams = $request->getParams();
    
    // POST data
    $postData = $request->getPost();
    $email = $request->getPost('email');
    
    // Request method
    $method = $request->getMethod(); // GET, POST, PUT, etc.
    
    // Headers
    $contentType = $request->getHeader('Content-Type');
    $allHeaders = $request->getHeaders();
    
    // Path
    $path = $request->getPath(); // e.g., "/User/Profile"
    $uri = $request->getUri();
    
    // ...
}
```

### Reading Request Body

```php
// Raw body
$body = $request->getBody();

// JSON body
$data = json_decode($request->getBody(), true);

// For file uploads
$files = $request->getFiles();
```

## Response Handling

### Simple Text Response

```php
return $this->response()
    ->header('Content-Type', 'text/plain')
    ->body('Hello World');
```

### JSON Response

```php
// Using helper method
return $this->response([
    'status' => 'success',
    'data' => $products
]);

// Explicit JSON
return $this->response()
    ->header('Content-Type', 'application/json')
    ->body(json_encode(['status' => 'success']));
```

### HTML Response with Layout

```php
return $this->response()
    ->layout('default')  // Use layout from app/layout/default/
    ->template('products/list.phtml')
    ->assign('products', $products)
    ->assign('title', 'Product List');
```

### Redirect Response

```php
// Simple redirect
return $this->redirect('/User/Dashboard');

// With status code
return $this->redirect('/Login', 302);

// External redirect
return $this->redirect('https://example.com');
```

### Custom Status Codes

```php
// 404 Not Found
return $this->response()
    ->status(404)
    ->body('Resource not found');

// 201 Created
return $this->response($newResource)
    ->status(201);

// 500 Internal Server Error
return $this->response()
    ->status(500)
    ->body('Something went wrong');
```

### Custom Headers

```php
return $this->response($data)
    ->header('Cache-Control', 'no-cache')
    ->header('X-Custom-Header', 'value')
    ->header('Access-Control-Allow-Origin', '*');
```

## Advanced Patterns

### RESTful API Handler

```php
<?php
namespace App\Http\Handler\Api;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use App\Service\UserService;

class Users extends Handler
{
    public function __construct(
        private UserService $userService
    ) {}

    public function handle(RequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        
        return match($method) {
            'GET' => $this->handleGet($request),
            'POST' => $this->handlePost($request),
            'PUT' => $this->handlePut($request),
            'DELETE' => $this->handleDelete($request),
            default => $this->response(['error' => 'Method not allowed'])->status(405)
        };
    }
    
    private function handleGet(RequestInterface $request): ResponseInterface
    {
        $id = $request->getParam('id');
        
        if ($id) {
            $user = $this->userService->getById($id);
            return $user ? $this->response($user) : 
                          $this->response(['error' => 'Not found'])->status(404);
        }
        
        $users = $this->userService->getAll();
        return $this->response($users);
    }
    
    private function handlePost(RequestInterface $request): ResponseInterface
    {
        $data = json_decode($request->getBody(), true);
        $user = $this->userService->create($data);
        return $this->response($user)->status(201);
    }
    
    private function handlePut(RequestInterface $request): ResponseInterface
    {
        $id = $request->getParam('id');
        $data = json_decode($request->getBody(), true);
        $user = $this->userService->update($id, $data);
        return $this->response($user);
    }
    
    private function handleDelete(RequestInterface $request): ResponseInterface
    {
        $id = $request->getParam('id');
        $this->userService->delete($id);
        return $this->response()->status(204);
    }
}
```

### Handler with Validation

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;

class Register extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            return $this->processRegistration($request);
        }
        
        // Show registration form
        return $this->response()
            ->layout('default')
            ->template('register.phtml');
    }
    
    private function processRegistration(RequestInterface $request): ResponseInterface
    {
        $email = $request->getPost('email');
        $password = $request->getPost('password');
        
        // Validation
        $errors = [];
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address';
        }
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        
        if (!empty($errors)) {
            return $this->response([
                'success' => false,
                'errors' => $errors
            ])->status(400);
        }
        
        // Process registration
        // ...
        
        return $this->response([
            'success' => true,
            'message' => 'Registration successful'
        ]);
    }
}
```

### Handler with File Upload

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;

class Upload extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            return $this->handleUpload($request);
        }
        
        return $this->response()
            ->layout('default')
            ->template('upload.phtml');
    }
    
    private function handleUpload(RequestInterface $request): ResponseInterface
    {
        $files = $request->getFiles();
        
        if (empty($files['file'])) {
            return $this->response(['error' => 'No file uploaded'])->status(400);
        }
        
        $file = $files['file'];
        
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            return $this->response(['error' => 'Invalid file type'])->status(400);
        }
        
        // Move file
        $uploadDir = '/path/to/uploads/';
        $filename = uniqid() . '_' . basename($file['name']);
        $destination = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $this->response([
                'success' => true,
                'filename' => $filename,
                'url' => '/uploads/' . $filename
            ]);
        }
        
        return $this->response(['error' => 'Upload failed'])->status(500);
    }
}
```

### Async/Background Processing Handler

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use App\Service\EmailService;
use App\Service\QueueService;

class SendEmail extends Handler
{
    public function __construct(
        private QueueService $queue
    ) {}

    public function handle(RequestInterface $request): ResponseInterface
    {
        $to = $request->getPost('to');
        $subject = $request->getPost('subject');
        $body = $request->getPost('body');
        
        // Queue the email for background processing
        $jobId = $this->queue->push('send-email', [
            'to' => $to,
            'subject' => $subject,
            'body' => $body
        ]);
        
        return $this->response([
            'success' => true,
            'message' => 'Email queued for sending',
            'job_id' => $jobId
        ])->status(202); // 202 Accepted
    }
}
```

## Testing Handlers

### Example Unit Test

```php
<?php
namespace Tests\Handler;

use PHPUnit\Framework\TestCase;
use App\Http\Handler\Welcome;
use Juzdy\Http\Request;

class WelcomeTest extends TestCase
{
    public function testWelcomeResponse()
    {
        $handler = new Welcome();
        $request = new Request();
        
        $response = $handler->handle($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Welcome', $response->getBody());
    }
}
```

## Best Practices

1. **Keep Handlers Thin**: Delegate business logic to services
2. **Use Dependency Injection**: Inject dependencies via constructor
3. **Validate Input**: Always validate user input
4. **Handle Errors**: Use try-catch blocks for exceptions
5. **Return Appropriate Status Codes**: Use correct HTTP status codes
6. **Use Type Hints**: Leverage PHP's type system
7. **Follow RESTful Conventions**: For APIs, use proper HTTP methods
8. **Separate Concerns**: Keep handlers focused on HTTP request/response

## Common Patterns

### Handler Factory Pattern

For complex handlers, use a factory:

```php
class ComplexHandler extends Handler
{
    public static function create(Container $container): self
    {
        return new self(
            $container->get(ServiceA::class),
            $container->get(ServiceB::class),
            $container->get(ServiceC::class)
        );
    }
}
```

### Handler with Multiple Actions

```php
class User extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        $action = $request->getParam('action', 'view');
        
        return match($action) {
            'view' => $this->viewUser($request),
            'edit' => $this->editUser($request),
            'delete' => $this->deleteUser($request),
            default => $this->response(['error' => 'Invalid action'])->status(400)
        };
    }
}
```

## Next Steps

- Learn about [Middleware](middleware.md)
- Work with [Database](database.md)
- Explore [Examples](examples.md)
- Understand [Layouts](layouts.md)
