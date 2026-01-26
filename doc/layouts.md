# Layouts and Views

Juzdy provides a flexible layout system for rendering HTML templates with support for layouts, blocks, and asset management.

## Overview

The layout system allows you to:
- Create reusable page layouts
- Use template inheritance with blocks
- Manage CSS and JavaScript assets
- Pass data to templates
- Organize templates by feature

## Configuration

Configure layouts in `etc/config/layout.php`:

```php
<?php
return [
    'layout' => [
        'path' => '@{root}/app/layout',  // Layout directory
        'main' => 'layout.phtml',        // Main layout file
        'default' => 'default',          // Default layout folder
    ],
];
```

## Directory Structure

```
app/layout/
├── default/              # Default layout
│   ├── layout.phtml     # Main layout template
│   ├── header.phtml     # Header partial
│   ├── footer.phtml     # Footer partial
│   └── home.phtml       # Home page template
└── errors/              # Error page layouts
    ├── 404.phtml
    └── 500.phtml
```

## Creating Layouts

### Main Layout Template

**File:** `app/layout/default/layout.phtml`

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->escape($title ?? 'My App') ?></title>
    
    <!-- CSS Assets -->
    <?= $this->renderAssets('css') ?>
    
    <!-- Custom head content -->
    <?= $this->block('head') ?>
</head>
<body>
    <!-- Header -->
    <?= $this->partial('header') ?>
    
    <!-- Main Content -->
    <main>
        <?= $this->block('content') ?>
    </main>
    
    <!-- Footer -->
    <?= $this->partial('footer') ?>
    
    <!-- JavaScript Assets -->
    <?= $this->renderAssets('js') ?>
    
    <!-- Custom scripts -->
    <?= $this->block('scripts') ?>
</body>
</html>
```

### Page Templates

**File:** `app/layout/default/home.phtml`

```php
<?php $this->extend('layout') ?>

<?php $this->block('content') ?>
<div class="container">
    <h1><?= $this->escape($title) ?></h1>
    <p><?= $this->escape($message) ?></p>
    
    <?php if (!empty($users)): ?>
    <ul>
        <?php foreach ($users as $user): ?>
        <li><?= $this->escape($user->name) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
<?php $this->endBlock() ?>

<?php $this->block('scripts') ?>
<script>
    console.log('Home page loaded');
</script>
<?php $this->endBlock() ?>
```

## Using Layouts in Handlers

### Basic Usage

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;

class Home extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        return $this->response()
            ->layout('default')           // Use default layout
            ->template('home.phtml')      // Use home template
            ->assign('title', 'Welcome')  // Pass data
            ->assign('message', 'Hello World!');
    }
}
```

### Passing Multiple Variables

```php
public function handle(RequestInterface $request): ResponseInterface
{
    $data = [
        'title' => 'User List',
        'users' => User::all(),
        'count' => User::count(),
    ];
    
    return $this->response()
        ->layout('default')
        ->template('users/list.phtml')
        ->assign($data);  // Pass array of variables
}
```

### Without Layout (Partial Rendering)

```php
public function handle(RequestInterface $request): ResponseInterface
{
    return $this->response()
        ->template('partials/user-card.phtml')
        ->assign('user', $user);
}
```

## Template Features

### Escaping Output

Always escape user-generated content:

```php
<!-- Safe escaping -->
<?= $this->escape($user->name) ?>
<?= $this->escape($user->bio) ?>

<!-- HTML escaping -->
<?= $this->escapeHtml($content) ?>

<!-- URL escaping -->
<a href="<?= $this->escapeUrl($url) ?>">Link</a>

<!-- JavaScript escaping -->
<script>
var data = <?= $this->escapeJs($data) ?>;
</script>
```

### Blocks

Define and use blocks for template inheritance:

```php
<!-- Define a block -->
<?php $this->block('sidebar') ?>
<div class="sidebar">
    <!-- Sidebar content -->
</div>
<?php $this->endBlock() ?>

<!-- Use default content if block not defined -->
<?= $this->block('sidebar', '<div>Default sidebar</div>') ?>
```

### Partials

Include reusable template parts:

