FROM php:7.4-apache
RUN apt-get update && \
    apt-get install -y \
        zlib1g-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng-dev \
        libzip-dev \
        zip
COPY server.crt /etc/apache2/ssl/server.crt
COPY server.key /etc/apache2/ssl/server.key
COPY dev.conf /etc/apache2/sites-enabled/dev.conf
RUN docker-php-ext-install mysqli pdo pdo_mysql zip  \
    && docker-php-ext-install -j$(nproc) iconv  \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host = host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN a2enmod rewrite
RUN a2enmod ssl
RUN service apache2 restart

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Use the development configuration
#RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"


# For gd see https://stackoverflow.com/questions/36359917/docker-gd-module-for-php-7
#Test: https://localhost/autenticacao/securimage/securimage_test.php?tested=1
