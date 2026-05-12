FROM php:8.2-fpm-alpine AS production

RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev \
    icu-dev \
    imagemagick-dev \
    imagemagick \
    fcgi \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pgsql \
    mbstring \
    xml \
    curl \
    zip \
    intl \
    bcmath \
    gd \
    opcache \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY . /var/www

WORKDIR /var/www

RUN composer install --no-dev --no-interaction --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN mkdir -p storage/app/public/fotos \
    storage/app/public/separatas \
    storage/app/public/comprobantes \
    storage/app/public/temp

COPY --chmod=755 entrypoint.sh /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
