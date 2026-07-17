FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mysqli \
        mbstring \
        zip \
        intl \
        gd \
        xml \
    && a2enmod rewrite headers expires \
    && printf "ServerName localhost\n" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html

COPY . /var/www/html

ARG COMPOSER_INSTALL_FLAGS=--no-dev
RUN composer install ${COMPOSER_INSTALL_FLAGS} --no-interaction --optimize-autoloader \
    && rm -rf /root/.composer/cache

RUN mkdir -p \
        /var/www/html/backups \
        /var/www/html/cortesProgramacion \
        /var/www/html/construccion/programacion_intermedia/cortesRestricciones \
        /var/www/html/construccion/programacion_semanal/compromisosSemana \
        /var/www/html/construccion/controlCambios/ordenes \
        /var/www/html/admin/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R ug+rwX \
        /var/www/html/backups \
        /var/www/html/cortesProgramacion \
        /var/www/html/construccion/programacion_intermedia/cortesRestricciones \
        /var/www/html/construccion/programacion_semanal/compromisosSemana \
        /var/www/html/construccion/controlCambios/ordenes \
        /var/www/html/admin/logs
