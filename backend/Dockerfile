FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libonig-dev \
    libpq-dev \
    libsqlite3-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install mbstring pdo pdo_pgsql pdo_sqlite zip opcache \
    && rm -rf /var/lib/apt/lists/* \
    && echo "opcache.enable=1" >> /usr/local/etc/php/php.ini \
    && echo "opcache.enable_cli=1" >> /usr/local/etc/php/php.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/php.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/php.ini \
    && echo "opcache.max_accelerated_files=8000" >> /usr/local/etc/php/php.ini \
    && echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/php.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/php.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY backend/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
