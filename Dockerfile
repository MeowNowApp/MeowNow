# Base PHP with Apache image
FROM php:apache

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable mod_rewrite for Apache
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory
WORKDIR /var/www/html

# Copy application files
COPY website/ /var/www/html/

# Copy Composer files
COPY composer.json composer.lock ./

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions for Apache
RUN chown -R www-data:www-data /var/www/html