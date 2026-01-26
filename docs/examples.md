# 💻 Code Examples

Real-world examples to help you build with JUZDY. Copy, paste, and customize these patterns for your own application.

---

## 🎯 Basic Examples

### Hello World Handler

The simplest possible handler:

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
        return $this->response()->body('Hello, World!');
    }
}
```

**URL:** `/hello`

---

## 🌐 RESTful API Examples

### Complete CRUD API

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
            'GET' => $this->handleGet($request),
            'POST' => $this->handlePost($request),
            'PUT' => $this->handlePut($request),
            'DELETE' => $this->handleDelete($request),
            default => $this->json(['error' => 'Method not allowed'], 405),
        };
    }

    private function handleGet(RequestInterface $request): ResponseInterface
    {
        $id = $request->query('id');
        
        if ($id) {
            // Get single product
            $product = $this->products->findById($id);
            
            if (!$product) {
                return $this->json(['error' => 'Product not found'], 404);
            }
            
            return $this->json($product);
        }
        
        // List all products with pagination
        $page = (int)$request->query('page', 1);
        $limit = (int)$request->query('limit', 20);
        
        $products = $this->products->paginate($page, $limit);
        
        return $this->json([
            'data' => $products,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    private function handlePost(RequestInterface $request): ResponseInterface
    {
        $data = json_decode($request->body(), true);
        
        // Validate
        if (!$this->validateProduct($data)) {
            return $this->json(['error' => 'Invalid data'], 400);
        }
        
        // Create
        $product = $this->products->create($data);
        
        return $this->json($product, 201);
    }

    private function handlePut(RequestInterface $request): ResponseInterface
    {
        $id = $request->query('id');
        
        if (!$id) {
            return $this->json(['error' => 'ID required'], 400);
        }
        
        $data = json_decode($request->body(), true);
        
        // Update
        $product = $this->products->update($id, $data);
        
        if (!$product) {
            return $this->json(['error' => 'Product not found'], 404);
        }
        
        return $this->json($product);
    }

    private function handleDelete(RequestInterface $request): ResponseInterface
    {
        $id = $request->query('id');
        
        if (!$id) {
            return $this->json(['error' => 'ID required'], 400);
        }
        
        $deleted = $this->products->delete($id);
        
        if (!$deleted) {
            return $this->json(['error' => 'Product not found'], 404);
        }
        
        return $this->response()->status(204);
    }

    private function validateProduct(array $data): bool
    {
        return isset($data['name']) && isset($data['price']);
    }

    private function json($data, int $status = 200): ResponseInterface
    {
        return $this->response()
            ->status($status)
            ->header('Content-Type', 'application/json')
            ->body(json_encode($data));
    }
}
```

**Usage:**

```bash
# List products
curl http://localhost:8080/api/products

# Get single product
curl http://localhost:8080/api/products?id=123

# Create product
curl -X POST http://localhost:8080/api/products \
  -H "Content-Type: application/json" \
  -d '{"name":"Widget","price":29.99}'

# Update product
curl -X PUT http://localhost:8080/api/products?id=123 \
  -H "Content-Type: application/json" \
  -d '{"name":"Updated Widget","price":39.99}'

# Delete product
curl -X DELETE http://localhost:8080/api/products?id=123
```

---

## 📝 Form Handling Examples

### Contact Form with Validation

**Handler:**

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use App\Service\EmailService;

class Contact extends Handler
{
    public function __construct(
        private EmailService $email
    ) {}

    public function handle(RequestInterface $request): ResponseInterface
    {
        if ($request->method() === 'GET') {
            return $this->showForm();
        }
        
        return $this->submitForm($request);
    }

    private function showForm(array $data = [], array $errors = []): ResponseInterface
    {
        return $this->response()->body(
            $this->renderTemplate('contact', [
                'data' => $data,
                'errors' => $errors,
            ])
        );
    }

    private function submitForm(RequestInterface $request): ResponseInterface
    {
        $data = [
            'name' => $request->post('name'),
            'email' => $request->post('email'),
            'subject' => $request->post('subject'),
            'message' => $request->post('message'),
        ];
        
        // Validate
        $errors = $this->validate($data);
        
        if (!empty($errors)) {
            return $this->showForm($data, $errors);
        }
        
        // Send email
        $this->email->send([
            'to' => 'contact@example.com',
            'from' => $data['email'],
            'subject' => $data['subject'],
            'body' => "From: {$data['name']} ({$data['email']})\n\n{$data['message']}",
        ]);
        
        // Redirect to success page
        return $this->response()
            ->status(302)
            ->header('Location', '/contact/success');
    }

