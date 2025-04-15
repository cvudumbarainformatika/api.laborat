#!/bin/bash

# Create Laravel storage directory structure
mkdir -p /var/www/storage/app/public
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/testing
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache

# Set permissions
chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache

# Create log file if it doesn't exist
touch /var/www/storage/logs/laravel.log
touch /var/www/storage/logs/websockets.log
chmod 664 /var/www/storage/logs/laravel.log
chmod 664 /var/www/storage/logs/websockets.log

# Start Supervisor
/usr/bin/supervisord -c /etc/supervisor/supervisord.conf

# Start PHP-FPM
php-fpm
