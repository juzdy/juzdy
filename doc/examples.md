# Examples and Code Patterns

This guide provides practical examples and common patterns for building applications with Juzdy.

## Table of Contents

1. [Basic Examples](#basic-examples)
2. [API Development](#api-development)
3. [Authentication](#authentication)
4. [CRUD Operations](#crud-operations)
5. [File Uploads](#file-uploads)
6. [Forms and Validation](#forms-and-validation)
7. [Real-World Patterns](#real-world-patterns)

## Basic Examples

### Hello World Handler

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
        return $this->response()
            ->header('Content-Type', 'text/plain')
            ->body('Hello, World!');
    }
}
```

### JSON API Response

```php
<?php
namespace App\Http\Handler\Api;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;

class Status extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        return $this->response([
            'status' => 'ok',
            'timestamp' => time(),
            'version' => '1.0.0',
        ]);
    }
}
```

### HTML Page with Layout

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;

class About extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        return $this->response()
            ->layout('default')
            ->template('about.phtml')
            ->assign([
                'title' => 'About Us',
                'content' => 'Welcome to our application!',
            ]);
    }
}
```

## API Development

### RESTful API Handler

```php
<?php
namespace App\Http\Handler\Api;

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
        return match($request->getMethod()) {
            'GET' => $this->index($request),
            'POST' => $this->store($request),
            'PUT' => $this->update($request),
            'DELETE' => $this->destroy($request),
            default => $this->methodNotAllowed(),
        };
    }

    private function index(RequestInterface $request): ResponseInterface
    {
        $page = (int) $request->getParam('page', 1);
        $perPage = (int) $request->getParam('per_page', 20);
        
        $products = $this->productService->paginate($page, $perPage);
        
        return $this->response([
            'data' => $products,
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    private function store(RequestInterface $request): ResponseInterface
    {
        $data = json_decode($request->getBody(), true);
        
        try {
            $product = $this->productService->create($data);
            return $this->response($product)->status(201);
        } catch (\Exception $e) {
            return $this->response([
                'error' => $e->getMessage()
            ])->status(400);
        }
    }

    private function update(RequestInterface $request): ResponseInterface
    {
        $id = (int) $request->getParam('id');
        $data = json_decode($request->getBody(), true);
        
        try {
            $product = $this->productService->update($id, $data);
            return $this->response($product);
        } catch (\Exception $e) {
            return $this->response([
                'error' => $e->getMessage()
            ])->status(400);
        }
    }

    private function destroy(RequestInterface $request): ResponseInterface
    {
        $id = (int) $request->getParam('id');
        
        try {
            $this->productService->delete($id);
            return $this->response()->status(204);
        } catch (\Exception $e) {
            return $this->response([
                'error' => $e->getMessage()
            ])->status(400);
        }
    }

    private function methodNotAllowed(): ResponseInterface
    {
        return $this->response([
            'error' => 'Method not allowed'
        ])->status(405);
    }
}
```

### API with Pagination

```php
<?php
namespace App\Service;

use App\Model\Product;

class ProductService
{
    public function paginate(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        
        $products = Product::orderBy('created_at', 'desc')
            ->limit($perPage)
            ->offset($offset)
            ->get();
        
        $total = Product::count();
        
        return [
            'items' => $products,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ];
    }
}
```

## Authentication

### Login Handler

```php
<?php
namespace App\Http\Handler\Auth;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use App\Service\AuthService;

class Login extends Handler
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function handle(RequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            return $this->processLogin($request);
        }
        
        // Show login form
        return $this->response()
            ->layout('default')
            ->template('auth/login.phtml');
    }

    private function processLogin(RequestInterface $request): ResponseInterface
    {
        $email = $request->getPost('email');
        $password = $request->getPost('password');
        
        $user = $this->authService->attempt($email, $password);
        
        if (!$user) {
            return $this->response()
                ->layout('default')
                ->template('auth/login.phtml')
                ->assign([
                    'error' => 'Invalid credentials',
                    'email' => $email,
                ]);
        }
        
        // Store user in session
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        
        return $this->redirect('/Dashboard');
    }
}
```

### Authentication Service

```php
<?php
namespace App\Service;

use App\Model\User;

class AuthService
{
    public function attempt(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();
        
        if (!$user || !password_verify($password, $user->password)) {
            return null;
        }
        
        return $user;
    }
    
    public function check(): bool
    {
        return isset($_SESSION['user_id']);
    }
    
    public function user(): ?User
    {
        if (!$this->check()) {
            return null;
        }
        
        return User::find($_SESSION['user_id']);
    }
    
    public function logout(): void
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_email']);
        session_destroy();
    }
}
```

### Authentication Middleware

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
        if (!$this->authService->check()) {
            // Redirect to login
            return new \Juzdy\Http\Response(
                302,
                ['Location' => '/Auth/Login'],
                ''
            );
        }
        
        // Add user to request
        $user = $this->authService->user();
        $request = $request->withAttribute('user', $user);
        
        return $handler->handle($request);
    }
}
```

### Protected Handler

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use App\Interface\AuthenticatableInterface;

class Dashboard extends Handler implements AuthenticatableInterface
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute('user');
        
        return $this->response()
            ->layout('default')
            ->template('dashboard.phtml')
            ->assign([
                'user' => $user,
                'title' => 'Dashboard',
            ]);
    }
}
```

## CRUD Operations

### Complete CRUD Handler

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use App\Model\Post;

class Posts extends Handler
{
    // List all posts
    public function handle(RequestInterface $request): ResponseInterface
    {
        $action = $request->getParam('action', 'list');
        
        return match($action) {
            'list' => $this->list($request),
            'show' => $this->show($request),
            'create' => $this->create($request),
            'edit' => $this->edit($request),
            'delete' => $this->delete($request),
            default => $this->response(['error' => 'Invalid action'])->status(400),
        };
    }

    private function list(RequestInterface $request): ResponseInterface
    {
        $posts = Post::orderBy('created_at', 'desc')->get();
        
        return $this->response()
            ->layout('default')
            ->template('posts/list.phtml')
            ->assign(['posts' => $posts]);
    }

    private function show(RequestInterface $request): ResponseInterface
    {
        $id = (int) $request->getParam('id');
        $post = Post::findOrFail($id);
        
        return $this->response()
            ->layout('default')
            ->template('posts/show.phtml')
            ->assign(['post' => $post]);
    }

    private function create(RequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            $post = Post::create([
                'title' => $request->getPost('title'),
                'content' => $request->getPost('content'),
                'user_id' => $_SESSION['user_id'],
            ]);
            
            return $this->redirect('/Posts?action=show&id=' . $post->id);
        }
        
        return $this->response()
            ->layout('default')
            ->template('posts/create.phtml');
    }

    private function edit(RequestInterface $request): ResponseInterface
    {
        $id = (int) $request->getParam('id');
        $post = Post::findOrFail($id);
        
        if ($request->getMethod() === 'POST') {
            $post->title = $request->getPost('title');
            $post->content = $request->getPost('content');
            $post->save();
            
            return $this->redirect('/Posts?action=show&id=' . $post->id);
        }
        
        return $this->response()
            ->layout('default')
            ->template('posts/edit.phtml')
            ->assign(['post' => $post]);
    }

    private function delete(RequestInterface $request): ResponseInterface
    {
        $id = (int) $request->getParam('id');
        Post::destroy($id);
        
        return $this->redirect('/Posts');
    }
}
```

## File Uploads

### File Upload Handler

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;

class Upload extends Handler
{
    private string $uploadDir;
    
    public function __construct()
    {
        $this->uploadDir = realpath(__DIR__ . '/../../../var/uploads');
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function handle(RequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            return $this->processUpload($request);
        }
        
        return $this->response()
            ->layout('default')
            ->template('upload.phtml');
    }

    private function processUpload(RequestInterface $request): ResponseInterface
    {
        $files = $request->getFiles();
        
        if (empty($files['file'])) {
            return $this->response([
                'success' => false,
                'error' => 'No file uploaded'
            ])->status(400);
        }
        
        $file = $files['file'];
        
        // Validate file
        $errors = $this->validateFile($file);
        if (!empty($errors)) {
            return $this->response([
                'success' => false,
                'errors' => $errors
            ])->status(400);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('upload_') . '.' . $extension;
        $destination = $this->uploadDir . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return $this->response([
                'success' => false,
                'error' => 'Failed to move uploaded file'
            ])->status(500);
        }
        
        return $this->response([
            'success' => true,
            'filename' => $filename,
            'url' => '/uploads/' . $filename,
            'size' => filesize($destination),
        ]);
    }

    private function validateFile(array $file): array
    {
        $errors = [];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload error: ' . $this->getUploadErrorMessage($file['error']);
        }
        
        // Check file size (10MB max)
        if ($file['size'] > 10 * 1024 * 1024) {
            $errors[] = 'File too large (max 10MB)';
        }
        
        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = 'Invalid file type';
        }
        
        return $errors;
    }

    private function getUploadErrorMessage(int $error): string
    {
        return match($error) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
            default => 'Unknown upload error',
        };
    }
}
```

