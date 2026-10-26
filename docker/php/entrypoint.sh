#!/bin/sh
set -e

umask 0000

mkdir -p /var/www/html/storage/logs \
         /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/bootstrap/cache

chmod -R 0777 /var/www/html/storage /var/www/html/bootstrap/cache || true

exec "$@"
