#!/bin/bash

# Quick deployment script for updates
# This script is for updating an already deployed application

set -e

PROJECT_DIR="/var/www/pokemon-api"

echo "Starting application update..."

cd $PROJECT_DIR

# Pull latest changes
git pull origin main

# Rebuild and restart containers
docker-compose -f docker-compose.production.yml down
docker-compose -f docker-compose.production.yml up -d --build

# Run Laravel maintenance commands
docker-compose -f docker-compose.production.yml exec app php artisan migrate --force
docker-compose -f docker-compose.production.yml exec app php artisan config:cache
docker-compose -f docker-compose.production.yml exec app php artisan route:cache
docker-compose -f docker-compose.production.yml exec app php artisan view:cache

echo "Application updated successfully!"
