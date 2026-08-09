#!/bin/sh
set -e

: "${PORT:=10000}"
export PORT

envsubst '${PORT}' < /etc/nginx/sites-available/default.template > /etc/nginx/sites-available/default

php artisan config:clear
php artisan migrate --force

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
