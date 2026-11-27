#!/usr/bin/env sh
set -e

export PORT=${PORT:-10000}

# Injection du port dynamique Render dans la config Nginx
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

# Exécution des migrations Doctrine (si une base est prête)
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Démarrage de PHP-FPM en arrière-plan et de Nginx au premier plan
php-fpm -D
exec nginx -g 'daemon off;'