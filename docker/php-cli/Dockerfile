FROM php:8.1-cli-alpine

# Install required librairies
RUN apk update && apk add autoconf g++ make git unzip \
    libzip-dev icu-dev libxml2-dev libpng-dev libxslt-dev libpq-dev \
    unixodbc-dev gnupg

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql zip intl bcmath gd sockets pcntl xsl
RUN docker-php-ext-enable pcntl

RUN pecl install xdebug-3.1.5 redis
RUN docker-php-ext-enable xdebug redis

RUN rm -rf /tmp/pear

COPY ./conf.d /usr/local/etc/php/conf.d

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/bin --filename=composer --quiet
RUN composer --version

WORKDIR /app

RUN apk add --no-cache shadow \
    && usermod -u 1000 www-data \
    && groupmod -g 1000 www-data
