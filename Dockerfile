
# Stage 1: Build the Laravel application
FROM php:8.1-apache

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y \
        nano \
        git \
        curl \
        gnupg \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        zip \
        unzip


RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

COPY . .

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set up Apache configuration
RUN a2enmod rewrite

EXPOSE 8000
# Set up environment variables using Doppler
CMD ["apache2-foreground"]