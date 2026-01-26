# 🚀 JUZDY - Modern PHP Made Simple

> **Your Express Lane to Building Blazing-Fast PHP Applications**

JUZDY is the ultimate project template for modern PHP developers who want to build powerful web applications without the complexity. Built on top of the robust [juzdy/core](https://github.com/juzdy/core) framework, it delivers a clean, PSR-compliant foundation that gets you from idea to production in minutes, not weeks.

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Core](https://img.shields.io/badge/juzdy%2Fcore-%5E1.0-orange.svg)](https://github.com/juzdy/core)

---

## ✨ Why Choose JUZDY?

### 🎯 **Built for Speed**
Start coding immediately with a pre-configured project structure. No hours of setup, no configuration headaches. Just `composer install` and you're ready to build.

### 🏗️ **Modern Architecture**
- **PSR Standards**: Fully PSR-compliant (PSR-7, PSR-11, PSR-14, PSR-15, PSR-16)
- **Dependency Injection**: Built-in container for clean, testable code
- **Middleware Pipeline**: Flexible request/response handling
- **Clean Routing**: Simple, intuitive routing system

### 🐳 **Docker Ready**
Production-grade Docker setup included. Deploy anywhere with confidence.
- Pre-configured PHP 8.0 + Apache environment
- MySQL 8.0 database ready to go
- One command to start developing

### 🎨 **Developer-Friendly**
- Clear project structure
- Minimal boilerplate
- Extensive documentation
- Easy to extend and customize

---

## 🚀 Quick Start

Get your application running in under 2 minutes:

### Option 1: Traditional Setup

```bash
# Clone the project
git clone https://github.com/juzdy/juzdy.git my-app
cd my-app

# Install dependencies
composer install

# Configure your web server to point to /pub directory
# Visit http://localhost
```

### Option 2: Docker Setup (Recommended)

```bash
# Start everything with one command
./bin/docker-start.sh

# Visit http://localhost:8080
# That's it! 🎉
```

---

## 💡 Create Your First Handler in 30 Seconds

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

Access it at: `http://localhost:8080/welcome`

---

## 📚 Documentation

Ready to dive deeper? Check out our comprehensive guides:

- **[📖 Getting Started Guide](docs/getting-started.md)** - Detailed installation and first steps
- **[🏗️ Architecture Overview](docs/architecture.md)** - Understanding JUZDY's structure
- **[⚙️ Configuration](docs/configuration.md)** - Customize your application
- **[🎯 Request Handlers](docs/handlers.md)** - Building your application logic
- **[🔗 Middleware](docs/middleware.md)** - Request/response processing
- **[🚀 Deployment](docs/deployment.md)** - Going to production
- **[💻 Examples](docs/examples.md)** - Real-world code samples
- **[🤝 Contributing](docs/contributing.md)** - Join the community

### 🐳 Docker Specific
- **[Docker Setup Guide](DOCKER.md)** - Complete Docker documentation

---

## 🎯 What's Inside?

```
juzdy/
├── app/                    # Your application code
│   ├── src/               # PHP source files
│   │   └── Http/
│   │       └── Handler/   # Request handlers
│   └── layout/            # View templates
├── bin/                   # Executable scripts
├── etc/                   # Configuration files
│   └── config/
├── pub/                   # Public web root
│   └── index.php          # Application entry point
├── var/                   # Runtime files (logs, cache)
└── vendor/                # Composer dependencies
```

---

## 🌟 Key Features

### ⚡ **Lightning Fast Setup**
No complicated wizards or generators. Clone, install, code. It's that simple.

### 🔧 **Highly Configurable**
Every aspect of JUZDY can be customized through simple PHP configuration files in `etc/config/`.

### 🛡️ **Production Ready**
- Comprehensive error handling
- PSR-compliant logging
- Security best practices built-in
- Docker deployment templates

### 🧩 **Extensible Core**
Built on [juzdy/core](https://github.com/juzdy/core), you get:
- Robust PSR container implementation
- Event dispatcher system
- HTTP server handler
- Middleware support
- Simple caching

---

## 🔥 Popular Use Cases

✅ **RESTful APIs** - Build modern APIs in minutes  
✅ **Web Applications** - Full-featured web apps with routing and middleware  
✅ **Microservices** - Lightweight and fast microservice architecture  
✅ **Prototypes** - Rapid prototyping without sacrificing quality  
✅ **Enterprise Applications** - Scalable foundation for large projects

---

## 🤝 Community & Support

- **📖 Documentation**: [Full documentation](docs/)
- **🐛 Bug Reports**: [GitHub Issues](https://github.com/juzdy/juzdy/issues)
- **💡 Feature Requests**: [GitHub Issues](https://github.com/juzdy/juzdy/issues)
- **🤝 Contributing**: See [CONTRIBUTING.md](docs/contributing.md)

---

## 📊 System Requirements

- **PHP** >= 8.0
- **Composer** for dependency management
- **Docker** (optional, but recommended)

---

## 📜 License

JUZDY is open-source software licensed under the [MIT license](LICENSE).

---

## 🙏 Acknowledgments

Built with ❤️ using [juzdy/core](https://github.com/juzdy/core) - The modern PHP core that powers JUZDY.

---

<p align="center">
  <strong>Ready to build something amazing?</strong><br>
  <a href="docs/getting-started.md">Get Started Now →</a>
</p>

---

**Made with 💙 by the JUZDY Team**
