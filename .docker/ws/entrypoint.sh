#!/bin/sh
set -e

cd /var/www/api
composer install --no-interaction --optimize-autoloader

exec php bin/console app:ws:serve
