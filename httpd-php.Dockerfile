FROM composer:latest as composer
FROM php:7.4-apache
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libgd-dev \
    libzip-dev \
    zip \
    unzip \
    imagemagick libmagickwand-dev --no-install-recommends \
    && docker-php-ext-configure gd \
    --with-freetype=/usr/include/ \
    --with-jpeg=/usr/include/ \
    --with-webp=/usr/include/ \
    && docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install -j$(nproc) exif gd pdo_mysql zip opcache \
    && pecl install imagick \
    && docker-php-ext-enable imagick opcache \
    && php -m 

RUN apt-get install -y \
    rsync \
    inotify-tools \
    && a2enmod rewrite

ARG CHEVERETO_SOFTWARE=chevereto
ARG CHEVERETO_TAG=free
ARG CHEVERETO_SERVICING=docker

ENV CHEVERETO_SOFTWARE=$CHEVERETO_SOFTWARE \
    CHEVERETO_TAG=$CHEVERETO_TAG \
    CHEVERETO_SERVICING=$CHEVERETO_SERVICING \
    CHEVERETO_DB_HOST=mariadb \
    CHEVERETO_DB_USER=chevereto \
    CHEVERETO_DB_PASS=user_database_password \
    CHEVERETO_DB_NAME=chevereto \
    CHEVERETO_DB_TABLE_PREFIX=chv_ \
    CHEVERETO_DB_PORT=3306 \
    CHEVERETO_DB_DRIVER=mysql \
    CHEVERETO_DB_PDO_ATTRS=[] \
    CHEVERETO_DEBUG_LEVEL=1 \
    CHEVERETO_DISABLE_PHP_PAGES=1 \
    CHEVERETO_DISABLE_UPDATE_HTTP=1 \
    CHEVERETO_DISABLE_UPDATE_CLI=1 \
    CHEVERETO_ERROR_LOG=/dev/stderr \
    CHEVERETO_IMAGE_FORMATS_AVAILABLE=JPG,PNG,BMP,GIF,WEBP \
    CHEVERETO_IMAGE_LIBRARY=gd \
    CHEVERETO_HTTPS=1 \
    CHEVERETO_HOSTNAME=localhost \
    CHEVERETO_HOSTNAME_PATH=/ \
    CHEVERETO_SESSION_SAVE_HANDLER=files \
    CHEVERETO_SESSION_SAVE_PATH=/tmp \
    CHEVERETO_UPLOAD_MAX_FILESIZE=64M \
    CHEVERETO_POST_MAX_SIZE=64M \
    CHEVERETO_MAX_EXECUTION_TIME=30 \
    CHEVERETO_MEMORY_LIMIT=512M

RUN set -eux; \
    { \
    echo "log_errors = On"; \
    echo "error_log = /dev/stderr"; \
    echo "upload_max_filesize = \${CHEVERETO_UPLOAD_MAX_FILESIZE}"; \
    echo "post_max_size = \${CHEVERETO_POST_MAX_SIZE}"; \
    echo "max_execution_time = \${CHEVERETO_MAX_EXECUTION_TIME}"; \
    echo "memory_limit = \${CHEVERETO_MEMORY_LIMIT}"; \
    } > $PHP_INI_DIR/conf.d/php.ini

VOLUME /var/www/html/content
VOLUME /var/www/html/images

COPY . /var/www/html
RUN rm /var/www/html/sync.sh 
RUN composer install \
    --working-dir=/var/www/html \
    --prefer-dist \
    --no-progress \
    --classmap-authoritative \
    --ignore-platform-reqs
RUN chown www-data: /var/www/html -R
COPY sync.sh /var/www/sync.sh
RUN chmod +x /var/www/sync.sh
CMD ["/bin/bash", "apache2-foreground"]