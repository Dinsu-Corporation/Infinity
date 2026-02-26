FROM dunglas/frankenphp:latest-php8.3-alpine

RUN install-php-extensions \
    yaml \
    opcache \
    intl \
    zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock* ./

RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev

ENV PHP_SERVER_ROOT=/app/public
ENV FRANKENPHP_CONFIG="worker /app/public/index.php"

EXPOSE 80
EXPOSE 443
EXPOSE 443/udp

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
