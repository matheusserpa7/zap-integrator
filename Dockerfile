# syntax=docker/dockerfile:1.7

FROM node:24.11.1-alpine3.21 AS node-builder
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY vite.config.ts tsconfig.json ./
COPY resources/js ./resources/js
RUN npm run build

FROM composer:2.8.12 AS composer-builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs \
    --no-scripts
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

FROM php:8.5.9-fpm-alpine3.24 AS php-runtime
WORKDIR /var/www/html

RUN apk add --no-cache \
        bash \
        curl \
        git \
        icu-dev \
        libpq-dev \
        libzip-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install \
        bcmath \
        intl \
        pcntl \
        pdo_pgsql \
        zip \
    && apk del $PHPIZE_DEPS \
    && rm -rf /tmp/pear

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zap.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

COPY --from=composer:2.8.12 /usr/bin/composer /usr/bin/composer

RUN addgroup -g 1000 zap \
    && adduser -D -G zap -u 1000 zap \
    && mkdir -p storage bootstrap/cache \
    && chown -R zap:zap /var/www/html

COPY --chown=zap:zap --from=composer-builder /app/vendor ./vendor
COPY --chown=zap:zap . .
COPY --chown=zap:zap --from=node-builder /app/public/build ./public/build

COPY docker/scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R zap:zap storage bootstrap/cache

USER zap

EXPOSE 9000

HEALTHCHECK --interval=15s --timeout=5s --start-period=40s --retries=5 \
    CMD php -r "echo 'ok';"

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