## Forms and Validation

### Form Handler with Validation

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use App\Service\UserService;
use App\Validator\UserValidator;

class Register extends Handler
{
    public function __construct(
        private UserService $userService,
        private UserValidator $validator
    ) {}

    public function handle(RequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            return $this->processRegistration($request);
        }
        
        return $this->showForm();
    }

    private function showForm(array $errors = [], array $old = []): ResponseInterface
    {
        return $this->response()
            ->layout('default')
            ->template('register.phtml')
            ->assign([
                'errors' => $errors,
                'old' => $old,
            ]);
    }

    private function processRegistration(RequestInterface $request): ResponseInterface
    {
        $data = [
            'name' => $request->getPost('name'),
            'email' => $request->getPost('email'),
            'password' => $request->getPost('password'),
            'password_confirmation' => $request->getPost('password_confirmation'),
        ];
        
        // Validate
        $errors = $this->validator->validate($data);
        
        if (!empty($errors)) {
            return $this->showForm($errors, $data);
        }
        
        // Create user
        try {
            $user = $this->userService->register($data);
            
            // Log in user
            $_SESSION['user_id'] = $user->id;
            
            return $this->redirect('/Dashboard');
        } catch (\Exception $e) {
            return $this->showForm(['general' => $e->getMessage()], $data);
        }
    }
}
```

### Validator

```php
<?php
namespace App\Validator;

