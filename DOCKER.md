# Docker Setup for Juzdy

This document describes how to run the Juzdy project using Docker.

## Prerequisites

- Docker (version 20.10 or higher)
- Docker Compose (version 1.29 or higher)

## Quick Start

1. **Start the application:**
   ```bash
   ./bin/docker-start.sh
   ```

2. **Access the application:**
   - Web application: http://localhost:8080
   - MySQL database: localhost:3306

3. **Stop the application:**
   ```bash
   ./bin/docker-stop.sh
   ```

## What's Included

The Docker setup includes:

- **Web Server**: PHP 8.0 with Apache
  - Exposed on port 8080
  - Includes all necessary PHP extensions (zip, pdo, pdo_mysql)
  - mod_rewrite enabled for .htaccess support
  - Composer dependencies installed automatically

- **Database Server**: MySQL 8.0
  - Exposed on port 3306
  - Default credentials:
    - Database: `juzdy`
    - User: `juzdy`
    - Password: `juzdy`
    - Root password: `root`

## Configuration

### Database Connection

When running in Docker, update your `etc/config/db.php` to use these settings:

```php
<?php
return [
    'db' => [
        'host' => 'db',  // Use 'db' (service name) instead of 'localhost'
        'port' => 3306,
        'user' => 'juzdy',
        'password' => 'juzdy',
        'database' => 'juzdy'
    ],
];
```

### Custom Ports

To change the default ports, edit `docker-compose.yml` and modify the `ports` section:

```yaml
services:
  web:
    ports:
      - "8080:80"  # Change 8080 to your desired port
```

## Common Commands

### View Logs
```bash
docker-compose logs -f        # Follow all logs
docker-compose logs -f web    # Follow web server logs only
docker-compose logs -f db     # Follow database logs only
```

### Execute Commands in Container
```bash
# Access web container shell
docker exec -it juzdy-web bash

# Run Composer commands
docker exec -it juzdy-web composer install
docker exec -it juzdy-web composer update

# Run CLI commands
docker exec -it juzdy-web php bin/cli list

# Access MySQL
docker exec -it juzdy-db mysql -u juzdy -pjuzdy juzdy
```

### Restart Services
```bash
docker-compose restart        # Restart all services
docker-compose restart web    # Restart web server only
docker-compose restart db     # Restart database only
```

### Rebuild Containers
```bash
docker-compose build          # Rebuild images
docker-compose up -d --build  # Rebuild and restart
```

### Remove Everything (including data)
```bash
docker-compose down -v        # Stop and remove containers, networks, and volumes
```

## Troubleshooting

### Port Already in Use
If port 8080 or 3306 is already in use, you'll see an error. Change the port in `docker-compose.yml`.

### Permission Issues
If you encounter permission issues, try:
```bash
docker-compose exec web chown -R www-data:www-data /var/www/html
```

### Container Won't Start
Check logs for errors:
```bash
docker-compose logs web
docker-compose logs db
```

### Reset Database
To completely reset the database:
```bash
docker-compose down -v
./bin/docker-start.sh
```

## Development Workflow

The project directory is mounted as a volume, so changes to your code are immediately reflected without rebuilding:

1. Edit files on your host machine
2. Refresh browser to see changes
3. No need to restart containers for code changes

However, if you modify `composer.json` or add new dependencies:
```bash
docker-compose exec web composer install
```

## Production Considerations

This Docker setup is designed for development. For production:

1. Remove volume mounts in `docker-compose.yml`
2. Use environment variables for sensitive data
3. Set `composer install --no-dev` in Dockerfile
4. Consider using a reverse proxy (nginx)
5. Implement proper logging and monitoring
6. Use secrets management for credentials
