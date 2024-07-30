FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
  git \
  unzip \
  libpq-dev \
  libzip-dev \
  libicu-dev \
  libonig-dev \
  libxml2-dev \
  libpng-dev \
  libjpeg-dev \
  libfreetype6-dev \
  libmcrypt-dev \
  libjpeg62-turbo-dev \
  libpng-dev \
  libwebp-dev \
  libxpm-dev \
  libvpx-dev \
  libgd-dev \
  libcurl4-openssl-dev \
  pkg-config \
  libssl-dev \
  libmagickwand-dev --no-install-recommends

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip intl opcache

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
# COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory contents
COPY . /var/www/html

# Set environment variable to allow Composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install Symfony dependencies
RUN composer install

# Expose port 8000 and start PHP-FPM server
EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]