```php
<!-- Include a partial -->
<?= $this->partial('header') ?>
<?= $this->partial('user-card', ['user' => $user]) ?>

<!-- Partial with data -->
<?= $this->partial('components/alert', [
    'type' => 'success',
    'message' => 'Operation completed!'
]) ?>
```

### Conditionals and Loops

```php
<!-- If statement -->
<?php if ($user->isAdmin()): ?>
    <div class="admin-panel">Admin Controls</div>
<?php endif; ?>

<!-- If-else -->
<?php if (!empty($users)): ?>
    <ul>
    <?php foreach ($users as $user): ?>
        <li><?= $this->escape($user->name) ?></li>
    <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>No users found.</p>
<?php endif; ?>

<!-- Switch statement -->
<?php switch ($status): 
    case 'active': ?>
        <span class="badge badge-success">Active</span>
        <?php break; ?>
    <?php case 'inactive': ?>
        <span class="badge badge-danger">Inactive</span>
        <?php break; ?>
<?php endswitch; ?>
```

## Asset Management

### Adding Assets in Templates

```php
<!-- Add CSS -->
<?php $this->addCss('/css/style.css') ?>
<?php $this->addCss('/css/custom.css', ['media' => 'print']) ?>

<!-- Add JavaScript -->
<?php $this->addJs('/js/app.js') ?>
<?php $this->addJs('/js/analytics.js', ['async' => true]) ?>

<!-- Inline styles -->
<?php $this->addInlineCss('body { margin: 0; }') ?>

<!-- Inline scripts -->
<?php $this->addInlineJs('console.log("Hello");') ?>
```

### Rendering Assets in Layout

```php
<!-- In layout.phtml -->
<head>
    <?= $this->renderAssets('css') ?>
</head>
<body>
    <!-- Content -->
    <?= $this->renderAssets('js') ?>
</body>
```

### Asset Helpers

```php
<!-- Generate asset URLs -->
<img src="<?= $this->asset('/images/logo.png') ?>" alt="Logo">

<!-- With version/cache busting -->
<link rel="stylesheet" href="<?= $this->asset('/css/style.css?v=1.0.0') ?>">
```

## Helper Functions

### URL Generation

```php
<!-- Base URL -->
<?= $this->url('/User/Profile') ?>

<!-- URL with parameters -->
<?= $this->url('/Search', ['q' => 'php', 'page' => 2]) ?>
// Output: /Search?q=php&page=2

<!-- Full URL -->
<?= $this->fullUrl('/api/users') ?>
```

### Format Helpers

```php
<!-- Date formatting -->
<?= $this->formatDate($user->created_at, 'Y-m-d H:i:s') ?>

<!-- Number formatting -->
<?= $this->formatNumber($price, 2) ?>

<!-- Currency -->
<?= $this->formatCurrency($amount, 'USD') ?>
```

## Advanced Patterns

### Nested Layouts

Create layout hierarchy:

**File:** `app/layout/admin/layout.phtml`

```php
<?php $this->extend('default/layout') ?>

<?php $this->block('content') ?>
<div class="admin-layout">
    <aside class="sidebar">
        <?= $this->partial('admin/sidebar') ?>
    </aside>
    <main class="admin-content">
        <?= $this->block('admin-content') ?>
    </main>
</div>
<?php $this->endBlock() ?>
```

**File:** `app/layout/admin/dashboard.phtml`

```php
<?php $this->extend('admin/layout') ?>

<?php $this->block('admin-content') ?>
<h1>Admin Dashboard</h1>
<!-- Dashboard content -->
<?php $this->endBlock() ?>
```

### Component System

Create reusable components:

**File:** `app/layout/components/card.phtml`

```php
<div class="card <?= $class ?? '' ?>">
    <?php if (!empty($title)): ?>
    <div class="card-header">
        <h3><?= $this->escape($title) ?></h3>
    </div>
    <?php endif; ?>
    
    <div class="card-body">
        <?= $content ?? '' ?>
    </div>
    
    <?php if (!empty($footer)): ?>
    <div class="card-footer">
        <?= $footer ?>
    </div>
    <?php endif; ?>
</div>
```

**Usage:**

```php
<?= $this->partial('components/card', [
    'title' => 'User Info',
    'content' => $this->partial('user-details', ['user' => $user]),
    'class' => 'shadow-lg'
]) ?>
```

