#!/bin/sh

# Cek apakah direktori storage sudah ada, jika belum buat
# mkdir -p /var/www/storage/app/public
# mkdir -p /var/www/storage/framework/cache/data
# mkdir -p /var/www/storage/framework/sessions
# mkdir -p /var/www/storage/framework/testing
# mkdir -p /var/www/storage/framework/views

echo "[Entrypoint] Setting up Laravel environment..."

# mkdir -p /var/www/storage/logs
mkdir -p /var/www/storage/{app/public,framework/{cache/data,sessions,testing,views},logs}
mkdir -p /var/www/bootstrap/cache
mkdir -p /var/www/bootstrap/cache

# Set permissions pada folder storage dan cache
chmod -R 775 /var/www/storage
chmod -R 775 /var/www/bootstrap/cache

# Buat file log jika belum ada dan set permissions
touch /var/www/storage/logs/{laravel.log,websockets.log,queue.log,swoole.log,supervisord.log}
# touch /var/www/storage/logs/laravel.log
# touch /var/www/storage/logs/websockets.log
# touch /var/www/storage/logs/supervisord.log
# touch /var/www/storage/logs/queue.log
# chmod 664 /var/www/storage/logs/laravel.log
# chmod 664 /var/www/storage/logs/websockets.log
# chmod 664 /var/www/storage/logs/supervisord.log
# chmod 664 /var/www/storage/logs/queue.log
# Permission
chown -R laravel:laravel /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache
chmod 664 /var/www/storage/logs/*.log

# Jalankan Supervisor untuk menanggani queue, websockets, dan swoole
echo "Starting Supervisor..."
if ! [ -x "$(command -v /usr/bin/supervisord)" ]; then
  echo 'Error: supervisord is not installed.' >&2
  exit 1
fi

/usr/bin/supervisord -c /etc/supervisor/supervisord.conf

# Pastikan supervisord berjalan, kemudian jalankan PHP-FPM
echo "Starting PHP-FPM..."
exec php-fpm
