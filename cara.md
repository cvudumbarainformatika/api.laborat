docker compose build --no-cache
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app chmod -R 777 storage bootstrap/cache


permissions:
    chmod +x fix-permissions.sh
    ./fix-permissions.sh