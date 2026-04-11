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
    && apt-get install -y libzip-dev unzip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        zip \
        gd \
        opcache \
        bcmath \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY docker/nginx/nginx.conf /etc/nginx/conf.d/default.conf

# 移除默认站点配置避免冲突
RUN rm -f /etc/nginx/sites-enabled/default

# 配置 Redis session handler
RUN echo "session.save_handler = redis" > /usr/local/etc/php/conf.d/docker-session.ini \
    && echo "session.save_path = tcp://redis:6379" >> /usr/local/etc/php/conf.d/docker-session.ini \
    && echo "session.gc_maxlifetime = 86400" >> /usr/local/etc/php/conf.d/docker-session.ini \
    && echo "session.cookie_path = /" >> /usr/local/etc/php/conf.d/docker-session.ini

WORKDIR /var/www/html

COPY app/php /var/www/html

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

CMD nginx & php-fpm && tail -f /dev/null
