#!/usr/bin/env bash
# Docker Stop Script for Juzdy Project
# This script stops and removes the Docker containers

set -e

echo "======================================"
echo "Stopping Juzdy Docker Container"
echo "======================================"
echo ""

# Navigate to project root
cd "$(dirname "$0")/.."

echo "Stopping containers..."
docker compose down

echo ""
echo "======================================"
echo "Containers stopped successfully!"
echo "======================================"
echo ""
echo "To start again, run: ./bin/docker-start.sh"
echo "To remove all data (including database), run: docker compose down -v"
echo ""
