.PHONY: setup build up down logs restart shell permissions

setup:
    @make build
    @make permissions
    @make up
    docker compose exec app composer install
    docker compose exec app php artisan key:generate
    docker compose exec app php artisan config:cache
    docker compose exec app php artisan route:cache
    docker compose exec app php artisan view:cache

permissions:
    chmod +x fix-permissions.sh
    ./fix-permissions.sh

build:
    docker compose build --no-cache

up:
    docker compose up -d

down:
    docker compose down

logs:
    docker compose logs -f

restart:
    @make down
    @make up

shell:
    docker compose exec app bash
