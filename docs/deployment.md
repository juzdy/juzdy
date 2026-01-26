# 🚀 Deployment Guide

This guide covers everything you need to deploy your JUZDY application to production, from server setup to optimization tips.

---

## 📋 Pre-Deployment Checklist

Before deploying, ensure you've completed these tasks:

- [ ] All tests pass
- [ ] Environment configuration is set up
- [ ] Debug mode is disabled
- [ ] Error logging is configured
- [ ] Database credentials are secured
- [ ] Sensitive files are in `.gitignore`
- [ ] Composer autoloader is optimized
- [ ] Security headers are configured
- [ ] HTTPS/SSL is set up

---

## 🐳 Docker Deployment (Recommended)

Docker provides the easiest and most reliable deployment method.

### Production Docker Setup

#### 1. Update `docker-compose.yml` for Production

```yaml
version: '3.8'

services:
  web:
    build:
      context: .
      dockerfile: Dockerfile
      args:
        INSTALL_DEV_DEPS: "false"  # No dev dependencies
    ports:
      - "80:80"
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    env_file:
      - .env.production
    volumes:
      - ./var/logs:/var/www/html/var/logs
    restart: always
    depends_on:
      - db

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_DATABASE: ${MYSQL_DATABASE}
      MYSQL_USER: ${MYSQL_USER}
      MYSQL_PASSWORD: ${MYSQL_PASSWORD}
    volumes:
      - db-data:/var/lib/mysql
    restart: always

volumes:
  db-data:
```

#### 2. Create `.env.production`

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_NAME="My Production App"

# Database
DB_HOST=db
DB_PORT=3306
DB_NAME=juzdy_prod
DB_USER=juzdy_user
DB_PASSWORD=very_secure_password_here

# MySQL
MYSQL_ROOT_PASSWORD=super_secure_root_password
MYSQL_DATABASE=juzdy_prod
MYSQL_USER=juzdy_user
MYSQL_PASSWORD=very_secure_password_here
```

⚠️ **Important:** Never commit `.env.production` to version control!

#### 3. Deploy

```bash
# Build and start containers
docker compose -f docker-compose.yml up -d --build

# View logs
docker compose logs -f

# Check status
docker compose ps
```

---

## 🌐 Traditional Server Deployment

### Apache Setup

#### 1. Install Prerequisites

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install apache2 php8.0 php8.0-cli php8.0-common \
    php8.0-mysql php8.0-zip php8.0-mbstring php8.0-xml \
    php8.0-curl mysql-server composer

# Enable required modules
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### 2. Configure Virtual Host

Create `/etc/apache2/sites-available/juzdy.conf`:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    
    DocumentRoot /var/www/juzdy/pub
    
    <Directory /var/www/juzdy/pub>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Deny access to sensitive directories
    <DirectoryMatch "^/var/www/juzdy/(app|etc|bin|vendor)">
        Require all denied
    </DirectoryMatch>
    
    ErrorLog ${APACHE_LOG_DIR}/juzdy-error.log
    CustomLog ${APACHE_LOG_DIR}/juzdy-access.log combined
</VirtualHost>
```

#### 3. Enable Site

```bash
sudo a2ensite juzdy.conf
sudo systemctl reload apache2
```

---

### Nginx + PHP-FPM Setup

#### 1. Install Prerequisites

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install nginx php8.0-fpm php8.0-cli php8.0-common \
    php8.0-mysql php8.0-zip php8.0-mbstring php8.0-xml \
    php8.0-curl mysql-server composer
