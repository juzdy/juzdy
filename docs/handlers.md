# 🎯 Request Handlers Guide

Request handlers are the heart of your JUZDY application. They process HTTP requests and generate responses. This guide will teach you everything you need to know.

---

## 🚀 Quick Start

### Your First Handler

Create `app/src/Http/Handler/Welcome.php`:

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
            ->header('Content-Type', 'text/html')
            ->body('<h1>Welcome to JUZDY!</h1>');
    }
}
```

Access it: `http://localhost:8080/welcome`

---

## 📖 Handler Basics

### Handler Structure

Every handler must:
1. Extend `Juzdy\Http\Handler`
2. Implement the `handle()` method
3. Return a `ResponseInterface`

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;

class MyHandler extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        // Your code here
        return $this->response()->body('Hello!');
    }
}
```

### URL to Handler Mapping

JUZDY automatically maps URLs to handlers:

| URL | Handler Class |
|-----|---------------|
| `/` or `/index` | `App\Http\Handler\Index` |
| `/hello` | `App\Http\Handler\Hello` |
| `/user` | `App\Http\Handler\User` |
| `/api/products` | `App\Http\Handler\Api\Products` |
| `/admin/users/list` | `App\Http\Handler\Admin\Users\List` |

**Convention:** 
- URL segments map to namespace segments
- CamelCase in class names
- No manual route definitions needed!

---

## 🔍 Working with Requests

### Getting Query Parameters

```php
class Search extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        // Get single parameter
        $query = $request->query('q');
        
        // Get with default value
        $page = $request->query('page', 1);
        
        // Get all query parameters
        $allParams = $request->query();
        
        return $this->response()->body("Searching for: {$query}");
    }
}
```

**URL:** `/search?q=juzdy&page=2`

### Getting POST Data

```php
class Contact extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        // Get POST parameter
        $name = $request->post('name');
        $email = $request->post('email');
        
        // Get all POST data
        $allData = $request->post();
        
        // Process the form
        $this->sendEmail($name, $email);
        
        return $this->response()->body('Thank you!');
    }
}
```

### Getting Request Headers

```php
class Api extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        // Get specific header
        $authToken = $request->header('Authorization');
        $contentType = $request->header('Content-Type');
        
        // Get all headers
        $allHeaders = $request->headers();
        
        return $this->response()->body('Headers received');
    }
}
```

### Getting Request Method

```php
class User extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        $method = $request->method(); // GET, POST, PUT, DELETE, etc.
        
        match($method) {
            'GET' => $this->getUser($request),
            'POST' => $this->createUser($request),
            'PUT' => $this->updateUser($request),
            'DELETE' => $this->deleteUser($request),
            default => $this->response()->status(405)->body('Method Not Allowed'),
        };
    }
}
```

### Getting Request Body

```php
class ApiWebhook extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        // Get raw body
        $body = $request->body();
        
        // Parse JSON
        $data = json_decode($body, true);
        
        // Process webhook
        $this->processWebhook($data);
        
        return $this->response()->status(200)->body('OK');
    }
}
```

---

## 📤 Creating Responses

### Simple Text Response

```php
return $this->response()->body('Hello, World!');
```

### HTML Response

```php
return $this->response()
    ->header('Content-Type', 'text/html')
    ->body('<h1>Hello, World!</h1>');
```

### JSON Response

```php
$data = [
    'status' => 'success',
    'data' => ['id' => 123, 'name' => 'John'],
];

return $this->response()
    ->header('Content-Type', 'application/json')
    ->body(json_encode($data));
```

### Custom Status Code

```php
// 404 Not Found
return $this->response()
    ->status(404)
    ->body('Page not found');

// 201 Created
return $this->response()
    ->status(201)
    ->header('Content-Type', 'application/json')
    ->body(json_encode(['id' => $newId]));

// 500 Internal Server Error
return $this->response()
    ->status(500)
    ->body('Something went wrong');
```

### Redirects

```php
return $this->response()
    ->status(302)
    ->header('Location', '/success');
```

### Setting Multiple Headers

```php
return $this->response()
    ->header('Content-Type', 'application/json')
    ->header('X-Custom-Header', 'value')
    ->header('Cache-Control', 'no-cache')
    ->body(json_encode($data));