### Form Helpers

Create form helper functions:

**File:** `app/layout/helpers/form.php`

```php
<?php
function formInput($name, $value = '', $attrs = []) {
    $attrStr = '';
    foreach ($attrs as $key => $val) {
        $attrStr .= sprintf(' %s="%s"', $key, htmlspecialchars($val));
    }
    
    return sprintf(
        '<input type="text" name="%s" value="%s"%s>',
        htmlspecialchars($name),
        htmlspecialchars($value),
        $attrStr
    );
}
```

### View Models

For complex views, use view models:

```php
<?php
namespace App\ViewModel;

class UserProfileViewModel
{
    public function __construct(
        private User $user
    ) {}
    
    public function getDisplayName(): string
    {
        return $this->user->name ?: 'Anonymous';
    }
    
    public function getAvatarUrl(): string
    {
        return $this->user->avatar ?: '/images/default-avatar.png';
    }
    
    public function isVerified(): bool
    {
        return !is_null($this->user->email_verified_at);
    }
}
```

**In Handler:**

```php
public function handle(RequestInterface $request): ResponseInterface
{
    $user = User::find($id);
    $viewModel = new UserProfileViewModel($user);
    
    return $this->response()
        ->layout('default')
        ->template('user/profile.phtml')
        ->assign('vm', $viewModel);
}
```

**In Template:**

```php
<h1><?= $this->escape($vm->getDisplayName()) ?></h1>
<img src="<?= $this->escape($vm->getAvatarUrl()) ?>" alt="Avatar">
<?php if ($vm->isVerified()): ?>
    <span class="badge">Verified</span>
<?php endif; ?>
```

## JSON Responses

For API handlers, skip layouts and return JSON:

```php
public function handle(RequestInterface $request): ResponseInterface
{
    $users = User::all();
    
    // Automatic JSON response
    return $this->response($users);
    
    // Or explicit
    return $this->response()
        ->header('Content-Type', 'application/json')
        ->body(json_encode($users));
}
```

## Error Pages

Create custom error pages:

**File:** `app/layout/errors/404.phtml`

```php
<!DOCTYPE html>
<html>
<head>
    <title>Page Not Found</title>
</head>
<body>
    <h1>404 - Page Not Found</h1>
    <p>The page you requested could not be found.</p>
    <a href="/">Go Home</a>
</body>
</html>
```

## Best Practices

1. **Escape All Output**: Use `$this->escape()` for user content
2. **Keep Templates Simple**: Move logic to handlers or view models
3. **Reuse Components**: Create partials for repeated elements
4. **Organize by Feature**: Group related templates together
5. **Use Layouts**: Don't repeat HTML structure
6. **Optimize Assets**: Minimize and combine CSS/JS in production
7. **Cache Rendered Output**: For high-traffic pages
8. **Separate Concerns**: Keep business logic out of templates

## Example: Complete Page

**Handler:**

```php
<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use App\Model\Post;

class Blog extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        $posts = Post::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return $this->response()
            ->layout('default')
            ->template('blog/list.phtml')
            ->assign([
                'title' => 'Blog',
                'posts' => $posts,
                'meta_description' => 'Latest blog posts',
            ]);
    }
}
```

**Template:** `app/layout/default/blog/list.phtml`

```php
<?php $this->extend('layout') ?>

<?php $this->addCss('/css/blog.css') ?>
<?php $this->addJs('/js/blog.js') ?>

<?php $this->block('head') ?>
<meta name="description" content="<?= $this->escape($meta_description) ?>">
<?php $this->endBlock() ?>

<?php $this->block('content') ?>
<div class="container">
    <h1><?= $this->escape($title) ?></h1>
    
    <div class="posts">
        <?php foreach ($posts as $post): ?>
            <?= $this->partial('blog/post-card', ['post' => $post]) ?>
        <?php endforeach; ?>
    </div>
</div>
<?php $this->endBlock() ?>
```

## Next Steps

- Learn about [HTTP Handlers](http-handlers.md)
- Understand [Architecture](architecture.md)
- Work with [Database](database.md)
- Explore [Examples](examples.md)