class UserValidator
{
    public function validate(array $data): array
    {
        $errors = [];
        
        // Name validation
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        }
        
        // Email validation
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email address';
        }
        
        // Password validation
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }
        
        // Password confirmation
        if ($data['password'] !== $data['password_confirmation']) {
            $errors['password_confirmation'] = 'Passwords do not match';
        }
        
        return $errors;
    }
}
```

## Real-World Patterns

### Service Layer Pattern

```php
<?php
namespace App\Service;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Model\Order;

class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private ProductRepository $productRepository,
        private EmailService $emailService
    ) {}

    public function createOrder(int $userId, array $items): Order
    {
        // Start transaction
        DB::beginTransaction();
        
        try {
            // Calculate total
            $total = 0;
            foreach ($items as $item) {
                $product = $this->productRepository->find($item['product_id']);
                $total += $product->price * $item['quantity'];
            }
            
            // Create order
            $order = $this->orderRepository->create([
                'user_id' => $userId,
                'total' => $total,
                'status' => 'pending',
            ]);
            
            // Add order items
            foreach ($items as $item) {
                $order->addItem($item['product_id'], $item['quantity']);
            }
            
            // Send confirmation email
            $this->emailService->sendOrderConfirmation($order);
            
            DB::commit();
            
            return $order;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
```

### Event System

```php
<?php
namespace App\Event;

class OrderCreatedEvent
{
    public function __construct(
        public readonly Order $order
    ) {}
}

// Listener
namespace App\Listener;

class SendOrderNotification
{
    public function __construct(
        private EmailService $emailService
    ) {}

    public function handle(OrderCreatedEvent $event): void
    {
        $this->emailService->sendOrderNotification($event->order);
    }
}

// Dispatch event
$dispatcher->dispatch(new OrderCreatedEvent($order));
```

## Next Steps

- Review [Getting Started](getting-started.md)
- Understand [Architecture](architecture.md)
- Learn about [HTTP Handlers](http-handlers.md)
- Explore [Middleware](middleware.md)
- Work with [Database](database.md)