    private function validate(array $data): array
    {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }
        
        if (empty($data['subject'])) {
            $errors['subject'] = 'Subject is required';
        }
        
        if (empty($data['message'])) {
            $errors['message'] = 'Message is required';
        } elseif (strlen($data['message']) < 10) {
            $errors['message'] = 'Message must be at least 10 characters';
        }
        
        return $errors;
    }
}
```

**Template** (`app/layout/default/contact.phtml`):

```php
<!DOCTYPE html>
<html>
<head>
    <title>Contact Us</title>
    <style>
        .error { color: red; }
        .form-group { margin-bottom: 1rem; }
    </style>
</head>
<body>
    <h1>Contact Us</h1>
    
    <form method="POST" action="/contact">
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($data['name'] ?? '') ?>">
            <?php if (isset($errors['name'])): ?>
                <div class="error"><?= $errors['name'] ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($data['email'] ?? '') ?>">
            <?php if (isset($errors['email'])): ?>
                <div class="error"><?= $errors['email'] ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="subject">Subject:</label>
            <input type="text" id="subject" name="subject" value="<?= htmlspecialchars($data['subject'] ?? '') ?>">
            <?php if (isset($errors['subject'])): ?>
                <div class="error"><?= $errors['subject'] ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="message">Message:</label>
            <textarea id="message" name="message" rows="5"><?= htmlspecialchars($data['message'] ?? '') ?></textarea>
            <?php if (isset($errors['message'])): ?>
                <div class="error"><?= $errors['message'] ?></div>
            <?php endif; ?>
        </div>
        
        <button type="submit">Send Message</button>
    </form>
</body>
</html>
```

---

## 🔐 Authentication Examples

### Simple Session-Based Auth

**Login Handler:**

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use App\Service\AuthService;

class Login extends Handler
{
    public function __construct(
        private AuthService $auth
    ) {}

    public function handle(RequestInterface $request): ResponseInterface
    {
        if ($request->method() === 'GET') {
            return $this->showLoginForm();
        }
        
        return $this->processLogin($request);
    }

    private function showLoginForm(string $error = ''): ResponseInterface
    {
        return $this->response()->body(
            $this->renderTemplate('login', ['error' => $error])
        );
    }

    private function processLogin(RequestInterface $request): ResponseInterface
    {
        $username = $request->post('username');
        $password = $request->post('password');
        
        if ($this->auth->login($username, $password)) {
            return $this->response()
                ->status(302)
                ->header('Location', '/dashboard');
        }
        
        return $this->showLoginForm('Invalid credentials');
    }
}
```

**Auth Service:**

```php
<?php
namespace App\Service;

use App\Repository\UserRepository;

class AuthService
{
    public function __construct(
        private UserRepository $users
    ) {}

    public function login(string $username, string $password): bool
    {
        $user = $this->users->findByUsername($username);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        return true;
    }

    public function logout(): void
    {
        session_destroy();
    }

    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function getCurrentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
}
```

**Auth Middleware:**

```php
<?php
namespace App\Http\Middleware;

use Juzdy\Http\MiddlewareInterface;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use Juzdy\Http\HandlerInterface;
use Juzdy\Http\Response;
use App\Service\AuthService;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthService $auth
    ) {}

    public function process(
        RequestInterface $request,
        HandlerInterface $handler
    ): ResponseInterface {
        if (!$this->auth->isAuthenticated()) {
            return (new Response())
                ->status(302)
                ->header('Location', '/login');
        }
        
        return $handler->handle($request);
    }
}
```

**Protected Handler:**

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use App\Http\AuthenticatedInterface;

class Dashboard extends Handler implements AuthenticatedInterface
{
    // AuthMiddleware runs automatically!
    