```

---

## 💉 Dependency Injection

Inject dependencies via constructor - they're automatically resolved!

### Injecting Services

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use App\Service\UserService;
use App\Service\LoggerService;

class User extends Handler
{
    public function __construct(
        private UserService $userService,
        private LoggerService $logger
    ) {}

    public function handle(RequestInterface $request): ResponseInterface
    {
        $userId = $request->query('id');
        
        $this->logger->info("Fetching user: {$userId}");
        $user = $this->userService->findById($userId);
        
        return $this->response()
            ->header('Content-Type', 'application/json')
            ->body(json_encode($user));
    }
}
```

### Injecting Interfaces

```php
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class Products extends Handler
{
    public function __construct(
        private LoggerInterface $logger,
        private CacheInterface $cache
    ) {}

    public function handle(RequestInterface $request): ResponseInterface
    {
        // Use PSR interfaces
        $cacheKey = 'products_list';
        
        if ($this->cache->has($cacheKey)) {
            return $this->response()->body($this->cache->get($cacheKey));
        }
        
        $products = $this->fetchProducts();
        $this->cache->set($cacheKey, $products, 3600);
        
        return $this->response()->body($products);
    }
}
```

---

## 🎨 Rendering Templates

### Using renderTemplate()

```php
class Products extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        $products = $this->getProducts();
        
        return $this->response()->body(
            $this->renderTemplate('products', [
                'title' => 'Our Products',
                'products' => $products,
            ])
        );
    }
}
```

### Template File

Create `app/layout/default/products.phtml`:

```php
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?></title>
</head>
<body>
    <h1><?= $title ?></h1>
    <ul>
        <?php foreach ($products as $product): ?>
            <li><?= htmlspecialchars($product['name']) ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
```

---

## 🔥 Real-World Examples

### RESTful API Handler

```php
<?php
namespace App\Http\Handler\Api;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use App\Repository\ProductRepository;

class Products extends Handler
{
    public function __construct(
        private ProductRepository $products
    ) {}

    public function handle(RequestInterface $request): ResponseInterface
    {
        return match($request->method()) {
            'GET' => $this->list($request),
            'POST' => $this->create($request),
            'PUT' => $this->update($request),
            'DELETE' => $this->delete($request),
            default => $this->methodNotAllowed(),
        };
    }

    private function list(RequestInterface $request): ResponseInterface
    {
        $page = (int)$request->query('page', 1);
        $limit = (int)$request->query('limit', 10);
        
        $products = $this->products->paginate($page, $limit);
        
        return $this->jsonResponse($products);
    }

    private function create(RequestInterface $request): ResponseInterface
    {
        $data = json_decode($request->body(), true);
        $product = $this->products->create($data);
        
        return $this->jsonResponse($product, 201);
    }

    private function update(RequestInterface $request): ResponseInterface
    {
        $id = $request->query('id');
        $data = json_decode($request->body(), true);
        
        $product = $this->products->update($id, $data);
        
        return $this->jsonResponse($product);
    }

    private function delete(RequestInterface $request): ResponseInterface
    {
        $id = $request->query('id');
        $this->products->delete($id);
        
        return $this->response()->status(204);
    }

    private function jsonResponse($data, $status = 200): ResponseInterface
    {
        return $this->response()
            ->status($status)
            ->header('Content-Type', 'application/json')
            ->body(json_encode($data));
    }

    private function methodNotAllowed(): ResponseInterface
    {
        return $this->response()
            ->status(405)
            ->body('Method Not Allowed');
    }
}
```

### Form Handler with Validation

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;

