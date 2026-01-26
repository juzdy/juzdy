# Database Integration

Juzdy Core provides a lightweight ORM with an Active Record pattern for database operations. This guide covers database configuration, models, and queries.

## Configuration

### Database Setup

Configure database connection in `etc/config/db.php`:

```php
<?php
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'user' => 'your_username',
        'password' => 'your_password',
        'database' => 'your_database',
        'charset' => 'utf8mb4',
    ],
];
```

### Environment-Based Configuration

```php
<?php
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: 3306,
        'user' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'database' => getenv('DB_NAME') ?: 'juzdy',
        'charset' => 'utf8mb4',
    ],
];
```

### Docker Configuration

When using Docker, the host should be the service name:

```php
<?php
return [
    'db' => [
        'host' => 'db',  // Docker service name
        'port' => 3306,
        'user' => 'juzdy',
        'password' => 'juzdy',
        'database' => 'juzdy',
    ],
];
```

## Creating Models

Models extend `Juzdy\Model\Model` and represent database tables.

### Basic Model

```php
<?php
namespace App\Model;

use Juzdy\Model\Model;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    
    // Optional: Define fillable fields
    protected array $fillable = [
        'name',
        'email',
        'password',
    ];
}
```

### Model with Relationships

```php
<?php
namespace App\Model;

use Juzdy\Model\Model;

class Post extends Model
{
    protected string $table = 'posts';
    
    // One-to-many: A post belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    // Many-to-many: A post has many tags
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tags', 'post_id', 'tag_id');
    }
}
```

## CRUD Operations

### Creating Records

```php
// Method 1: Using create()
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => password_hash('secret', PASSWORD_DEFAULT),
]);

// Method 2: Using new instance and save()
$user = new User();
$user->name = 'Jane Doe';
$user->email = 'jane@example.com';
$user->password = password_hash('secret', PASSWORD_DEFAULT);
$user->save();
```

### Reading Records

```php
// Find by primary key
$user = User::find(1);

// Find or fail (throws exception if not found)
$user = User::findOrFail(1);

// Find by column
$user = User::findBy('email', 'john@example.com');

// Get all records
$users = User::all();

// Get with conditions
$users = User::where('status', 'active')->get();

// Get first record
$user = User::where('email', 'john@example.com')->first();
```

### Updating Records

```php
// Method 1: Find and update
$user = User::find(1);
$user->name = 'Updated Name';
$user->save();

// Method 2: Update without fetching
User::where('id', 1)->update([
    'name' => 'Updated Name'
]);

// Update multiple records
User::where('status', 'pending')->update([
    'status' => 'active'
]);
```

### Deleting Records

```php
// Method 1: Find and delete
$user = User::find(1);
$user->delete();

// Method 2: Delete by ID
User::destroy(1);

// Delete multiple
User::destroy([1, 2, 3]);

// Delete with conditions
User::where('status', 'inactive')->delete();
```

## Querying

### Basic Queries

```php
// Where clauses
$users = User::where('status', 'active')->get();
$users = User::where('age', '>', 18)->get();
$users = User::where('created_at', '>=', '2024-01-01')->get();

// Multiple where clauses
$users = User::where('status', 'active')
    ->where('age', '>', 18)
    ->get();

// Or where
$users = User::where('role', 'admin')
    ->orWhere('role', 'moderator')
    ->get();

// Where in
$users = User::whereIn('id', [1, 2, 3])->get();

// Where null/not null
$users = User::whereNull('deleted_at')->get();
$users = User::whereNotNull('email_verified_at')->get();
```

### Ordering and Limiting

```php
// Order by
$users = User::orderBy('name', 'asc')->get();
$users = User::orderBy('created_at', 'desc')->get();

// Limit
$users = User::limit(10)->get();

// Offset
$users = User::offset(10)->limit(10)->get();

// First/last
$user = User::orderBy('created_at', 'desc')->first();
```

### Aggregates

```php
// Count
$count = User::count();
$count = User::where('status', 'active')->count();

// Max/Min
$maxAge = User::max('age');
$minAge = User::min('age');

// Average
$avgAge = User::avg('age');

// Sum
$totalScore = User::sum('score');
```

### Select Specific Columns

```php
// Select columns
$users = User::select(['id', 'name', 'email'])->get();

// Distinct
$emails = User::distinct()->pluck('email');
```

## Relationships

### One-to-One

```php
class User extends Model
{
    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id');
    }
}

// Usage
$user = User::find(1);
$profile = $user->profile;
```

### One-to-Many

```php
class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id');
    }
}

// Usage
$user = User::find(1);
$posts = $user->posts;
```

### Belongs To (Inverse)

```php
class Post extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

// Usage
$post = Post::find(1);
$user = $post->user;
```

### Many-to-Many

```php
class Post extends Model
{
    public function tags()
    {
        return $this->belongsToMany(
            Tag::class,
            'post_tags',  // Pivot table
            'post_id',    // Foreign key
            'tag_id'      // Related key
        );
    }
}

// Usage
$post = Post::find(1);
$tags = $post->tags;
```

## Model Lifecycle Hooks

Models support lifecycle hooks:

