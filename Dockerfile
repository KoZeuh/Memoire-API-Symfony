FROM php:8.3-fpm AS build

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libpq-dev \
    && docker-php-ext-install intl pdo pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

COPY . .

RUN mkdir -p var/cache var/log \
 && chown -R www-data:www-data var

FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libicu-dev libpq-dev \
    && docker-php-ext-install intl pdo pdo_mysql

WORKDIR /app

COPY --from=build /app /app

RUN chown -R www-data:www-data /app/var

CMD ["php-fpm"]