    public function handle(RequestInterface $request): ResponseInterface
    {
        $userId = $_SESSION['user_id'];
        
        return $this->response()->body(
            $this->renderTemplate('dashboard', [
                'userId' => $userId,
                'username' => $_SESSION['username'],
            ])
        );
    }
}
```

---

## 📤 File Upload Example

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;

class Upload extends Handler
{
    private const UPLOAD_DIR = '/var/www/uploads';
    private const MAX_SIZE = 10 * 1024 * 1024; // 10MB
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

    public function handle(RequestInterface $request): ResponseInterface
    {
        if ($request->method() === 'GET') {
            return $this->showUploadForm();
        }
        
        return $this->handleUpload($request);
    }

    private function showUploadForm(string $message = '', bool $success = false): ResponseInterface
    {
        return $this->response()->body(
            $this->renderTemplate('upload', [
                'message' => $message,
                'success' => $success,
            ])
        );
    }

    private function handleUpload(RequestInterface $request): ResponseInterface
    {
        if (!isset($_FILES['file'])) {
            return $this->showUploadForm('No file selected', false);
        }
        
        $file = $_FILES['file'];
        
        // Validate
        $error = $this->validateFile($file);
        if ($error) {
            return $this->showUploadForm($error, false);
        }
        
        // Save file
        $filename = $this->saveFile($file);
        
        return $this->showUploadForm(
            "File uploaded successfully: {$filename}",
            true
        );
    }

    private function validateFile(array $file): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'Upload error occurred';
        }
        
        if ($file['size'] > self::MAX_SIZE) {
            return 'File is too large (max 10MB)';
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, self::ALLOWED_TYPES)) {
            return 'Invalid file type';
        }
        
        return null;
    }

    private function saveFile(array $file): string
    {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('upload_', true) . '.' . $extension;
        $destination = self::UPLOAD_DIR . '/' . $filename;
        
        move_uploaded_file($file['tmp_name'], $destination);
        
        return $filename;
    }
}
```

---

## 🔍 Search with Filters Example

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use App\Repository\ProductRepository;

class Search extends Handler
{
    public function __construct(
        private ProductRepository $products
    ) {}

    public function handle(RequestInterface $request): ResponseInterface
    {
        $query = $request->query('q', '');
        $category = $request->query('category');
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $sortBy = $request->query('sort', 'name');
        $page = (int)$request->query('page', 1);
        
        $filters = array_filter([
            'query' => $query,
            'category' => $category,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
        ]);
        
        $results = $this->products->search($filters, $sortBy, $page);
        
        return $this->response()->body(
            $this->renderTemplate('search-results', [
                'query' => $query,
                'results' => $results,
                'filters' => $filters,
                'page' => $page,
            ])
        );
    }
}
```

---

## 📊 JSON API with Pagination

```php
<?php
namespace App\Http\Handler\Api;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use App\Repository\UserRepository;

class Users extends Handler
{
    public function __construct(
        private UserRepository $users
    ) {}

    public function handle(RequestInterface $request): ResponseInterface
    {
        $page = (int)$request->query('page', 1);
        $perPage = (int)$request->query('per_page', 20);
        $perPage = min($perPage, 100); // Max 100 items per page
        
        $users = $this->users->paginate($page, $perPage);
        $total = $this->users->count();
        
        return $this->json([
            'data' => $users,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
            ],
            'links' => [
                'self' => "/api/users?page={$page}&per_page={$perPage}",
                'first' => "/api/users?page=1&per_page={$perPage}",
                'last' => "/api/users?page=" . ceil($total / $perPage) . "&per_page={$perPage}",
                'prev' => $page > 1 ? "/api/users?page=" . ($page - 1) . "&per_page={$perPage}" : null,
                'next' => $page < ceil($total / $perPage) ? "/api/users?page=" . ($page + 1) . "&per_page={$perPage}" : null,
            ],
        ]);
    }

    private function json($data): ResponseInterface
    {
        return $this->response()
            ->header('Content-Type', 'application/json')
            ->body(json_encode($data, JSON_PRETTY_PRINT));
    }
}
```

---

## 🎯 Next Steps

Now that you've seen these examples:

1. **Copy** the patterns that fit your use case
2. **Customize** them for your application
3. **Test** thoroughly
4. **Deploy** with confidence

---

## 📚 More Resources

- 📖 [Request Handlers Guide](handlers.md)
- 📖 [Middleware Guide](middleware.md)
- 📖 [Configuration Guide](configuration.md)
- 📖 [Deployment Guide](deployment.md)

---

**Need more examples?** Check our [GitHub repository](https://github.com/juzdy/juzdy) or [open an issue](https://github.com/juzdy/juzdy/issues)!
