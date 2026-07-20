#!/bin/sh
set -e

# Si no viene APP_KEY definido (ej: docker compose sin .env), se genera uno
# al vuelo para que el contenedor arranque sin pasos manuales
if [ -z "$APP_KEY" ]; then
    export APP_KEY=$(php artisan key:generate --show)
    echo "APP_KEY generado automaticamente para esta corrida"
fi

# Espera a que la base de datos esté lista, corre las migraciones y arranca php-fpm
tries=0
until php artisan migrate --force --seed --no-interaction; do
    tries=$((tries + 1))

    if [ "$tries" -ge 10 ]; then
        echo "Base de datos no disponible, abortando." >&2
        exit 1
    fi

    echo "Esperando a la base de datos... (intento $tries)"
    sleep 3
done

php artisan optimize --no-interaction

chown -R www-data:www-data storage bootstrap/cache

exec php-fpm
