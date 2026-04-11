FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    nginx \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        zip \
        gd \
        opcache \
        bcmath \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY docker/nginx/nginx.conf /etc/nginx/conf.d/default.conf

RUN echo "daemon off;" >> /etc/nginx/nginx.conf

RUN usermod -u 1000 www-data

WORKDIR /var/www/html

COPY app/php /var/www/html

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

CMD service php8.2-fpm start && nginx