```php
class User extends Model
{
    protected function beforeSave(): void
    {
        // Called before saving (create or update)
        if ($this->isDirty('password')) {
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        }
    }
    
    protected function afterSave(): void
    {
        // Called after saving
        $this->sendWelcomeEmail();
    }
    
    protected function beforeDelete(): void
    {
        // Called before deleting
        $this->posts()->delete();  // Delete related posts
    }
    
    protected function afterDelete(): void
    {
        // Called after deleting
        $this->logDeletion();
    }
}
```

## Using Models in Handlers

### Simple CRUD Handler

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use App\Model\User;

class Users extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        
        return match($method) {
            'GET' => $this->list($request),
            'POST' => $this->create($request),
            'PUT' => $this->update($request),
            'DELETE' => $this->delete($request),
            default => $this->response(['error' => 'Method not allowed'])->status(405)
        };
    }
    
    private function list(RequestInterface $request): ResponseInterface
    {
        $users = User::all();
        return $this->response($users);
    }
    
    private function create(RequestInterface $request): ResponseInterface
    {
        $data = json_decode($request->getBody(), true);
        
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        
        return $this->response($user)->status(201);
    }
    
    private function update(RequestInterface $request): ResponseInterface
    {
        $id = $request->getParam('id');
        $data = json_decode($request->getBody(), true);
        
        $user = User::findOrFail($id);
        $user->name = $data['name'] ?? $user->name;
        $user->email = $data['email'] ?? $user->email;
        $user->save();
        
        return $this->response($user);
    }
    
    private function delete(RequestInterface $request): ResponseInterface
    {
        $id = $request->getParam('id');
        User::destroy($id);
        
        return $this->response()->status(204);
    }
}
```

## Repository Pattern

For better separation of concerns, use the repository pattern:

### Create a Repository

```php
<?php
namespace App\Repository;

use App\Model\User;

class UserRepository
{
    public function findById(int $id): ?User
    {
        return User::find($id);
    }
    
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
    
    public function getActive(): array
    {
        return User::where('status', 'active')->get();
    }
    
    public function create(array $data): User
    {
        return User::create($data);
    }
    
    public function update(int $id, array $data): User
    {
        $user = $this->findById($id);
        if (!$user) {
            throw new \Exception('User not found');
        }
        
        foreach ($data as $key => $value) {
            $user->$key = $value;
        }
        $user->save();
        
        return $user;
    }
    
    public function delete(int $id): void
    {
        User::destroy($id);
    }
}
```

### Use Repository in Handler

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use App\Repository\UserRepository;

class Users extends Handler
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function handle(RequestInterface $request): ResponseInterface
    {
        $users = $this->userRepository->getActive();
        return $this->response($users);
    }
}
```

## Raw SQL Queries

For complex queries, use raw SQL:

```php
use Juzdy\Database\Connection;

// Get database connection
$db = $container->get(Connection::class);

// Select query
$result = $db->query('SELECT * FROM users WHERE status = ?', ['active']);

// Insert
$db->execute('INSERT INTO users (name, email) VALUES (?, ?)', ['John', 'john@example.com']);

// Update
$db->execute('UPDATE users SET status = ? WHERE id = ?', ['active', 1]);

// Delete
$db->execute('DELETE FROM users WHERE id = ?', [1]);
```

## Migrations

While Juzdy doesn't include a built-in migration system, you can create simple migration scripts:

```php
<?php
// migrations/001_create_users_table.php

use Juzdy\Database\Connection;

return function(Connection $db) {
    $db->execute("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            status VARCHAR(50) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
};
```

## Best Practices

1. **Use Models for Business Logic**: Keep database logic in models
2. **Repository Pattern**: Separate data access from business logic
3. **Validate Input**: Always validate user input before saving
4. **Use Transactions**: For complex operations that need atomicity
5. **Index Queries**: Add database indexes for frequently queried columns
6. **Avoid N+1 Queries**: Use eager loading for relationships
7. **Use Prepared Statements**: Always use parameter binding (built-in)
8. **Handle Errors**: Catch and handle database exceptions

## Example: Complete User Service

```php
<?php
namespace App\Service;

use App\Repository\UserRepository;
use App\Model\User;

class UserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}
    
    public function register(array $data): User
    {
        // Validate
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid email');
        }
        
        if (strlen($data['password']) < 8) {
            throw new \Exception('Password must be at least 8 characters');
        }
        
        // Check if email exists
        if ($this->userRepository->findByEmail($data['email'])) {
            throw new \Exception('Email already exists');
        }
        
        // Create user
        return $this->userRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],  // Will be hashed in beforeSave hook
        ]);
    }
    
    public function authenticate(string $email, string $password): ?User
    {
        $user = $this->userRepository->findByEmail($email);
        
        if (!$user || !password_verify($password, $user->password)) {
            return null;
        }
        
        return $user;
    }
    
    public function updateProfile(int $userId, array $data): User
    {
        return $this->userRepository->update($userId, $data);
    }
}
```

## Next Steps

- Learn about [HTTP Handlers](http-handlers.md)
- Understand [Architecture](architecture.md)
- Explore [Examples](examples.md)
- Configure [Middleware](middleware.md)
