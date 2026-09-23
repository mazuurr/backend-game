FROM php:8.3-cli

# System libs needed to build the PHP extensions used by the app/domain.
RUN apt-get update \
    && apt-get install -qq -y --no-install-recommends \
        git \
        libicu-dev \
        libzip-dev \
        libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# pcntl + posix are required by Workerman (multi-process master/worker model).
RUN docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_mysql mysqli intl mbstring zip opcache pcntl posix

RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls \
    && mv composer.phar /usr/local/bin/composer

# Code is bind-mounted from the host; allow git/composer to read the repo metadata.
RUN git config --global --add safe.directory /var/www

WORKDIR /var/www/api

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh


# Long-running WebSocket process (Workerman). Apache is intentionally absent.
ENTRYPOINT ["entrypoint.sh"]
