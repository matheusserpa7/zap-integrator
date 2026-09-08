#!/usr/bin/env bash
set -euo pipefail

wait_for_postgres() {
    echo "Waiting for PostgreSQL..."
    until php -r "
        try {
            new PDO(
                sprintf('pgsql:host=%s;port=%s;dbname=%s', getenv('DB_HOST'), getenv('DB_PORT'), getenv('DB_DATABASE')),
                getenv('DB_USERNAME'),
                getenv('DB_PASSWORD')
            );
        } catch (Throwable \$e) {
            exit(1);
        }
    "; do
        sleep 1
    done
}

wait_for_redis() {
    echo "Waiting for Redis..."
    until php -r "
        \$host = getenv('REDIS_HOST') ?: '127.0.0.1';
        \$port = (int) (getenv('REDIS_PORT') ?: 6379);
        \$errno = 0;
        \$errstr = '';
        \$fp = @fsockopen(\$host, \$port, \$errno, \$errstr, 1.5);
        if (\$fp === false) {
            exit(1);
        }
        fclose(\$fp);
    "; do
        sleep 1
    done
}

if [[ "${SKIP_BOOTSTRAP:-false}" != "true" ]]; then
    wait_for_postgres
    wait_for_redis

    if [[ ! -f vendor/autoload.php ]]; then
        composer install --no-interaction --prefer-dist --no-progress
    fi

    if [[ -z "${APP_KEY:-}" || "${APP_KEY}" == "base64:" ]]; then
        php artisan key:generate --force --ansi
    fi

    php artisan migrate --force --ansi
    php artisan db:seed --force --ansi
fi

exec "$@"