```

#### 2. Configure Nginx

Create `/etc/nginx/sites-available/juzdy`:

```nginx
server {
    listen 80;
    listen [::]:80;
    
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/juzdy/pub;
    index index.php;

    # Security: Deny access to sensitive directories
    location ~ ^/(app|etc|bin|vendor) {
        deny all;
        return 404;
    }

    # Main location block
    location / {
        try_files $uri $uri/ /index.php?q=$uri&$args;
    }

    # PHP processing
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to .htaccess files
    location ~ /\.ht {
        deny all;
    }

    # Logging
    access_log /var/log/nginx/juzdy-access.log;
    error_log /var/log/nginx/juzdy-error.log;
}
```

#### 3. Enable Site

```bash
sudo ln -s /etc/nginx/sites-available/juzdy /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 🔒 SSL/HTTPS Setup

### Using Let's Encrypt (Free)

#### 1. Install Certbot

```bash
# Ubuntu/Debian
sudo apt install certbot python3-certbot-apache  # For Apache
# OR
sudo apt install certbot python3-certbot-nginx   # For Nginx
```

#### 2. Obtain Certificate

```bash
# Apache
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

#### 3. Auto-Renewal

```bash
# Test renewal
sudo certbot renew --dry-run

# Certbot automatically sets up a cron job for renewal
```

---

## ⚙️ Application Optimization

### 1. Optimize Composer Autoloader

```bash
composer install --no-dev --optimize-autoloader
```

**What this does:**
- `--no-dev`: Excludes development dependencies
- `--optimize-autoloader`: Creates class map for faster loading

### 2. Enable PHP OpCache

Edit `/etc/php/8.0/fpm/php.ini` or `/etc/php/8.0/apache2/php.ini`:

```ini
[opcache]
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
opcache.enable_cli=0
```

Restart PHP:

```bash
sudo systemctl restart php8.0-fpm  # For Nginx
# OR
sudo systemctl restart apache2      # For Apache
```

### 3. Disable Debug Mode

In `etc/config/config.php`:

```php
return [
    'app' => [
        'debug' => false,  // MUST be false in production
        'environment' => 'production',
    ],
];
```

### 4. Configure Error Logging

In `pub/index.php`:

```php
if ($_ENV['APP_ENV'] === 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../var/logs/php-errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}
```

### 5. Set Proper File Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/juzdy

# Set directory permissions
find /var/www/juzdy -type d -exec chmod 755 {} \;

# Set file permissions
find /var/www/juzdy -type f -exec chmod 644 {} \;

# Make var/ writable
chmod -R 775 /var/www/juzdy/var
```

---

## 🗄️ Database Setup

### MySQL/MariaDB

#### 1. Create Production Database

```bash
mysql -u root -p
```

```sql
CREATE DATABASE juzdy_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'juzdy_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON juzdy_prod.* TO 'juzdy_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### 2. Import Database

```bash
mysql -u juzdy_user -p juzdy_prod < database.sql
```

#### 3. Configure Connection

In `etc/config/db.php`:

```php
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'user' => $_ENV['DB_USER'] ?? 'juzdy_user',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'database' => $_ENV['DB_NAME'] ?? 'juzdy_prod',
        'charset' => 'utf8mb4',
    ],
];
```

---

## 📊 Monitoring & Logging

### Application Logging

Ensure logs directory is writable:

```bash
mkdir -p /var/www/juzdy/var/logs
chmod 775 /var/www/juzdy/var/logs
chown www-data:www-data /var/www/juzdy/var/logs
```

### Log Rotation

Create `/etc/logrotate.d/juzdy`:

```
/var/www/juzdy/var/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

### Server Monitoring

Monitor your application with these tools:

- **Server Status**: `htop`, `netdata`
- **Application Logs**: `tail -f /var/www/juzdy/var/logs/app.log`
- **Web Server Logs**: 
  - Apache: `/var/log/apache2/juzdy-*.log`
  - Nginx: `/var/log/nginx/juzdy-*.log`
- **PHP Errors**: `/var/www/juzdy/var/logs/php-errors.log`

---

## 🔐 Security Hardening

### 1. Secure Configuration Files

```bash
# Restrict access to config files
chmod 640 /var/www/juzdy/etc/config/*.php
chown www-data:www-data /var/www/juzdy/etc/config/*.php
```

