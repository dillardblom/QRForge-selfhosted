#!/bin/sh
set -e

php /var/www/html/scripts/migrate.php

exec docker-php-entrypoint "$@"
