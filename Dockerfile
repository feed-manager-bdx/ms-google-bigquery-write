FROM php:8.0.10-apache

ARG DOCKER_GATEWAY_HOST=host.docker.internal

RUN apt-get update          \
    && apt-get install -y   \
        git                 \
        vim                 \
        zlib1g-dev          \
        zip                 \
        unzip               \
        libxml2-dev         \
        libgd-dev           \
        libpng-dev          \
        libfreetype6-dev    \
        libjpeg62-turbo-dev \
        libzip-dev          \
        python3             \
        python3-pip         \
        htop                \
        default-mysql-client \
        sqlite3            \
    && pecl install xdebug                                       \
    && docker-php-ext-install mysqli pdo_mysql iconv simplexml   \
    && docker-php-ext-configure gd                               \
    && docker-php-ext-configure zip                              \
    && docker-php-ext-install gd zip                             \
    && docker-php-ext-enable xdebug                              \
    && apt-get clean all                                         \
    && rm -rvf /var/lib/apt/lists/*                              \
    && a2enmod rewrite headers


RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN cd /usr/local/etc/php/conf.d/ && \
  echo 'memory_limit = 1G' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini  && \
  echo 'max_execution_time = 120' >> /usr/local/etc/php/conf.d/docker-php-maxexectime.ini

RUN echo "xdebug.client_host=${DOCKER_GATEWAY_HOST}" >> /usr/local/etc/php/conf.d/99-xdebug.ini
RUN echo "xdebug.mode=develop,debug" >> /usr/local/etc/php/conf.d/99-xdebug.ini


RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

RUN curl -sS https://getcomposer.org/installer | php -- --filename=composer --install-dir=/bin
RUN docker-php-ext-install sockets

ENV PATH /root/.composer/vendor/bin:$PATH
EXPOSE 9003

WORKDIR /var/www/html
