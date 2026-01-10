Voici la correction de l'exercice sur la mise en place des gestions de fichiers. (Chapitre 3 - Partie 3)


### Dockerfile php
Créer un fichier Dockerfile à la racine du projet
```Dockerfile
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
```

### Fichier de conf Nginx

Créez le fichier : `docker/nginx/default.conf`

```
server {
    listen 80;
    server_name example.com;

    root /app/public;
    index index.php;

    # Empêche l'affichage de la version Nginx
    server_tokens off;

    # Limite la taille des uploads
    client_max_body_size 10M;

    # Sécurité : headers HTTP
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'" always;

    # Accès principal Symfony
    location / {
        try_files $uri /index.php$is_args$args;
    }

    # Bloque l'accès direct à d'autres fichiers PHP
    location ~ \.php$ {
        if ($uri != "/index.php") {
            return 404;
        }

        fastcgi_pass php:9000;
        include fastcgi_params;

        fastcgi_param SCRIPT_FILENAME /app/public$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT /app/public;

        fastcgi_intercept_errors on;
    }

    # Cache des fichiers statiques
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff2?)$ {
        expires 30d;
        access_log off;
        add_header Cache-Control "public, immutable";
    }

    # Protection des fichiers sensibles
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Désactive l'accès aux fichiers de config
    location ~* \.(env|log|yml|yaml|ini)$ {
        deny all;
    }

    # Gzip pour prod
    gzip on;
    gzip_types text/plain text/css application/javascript application/json application/xml text/xml application/xml+rss text/javascript;
}
```

### compose.yaml
```yaml
services:
    php:
        build: .
        container_name: symfony_php
        environment:
            APP_ENV: prod
            APP_SECRET: ${APP_SECRET}
            DATABASE_URL: ${DATABASE_URL}
        volumes:
            - .:/app
        depends_on:
            - db

    nginx:
        image: nginx:alpine
        container_name: symfony_nginx
        ports:
            - "8080:80"
        volumes:
            - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
            - .:/app:ro
        depends_on:
            - php

    db:
        image: mysql:8.0
        container_name: symfony_db
        environment:
            MYSQL_DATABASE: ${DB_NAME}
            MYSQL_USER: ${DB_USER}
            MYSQL_PASSWORD: ${DB_PASSWORD}
            MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
        volumes:
            - db_data:/var/lib/mysql
        restart: always

volumes:
    db_data:
```

### Lancer l'application
```shell
docker compose up -d --build
```
