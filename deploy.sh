#!/usr/bin/env bash
set -e

cd /var/www/appRevenda

php artisan down || true

# Dependências PHP
composer install --no-dev --optimize-autoloader

# Build dos assets (Vite)
npm ci          # instala TUDO, incluindo devDependencies (onde está o vite)
npm run build

# Migrações e caches
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan up
