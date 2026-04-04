FROM php:8.3-fpm AS base

ARG UID=1000
ARG GID=1000

ENV HOME=/home/appuser
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y \
    git curl unzip zip pkg-config \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
 && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring bcmath gd pcntl posix

RUN docker-php-ext-configure intl \
    && docker-php-ext-install intl

RUN pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Создаём пользователя с UID/GID как у хоста, чтобы локальный bind mount не ломал права.
RUN groupadd -g ${GID} appuser \
    && useradd -u ${UID} -g ${GID} -m -s /bin/bash appuser

WORKDIR /var/www

FROM base AS production-deps

COPY . /var/www

WORKDIR /var/www/apps/web

RUN mkdir -p \
    /var/www/apps/web/bootstrap/cache \
    /var/www/apps/web/storage/framework/cache \
    /var/www/apps/web/storage/framework/sessions \
    /var/www/apps/web/storage/framework/views \
    /var/www/apps/web/storage/logs

RUN composer install \
    --no-interaction \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-progress

FROM node:20-alpine AS frontend-deps

WORKDIR /var/www/apps/web

COPY apps/web/package*.json ./

RUN npm ci

COPY apps/web/ ./

RUN npm run build

FROM base AS production

COPY . /var/www

COPY --from=production-deps /var/www/apps/web/vendor /var/www/apps/web/vendor
COPY --from=frontend-deps /var/www/apps/web/public/build /var/www/apps/web/public/build

RUN mkdir -p \
    /var/www/apps/web/storage/framework/cache \
    /var/www/apps/web/storage/framework/sessions \
    /var/www/apps/web/storage/framework/views \
    /var/www/apps/web/storage/logs \
    /var/www/apps/web/bootstrap/cache \
 && chown -R appuser:appuser /var/www/apps/web/storage /var/www/apps/web/bootstrap/cache /var/www/apps/web/public/build

USER appuser

FROM base AS dev

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

USER appuser
