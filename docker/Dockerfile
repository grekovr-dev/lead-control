FROM php:8.3-fpm

ARG UID=1000
ARG GID=1000

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

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создаём пользователя с UID/GID как у хоста, чтобы файлы не были root:root
RUN groupadd -g ${GID} appuser \
    && useradd -u ${UID} -g ${GID} -m -s /bin/bash appuser

WORKDIR /var/www

# PsySH (tinker) будет писать в HOME без проблем
ENV HOME=/home/appuser

USER appuser
