# Deployment Guide

This guide covers deploying your Juzdy application to production environments.

## Pre-Deployment Checklist

Before deploying to production:

- [ ] Set `display_errors` to `0` in production
- [ ] Configure proper error logging
- [ ] Set strong database credentials
- [ ] Enable HTTPS/SSL
- [ ] Configure proper file permissions
- [ ] Optimize Composer autoloader
- [ ] Remove development dependencies
- [ ] Configure environment variables
- [ ] Set up monitoring and logging
- [ ] Create database backups
- [ ] Test all critical functionality

## Production Configuration

### 1. Environment Variables

Create a `.env` file (never commit this to version control):

```bash
APP_ENV=production
APP_DEBUG=false

DB_HOST=your-db-host
DB_PORT=3306
DB_USER=your-db-user
DB_PASSWORD=your-secure-password
DB_NAME=your-database

# Add other secrets
API_KEY=your-api-key
SECRET_KEY=your-secret-key
```

Add `.env` to `.gitignore`:

```
.env
.env.local
.env.*.local
```

### 2. Error Handling

Update `pub/index.php` for production:

```php
<?php
use Juzdy\Bootstrap;

session_start();

// Production error handling
if (getenv('APP_ENV') === 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../var/logs/php-errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

require_once realpath(__DIR__ . '/../vendor/autoload.php');

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Initialize configuration
\Juzdy\Config::init(__DIR__ . '/../etc/config/*.php');

// Initialize error handler
\Juzdy\Error\ErrorHandler::init();

// Bootstrap and run
(new \Juzdy\Container\Container())->get(Bootstrap::class)->boot();
```

### 3. Optimize Composer Autoloader

```bash
composer install --no-dev --optimize-autoloader
```

Or for even better performance:

```bash
composer dump-autoload --optimize --classmap-authoritative
```

### 4. File Permissions

Set proper permissions:

```bash
# Application files (read-only for web server)
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Make var/ writable
chmod -R 775 var/
chown -R www-data:www-data var/

# Make bin/ scripts executable
chmod +x bin/*
```

## Web Server Configuration

### Apache Setup

#### Virtual Host Configuration

```apache
<VirtualHost *:80>
    ServerName example.com
    ServerAlias www.example.com
    
    DocumentRoot /var/www/juzdy/pub
    
    <Directory /var/www/juzdy/pub>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/juzdy-error.log
    CustomLog ${APACHE_LOG_DIR}/juzdy-access.log combined
    
    # Security headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

#### SSL Configuration (HTTPS)

```apache
<VirtualHost *:443>
    ServerName example.com
    ServerAlias www.example.com
    
    DocumentRoot /var/www/juzdy/pub
    
    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem
    SSLCertificateChainFile /path/to/chain.pem
    
    <Directory /var/www/juzdy/pub>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Force HTTPS
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    
    ErrorLog ${APACHE_LOG_DIR}/juzdy-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/juzdy-ssl-access.log combined
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName example.com
    Redirect permanent / https://example.com/
</VirtualHost>
```

#### Required Apache Modules

```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod ssl
sudo systemctl restart apache2
```

### Nginx Setup

#### Server Block Configuration

```nginx
server {
    listen 80;
    server_name example.com www.example.com;
    root /var/www/juzdy/pub;
    index index.php;

    # Logging
    access_log /var/log/nginx/juzdy-access.log;
    error_log /var/log/nginx/juzdy-error.log;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        try_files $uri $uri/ /index.php?q=$uri&$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ /vendor/ {
        deny all;
    }
    
    location ~ /etc/ {
        deny all;
    }
}
```

#### SSL Configuration (HTTPS)

```nginx
server {
    listen 443 ssl http2;
    server_name example.com www.example.com;
    root /var/www/juzdy/pub;
    index index.php;

    # SSL certificates
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # HSTS
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # ... rest of configuration same as HTTP
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name example.com www.example.com;
    return 301 https://$server_name$request_uri;
}
```

## Database Setup

### Create Production Database

```sql
CREATE DATABASE juzdy_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'juzdy_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON juzdy_production.* TO 'juzdy_user'@'localhost';
FLUSH PRIVILEGES;
```

### Run Migrations

If you have migration scripts:

```bash
php bin/migrate.php
```

### Import Data

```bash
mysql -u juzdy_user -p juzdy_production < backup.sql
```

## Docker Deployment

### Production Dockerfile

```dockerfile
FROM php:8.0-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip

# Enable Apache modules
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . .

# Install Composer dependencies
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions
RUN chown -R www-data:www-data var/ \
    && chmod -R 775 var/

# Expose port
EXPOSE 80

CMD ["apache2-foreground"]
```

### Docker Compose for Production

```yaml
version: '3.8'

