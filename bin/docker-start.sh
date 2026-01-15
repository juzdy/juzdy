#!/usr/bin/env bash
# Docker Start Script for Juzdy Project
# This script builds and starts the Docker containers

set -e

echo "======================================"
echo "Starting Juzdy Docker Container"
echo "======================================"
echo ""

# Check if docker is installed
if ! command -v docker &> /dev/null; then
    echo "Error: Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if docker compose is available
if ! docker compose version &> /dev/null; then
    echo "Error: docker compose is not available. Please install Docker Compose."
    exit 1
fi

# Navigate to project root
cd "$(dirname "$0")/.."

echo "Building Docker images..."
docker compose build

echo ""
echo "Starting containers..."
docker compose up -d

echo ""
echo "======================================"
echo "Container started successfully!"
echo "======================================"
echo ""
echo "Web application is available at: http://localhost:8080"
echo "MySQL database is available at: localhost:3306"
echo ""
echo "Database credentials:"
echo "  Host: db (from within container) or localhost (from host)"
echo "  Port: 3306"
echo "  Database: juzdy"
echo "  User: juzdy"
echo "  Password: juzdy"
echo "  Root Password: root"
echo ""
echo "Useful commands:"
echo "  - View logs: docker compose logs -f"
echo "  - Stop containers: ./bin/docker-stop.sh"
echo "  - Restart containers: docker compose restart"
echo "  - Enter web container: docker exec -it juzdy-web bash"
echo "  - Enter db container: docker exec -it juzdy-db bash"
echo ""
