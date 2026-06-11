FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_IPRESOLVE=4 \
    COMPOSER_MAX_PARALLEL_HTTP=1 \
    COMPOSER_PROCESS_TIMEOUT=2000

RUN apk add --no-cache \
    bash \
    curl \
    git \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    unzip \
    zip \
    $PHPIZE_DEPS \
  && docker-php-ext-install \
    mbstring \
    pcntl \
    pdo_mysql \
    pdo_pgsql \
    zip \
  && pecl install redis \
  && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

CMD ["php-fpm"]