services:
  web:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "80:80"
    environment:
      - APP_ENV=production
      - DB_HOST=db
      - DB_USER=${DB_USER}
      - DB_PASSWORD=${DB_PASSWORD}
      - DB_NAME=${DB_NAME}
    volumes:
      - ./var/logs:/var/www/html/var/logs
    depends_on:
      - db
    restart: unless-stopped

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_NAME}
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql
    restart: unless-stopped

volumes:
  db_data:
```

## Cloud Deployment

### AWS EC2

1. **Launch EC2 Instance**:
   - Choose Ubuntu Server
   - Select appropriate instance type
   - Configure security group (allow HTTP/HTTPS)

2. **Connect and Setup**:

```bash
ssh -i your-key.pem ubuntu@your-instance-ip

# Update system
sudo apt update && sudo apt upgrade -y

# Install Apache, PHP, MySQL
sudo apt install apache2 php8.0 php8.0-mysql php8.0-zip mysql-server -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Clone your application
cd /var/www/
sudo git clone https://github.com/yourusername/your-app.git
cd your-app

# Install dependencies
composer install --no-dev --optimize-autoloader

# Configure Apache
sudo cp deployment/apache-vhost.conf /etc/apache2/sites-available/your-app.conf
sudo a2ensite your-app.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### DigitalOcean

Similar to AWS EC2, or use their App Platform:

1. Create a new app
2. Connect your GitHub repository
3. Configure build and run commands
4. Add environment variables
5. Deploy

### Heroku

Create `Procfile`:

```
web: vendor/bin/heroku-php-apache2 pub/
```

Deploy:

```bash
heroku login
heroku create your-app-name
git push heroku main
heroku config:set APP_ENV=production
heroku config:set DB_HOST=your-db-host
```

## Monitoring and Logging

### Application Logging

Configure logging in your handlers:

```php
use Psr\Log\LoggerInterface;

class YourHandler extends Handler
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function handle(RequestInterface $request): ResponseInterface
    {
        $this->logger->info('Request received', [
            'method' => $request->getMethod(),
            'path' => $request->getPath(),
        ]);
        
        // ... handle request
    }
}
```

### Error Monitoring

Consider using services like:
- **Sentry**: Error tracking and monitoring
- **Rollbar**: Real-time error alerting
- **New Relic**: Application performance monitoring

Example Sentry integration:

```bash
composer require sentry/sdk
```

```php
\Sentry\init(['dsn' => getenv('SENTRY_DSN')]);
```

## Performance Optimization

### 1. OPcache

Enable PHP OPcache in `php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  # Disable in production
```

### 2. Caching

Implement caching for expensive operations:

```php
use Psr\SimpleCache\CacheInterface;

class YourService
{
    public function __construct(
        private CacheInterface $cache
    ) {}

    public function getExpensiveData(): array
    {
        $key = 'expensive_data';
        
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }
        
        $data = $this->computeExpensiveData();
        $this->cache->set($key, $data, 3600); // Cache for 1 hour
        
        return $data;
    }
}
```

### 3. Database Optimization

- Add indexes to frequently queried columns
- Use connection pooling
- Enable query caching
- Optimize slow queries

### 4. Asset Optimization

```bash
# Minify CSS/JS
npm install -g clean-css-cli uglify-js

cleancss -o pub/css/style.min.css pub/css/style.css
uglifyjs pub/js/app.js -o pub/js/app.min.js
```

## Backup Strategy

### Database Backups

```bash
#!/bin/bash
# backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"
DB_NAME="juzdy_production"

mysqldump -u user -p$DB_PASSWORD $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +7 -delete
```

### Application Backups

```bash
tar -czf app_backup_$(date +%Y%m%d).tar.gz \
    --exclude='var/cache' \
    --exclude='var/logs' \
    --exclude='vendor' \
    /var/www/juzdy/
```

## Continuous Deployment

### GitHub Actions

`.github/workflows/deploy.yml`:

```yaml
name: Deploy

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Deploy to server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/juzdy
            git pull origin main
            composer install --no-dev --optimize-autoloader
            sudo systemctl reload apache2
```

## Security Checklist

- [ ] Use HTTPS everywhere
- [ ] Set security headers
- [ ] Validate all user input
- [ ] Use prepared statements for database queries
- [ ] Keep dependencies updated
- [ ] Implement rate limiting
- [ ] Use strong passwords
- [ ] Enable firewall
- [ ] Regular security audits
- [ ] Backup regularly

## Troubleshooting

### Application Not Loading

1. Check Apache/Nginx error logs
2. Verify file permissions
3. Check PHP error logs
4. Ensure mod_rewrite is enabled

### Database Connection Issues

1. Verify database credentials
2. Check database server is running
3. Ensure database user has proper permissions
4. Test connection manually

### Performance Issues

1. Enable OPcache
2. Check database query performance
3. Implement caching
4. Optimize assets
5. Use CDN for static files

## Next Steps

- Set up monitoring
- Configure backups
- Implement CI/CD
- Optimize performance
- Regular maintenance
