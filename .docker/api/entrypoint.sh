#!/bin/sh
set -e

git config --global --add safe.directory /var/www

cd /var/www/api
composer install --no-interaction --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction

crontab /etc/cron.d/puzzle-cron
cron

exec docker-php-entrypoint apache2-foreground