class ContactForm extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        if ($request->method() === 'GET') {
            return $this->showForm();
        }
        
        return $this->processForm($request);
    }

    private function showForm(): ResponseInterface
    {
        return $this->response()->body(
            $this->renderTemplate('contact-form', [
                'errors' => [],
            ])
        );
    }

    private function processForm(RequestInterface $request): ResponseInterface
    {
        $name = $request->post('name');
        $email = $request->post('email');
        $message = $request->post('message');
        
        // Validate
        $errors = $this->validate($name, $email, $message);
        
        if (!empty($errors)) {
            return $this->response()->body(
                $this->renderTemplate('contact-form', [
                    'errors' => $errors,
                    'name' => $name,
                    'email' => $email,
                    'message' => $message,
                ])
            );
        }
        
        // Process the form
        $this->sendEmail($name, $email, $message);
        
        // Redirect to success page
        return $this->response()
            ->status(302)
            ->header('Location', '/contact/success');
    }

    private function validate($name, $email, $message): array
    {
        $errors = [];
        
        if (empty($name)) {
            $errors['name'] = 'Name is required';
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }
        
        if (empty($message)) {
            $errors['message'] = 'Message is required';
        }
        
        return $errors;
    }

    private function sendEmail($name, $email, $message): void
    {
        // Send email logic here
    }
}
```

### File Upload Handler

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;

class Upload extends Handler
{
    private const UPLOAD_DIR = '/var/www/uploads';
    private const MAX_SIZE = 5 * 1024 * 1024; // 5MB
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif'];

    public function handle(RequestInterface $request): ResponseInterface
    {
        if ($request->method() !== 'POST') {
            return $this->response()
                ->status(405)
                ->body('Method Not Allowed');
        }

        // Note: Always validate $_FILES before processing
        $file = $_FILES['file'] ?? null;
        
        if (!$file) {
            return $this->jsonError('No file uploaded');
        }

        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->jsonError('Upload error');
        }

        if ($file['size'] > self::MAX_SIZE) {
            return $this->jsonError('File too large');
        }

        if (!in_array($file['type'], self::ALLOWED_TYPES)) {
            return $this->jsonError('Invalid file type');
        }

        // Save file
        $filename = $this->generateFilename($file['name']);
        $destination = self::UPLOAD_DIR . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return $this->jsonError('Failed to save file');
        }

        return $this->response()
            ->header('Content-Type', 'application/json')
            ->body(json_encode([
                'success' => true,
                'filename' => $filename,
                'url' => '/uploads/' . $filename,
            ]));
    }

    private function generateFilename(string $original): string
    {
        $ext = pathinfo($original, PATHINFO_EXTENSION);
        return uniqid() . '.' . $ext;
    }

    private function jsonError(string $message): ResponseInterface
    {
        return $this->response()
            ->status(400)
            ->header('Content-Type', 'application/json')
            ->body(json_encode(['error' => $message]));
    }
}
```

---

## 🛡️ Security Best Practices

### 1. Validate Input

```php
// ✅ Good
$id = filter_var($request->query('id'), FILTER_VALIDATE_INT);
if ($id === false) {
    return $this->response()->status(400)->body('Invalid ID');
}

// ❌ Bad
$id = $request->query('id');
$user = $this->db->query("SELECT * FROM users WHERE id = $id");
```

### 2. Escape Output

```php
// ✅ Good
return $this->response()->body(
    '<h1>' . htmlspecialchars($userInput) . '</h1>'
);

// ❌ Bad
return $this->response()->body("<h1>$userInput</h1>");
```

### 3. Use CSRF Protection

```php
class ProtectedForm extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        if ($request->method() === 'POST') {
            $token = $request->post('csrf_token');
            if (!$this->validateCsrfToken($token)) {
                return $this->response()->status(403)->body('Invalid token');
            }
        }
        
        // Process form...
    }
}
```

### 4. Sanitize File Uploads

```php
// Validate file type by content, not extension
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    return $this->response()->status(400)->body('Invalid file');
}
```

---

## 🎯 Best Practices

### 1. Keep Handlers Thin

❌ **Don't:**
```php
class User extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        // 200 lines of business logic...
        // Database queries...
        // Email sending...
        // PDF generation...
    }
}
```

✅ **Do:**
```php
class User extends Handler
{
    public function __construct(
        private UserService $userService
    ) {}

    public function handle(RequestInterface $request): ResponseInterface
    {
        $user = $this->userService->getUser($request->query('id'));
        return $this->response()->body($this->renderTemplate('user', compact('user')));
    }
}
```

### 2. Use Type Hints

```php
public function __construct(
    private UserRepository $users,
    private LoggerInterface $logger
) {}
```

### 3. Return Early

```php
public function handle(RequestInterface $request): ResponseInterface
{
    if (!$request->query('id')) {
        return $this->response()->status(400)->body('ID required');
    }
    
    $user = $this->findUser($request->query('id'));
    
    if (!$user) {
        return $this->response()->status(404)->body('User not found');
    }
    
    return $this->response()->body($this->renderTemplate('user', compact('user')));
}
```

### 4. Use Helper Methods

```php
class BaseApiHandler extends Handler
{
    protected function json($data, $status = 200): ResponseInterface
    {
        return $this->response()
            ->status($status)
            ->header('Content-Type', 'application/json')
            ->body(json_encode($data));
    }

    protected function error($message, $status = 400): ResponseInterface
    {
        return $this->json(['error' => $message], $status);
    }
}

class Products extends BaseApiHandler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        return $this->json(['products' => $this->getProducts()]);
    }
}
```

---

## 🎓 Next Steps

- 📖 [Middleware Guide](middleware.md)
- 📖 [Configuration](configuration.md)
- 📖 [Examples](examples.md)

---

**Ready for middleware?** Continue to [Middleware Guide](middleware.md) →
