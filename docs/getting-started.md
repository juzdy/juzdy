# 🚀 Getting Started with JUZDY

Welcome to JUZDY! This guide will walk you through everything you need to know to get your first application up and running.

---

## 📋 Prerequisites

Before you begin, ensure you have the following installed:

- **PHP 8.0 or higher** - [Download PHP](https://php.net/downloads)
- **Composer** - [Install Composer](https://getcomposer.org/download/)
- **Git** - [Install Git](https://git-scm.com/downloads)
- **Docker & Docker Compose** (Optional) - [Install Docker](https://docs.docker.com/get-docker/)

### Verify Your Installation

```bash
php -v        # Should show PHP 8.0 or higher
composer -V   # Should show Composer version
git --version # Should show Git version
```

---

## 🎯 Installation Methods

### Method 1: Using Composer (Recommended for Production)

Create a new JUZDY project using Composer:

```bash
composer create-project juzdy/juzdy my-awesome-app
cd my-awesome-app
```

### Method 2: Clone from GitHub

Clone the repository directly:

```bash
git clone https://github.com/juzdy/juzdy.git my-awesome-app
cd my-awesome-app
composer install
```

### Method 3: Docker Setup (Fastest Way to Start)

Perfect for development environments:

```bash
git clone https://github.com/juzdy/juzdy.git my-awesome-app
cd my-awesome-app
./bin/docker-start.sh
```

🎉 **Done!** Visit [http://localhost:8080](http://localhost:8080)

---

## 🖥️ Traditional Web Server Setup

### Apache Configuration

1. **Point your document root to the `pub` directory:**

```apache
<VirtualHost *:80>
    ServerName myapp.local
    DocumentRoot /path/to/my-awesome-app/pub
    
    <Directory /path/to/my-awesome-app/pub>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

2. **Enable mod_rewrite:**

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name myapp.local;
    root /path/to/my-awesome-app/pub;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?q=$uri&$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### PHP Built-in Server (Development Only)

Quick and dirty development server:

```bash
cd pub
php -S localhost:8080
```

⚠️ **Note:** Only use this for development, never in production!

---

## 🎨 Your First Request Handler

Let's create a simple "Hello World" handler:

### Step 1: Create the Handler

Create a new file: `app/src/Http/Handler/Hello.php`

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
        $name = $request->query('name') ?? 'World';
        
        return $this->response()
            ->header('Content-Type', 'text/html')
            ->body("<h1>Hello, {$name}!</h1>");
    }
}
```

### Step 2: Access Your Handler

Visit: `http://localhost:8080/hello`  
Or with a parameter: `http://localhost:8080/hello?name=JUZDY`

🎉 **That's it!** JUZDY automatically discovers and routes to your handler.

---

## 🔧 Configuration Basics

All configuration files live in `etc/config/`. Let's explore the main one:

### `etc/config/config.php`

```php
<?php
return [
    'root' => realpath(__DIR__ . '/../../'),
    
    'bootstrap' => [
        'discover' => true, // Auto-discover bootstrap classes
    ],
    
    'http' => [
        'request_handlers_namespace' => [
            '{namespace}\Http\Handler',
        ],
    ],
    
    'path' => [
        'pub' => '@{root}/pub',
        'var' => '@{root}/var',
        'logs' => '@{path.var}/logs',    
    ],
];
```

**What does this mean?**

- `root`: Your application's base directory
- `bootstrap.discover`: Automatically loads core components
- `http.request_handlers_namespace`: Where JUZDY looks for your handlers
- `path.*`: Directory shortcuts you can use throughout your app

---

## 📁 Understanding the Project Structure

```
my-awesome-app/
│
├── app/                      # 🎨 Your application code
│   ├── src/                  # PHP source files
│   │   └── Http/
│   │       └── Handler/      # Request handlers go here
│   └── layout/               # View templates
│       ├── default/          # Default layout
│       └── errors/           # Error pages
│
├── bin/                      # 🔧 Executable scripts
│   ├── cli                   # CLI entry point
│   ├── docker-start.sh       # Start Docker
│   └── docker-stop.sh        # Stop Docker
│
├── etc/                      # ⚙️ Configuration
│   └── config/
│       ├── config.php        # Main configuration
│       ├── middleware.php    # Middleware setup
│       ├── layout.php        # Layout configuration
│       └── db.php            # Database config (if needed)
│
├── pub/                      # 🌐 Public web root
│   ├── .htaccess            # Apache rewrite rules
│   └── index.php            # Application entry point
│
├── var/                      # 📝 Runtime files
│   └── logs/                # Application logs
│
└── vendor/                   # 📦 Composer dependencies
```

---

## 🧪 Testing Your Installation

### Test 1: Check the Default Handler

Visit: `http://localhost:8080/` or `http://localhost:8080/index`

You should see: **"Hello from Index Handler!"**

### Test 2: Check Routing

The URL pattern is: `http://localhost:8080/{HandlerName}`

- `/index` → `App\Http\Handler\Index`
- `/hello` → `App\Http\Handler\Hello`
- `/user/profile` → `App\Http\Handler\User\Profile`

---

## 🎯 Next Steps

Now that you have JUZDY running, explore these topics:

1. **[Architecture Overview](architecture.md)** - Understand how JUZDY works
2. **[Request Handlers](handlers.md)** - Build your application logic
3. **[Middleware](middleware.md)** - Process requests and responses
4. **[Configuration](configuration.md)** - Customize your application
5. **[Examples](examples.md)** - See real-world implementations

---

## ❓ Troubleshooting

### "Page Not Found" or 404 Errors

**Problem:** All routes return 404  
**Solution:** Ensure `.htaccess` is enabled (Apache) or URL rewriting is configured (Nginx)

### "Class Not Found" Errors

**Problem:** Handlers can't be found  
**Solution:** Run `composer dump-autoload` to regenerate the autoloader

### Permission Errors

**Problem:** Can't write to `var/logs/`  
**Solution:** Set proper permissions:

```bash
chmod -R 775 var/
chown -R www-data:www-data var/  # Linux/Apache
```

### Docker Port Conflicts

**Problem:** Port 8080 already in use  
**Solution:** Edit `docker-compose.yml` and change the port:

```yaml
ports:
  - "8081:80"  # Changed from 8080 to 8081
```

---

## 💬 Need Help?

- 📖 [Read the full documentation](../README.md)
- 🐛 [Report bugs](https://github.com/juzdy/juzdy/issues)
- 💡 [Request features](https://github.com/juzdy/juzdy/issues)

---

**Ready to build?** Let's move on to [Architecture Overview](architecture.md) →
