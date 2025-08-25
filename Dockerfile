FROM bitnami/php-fpm:8.3-debian-12

# Install supervisor
ARG user=laravel
ARG uid=1000
ARG gid=1000

RUN install_packages \
  git curl unzip zip supervisor \
  libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev \
  libxml2-dev libzip-dev pkg-config libbrotli-dev \
  autoconf make gcc g++ build-essential

# Enable needed PHP extensions (gd already precompiled, others can be enabled)
RUN php -m | grep -q redis || \
    (pecl install redis && echo "extension=redis.so" > /opt/bitnami/php/etc/conf.d/redis.ini)

# Copy Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user
RUN groupadd -g $gid $user && \
    useradd -u $uid -g $user -s /bin/bash -m $user

# Set working directory
WORKDIR /var/www

# Create necessary directories
RUN mkdir -p /var/www/storage/logs \
    /var/www/storage/framework/cache \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/bootstrap/cache \
    /etc/supervisor/conf.d

# Copy supervisor configuration
COPY docker-compose/supervisor/websockets.conf /etc/supervisor/conf.d/

# Set permissions
RUN chown -R $user:$user /var/www && \
    chmod -R 775 /var/www/storage && \
    chmod -R 775 /var/www/bootstrap/cache && \
    chown -R $user:$user /etc/supervisor/conf.d

# Copy and set permissions for entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER root

ENTRYPOINT ["docker-entrypoint.sh"]
