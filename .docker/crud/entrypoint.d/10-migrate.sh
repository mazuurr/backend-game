#!/bin/sh
set -e

cd /crud
composer install --no-interaction --optimize-autoloader
bin/cake migrations migrate