### 2. Hide Sensitive Information

In `.htaccess` (Apache) or Nginx config, deny access to:

```apache
# Apache .htaccess
<FilesMatch "(composer\.json|composer\.lock|\.env|\.git)">
    Require all denied
</FilesMatch>
```

```nginx
# Nginx
location ~ /\.(ht|git|env) {
    deny all;
    return 404;
}

location ~ (composer\.json|composer\.lock)$ {
    deny all;
    return 404;
}
```

### 3. Add Security Headers

Create middleware or add to web server config:

```apache
# Apache
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "DENY"
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

```nginx
# Nginx
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "DENY" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

### 4. Disable PHP Functions

In `php.ini`:

```ini
disable_functions = exec,passthru,shell_exec,system,proc_open,popen
```

### 5. Keep Software Updated

```bash
# Regular updates
sudo apt update
sudo apt upgrade

# Update Composer dependencies
composer update --no-dev
```

---

## 🎯 Performance Tips

### 1. Enable HTTP/2

```apache
# Apache (requires mod_http2)
Protocols h2 http/1.1
```

```nginx
# Nginx
listen 443 ssl http2;
listen [::]:443 ssl http2;
```

### 2. Enable Gzip Compression

```apache
# Apache (enable mod_deflate)
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

```nginx
# Nginx
gzip on;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
gzip_comp_level 6;
```

### 3. Browser Caching

```apache
# Apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

```nginx
# Nginx
location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

---

## 🔄 Deployment Workflow

### Automated Deployment Script

Create `deploy.sh`:

```bash
#!/bin/bash
set -e

echo "🚀 Deploying JUZDY Application..."

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Clear caches (if you have any)
# php bin/cli cache:clear

# Set permissions
chmod -R 775 var/
chown -R www-data:www-data var/

# Restart services
sudo systemctl reload php8.0-fpm
sudo systemctl reload nginx

echo "✅ Deployment complete!"
```

Make it executable:

```bash
chmod +x deploy.sh
```

### CI/CD with GitHub Actions

Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - name: Deploy via SSH
      uses: appleboy/ssh-action@master
      with:
        host: ${{ secrets.HOST }}
        username: ${{ secrets.USERNAME }}
        key: ${{ secrets.SSH_KEY }}
        script: |
          cd /var/www/juzdy
          ./deploy.sh
```

---

## 🆘 Troubleshooting

### Application Shows White Page

**Check:**
1. PHP error logs: `tail -f /var/www/juzdy/var/logs/php-errors.log`
2. Web server error logs
3. Enable display_errors temporarily
4. Check file permissions

### 500 Internal Server Error

**Common causes:**
- Syntax error in `.htaccess`
- Missing PHP extensions
- File permission issues
- PHP fatal error (check logs)

### Database Connection Failed

**Check:**
1. Database credentials in config
2. MySQL service: `sudo systemctl status mysql`
3. User permissions: `SHOW GRANTS FOR 'user'@'localhost';`

### Performance Issues

**Solutions:**
1. Enable OpCache
2. Optimize database queries
3. Add caching layer (Redis/Memcached)
4. Use CDN for static assets
5. Monitor with tools like New Relic

---

## 📚 Additional Resources

- [PHP-FPM Optimization](https://www.php.net/manual/en/install.fpm.php)
- [Apache Performance Tuning](https://httpd.apache.org/docs/2.4/misc/perf-tuning.html)
- [Nginx Performance Tuning](https://nginx.org/en/docs/http/ngx_http_core_module.html)
- [MySQL Optimization](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)

---

## 🎓 Next Steps

- 📖 [Configuration Guide](configuration.md)
- 📖 [Security Best Practices](middleware.md#security)
- 📖 [Examples](examples.md)

---

**Need help with deployment?** Check our [Contributing Guide](contributing.md) or open an issue on GitHub.
