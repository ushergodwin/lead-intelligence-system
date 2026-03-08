FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libonig-dev libxml2-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql zip mbstring exif pcntl gd sockets bcmath opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/lead-intelligence-system

# Copy source
COPY . .

# Install PHP dependencies (production, no dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Build front-end assets, stash them for the entrypoint, then drop node_modules
RUN npm install --legacy-peer-deps \
    && npm run build \
    && cp -r public/build /tmp/build-artifacts \
    && rm -rf node_modules

# Permissions
RUN chown -R www-data:www-data /var/www/lead-intelligence-system \
    && chmod -R 775 storage bootstrap/cache

# PHP opcache config for production
COPY .docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Supervisord config
COPY .docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Entrypoint
COPY .docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
