FROM php:8.3-apache

# Build arguments
ARG AWS_ACCESS_KEY_ID
ARG AWS_SECRET_ACCESS_KEY
ARG AWS_REGION
ARG AWS_BUCKET_RAW
ARG AWS_BUCKET_COMPRESSED
ARG S3_RAW_BUCKET
ARG S3_COMPRESSED_BUCKET
ARG S3_PREFIX
ARG MAX_UPLOAD_SIZE
ARG MAX_TOTAL_UPLOAD
ARG LOG_DIRECTORY

# Set environment variables
ENV AWS_ACCESS_KEY_ID=$AWS_ACCESS_KEY_ID
ENV AWS_SECRET_ACCESS_KEY=$AWS_SECRET_ACCESS_KEY
ENV AWS_REGION=$AWS_REGION
ENV AWS_BUCKET_RAW=$AWS_BUCKET_RAW
ENV AWS_BUCKET_COMPRESSED=$AWS_BUCKET_COMPRESSED
ENV S3_RAW_BUCKET=$S3_RAW_BUCKET
ENV S3_COMPRESSED_BUCKET=$S3_COMPRESSED_BUCKET
ENV S3_PREFIX=$S3_PREFIX
ENV MAX_UPLOAD_SIZE=$MAX_UPLOAD_SIZE
ENV MAX_TOTAL_UPLOAD=$MAX_TOTAL_UPLOAD
ENV LOG_DIRECTORY=$LOG_DIRECTORY

# Install dependencies
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip exif

# Enable Apache modules
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Create directory structure
RUN mkdir -p /var/www/html /var/www/config /var/www/logs /var/www/src

# Set permissions for logs directory
RUN chown -R www-data:www-data /var/www/logs \
    && chmod -R 750 /var/www/logs

# Copy application files
COPY public/ /var/www/html/
COPY docker/config /var/www/config/
COPY src/ /var/www/src/
COPY composer.json /var/www/

# Install dependencies and generate autoloader
RUN composer update --no-dev --optimize-autoloader

# Configure Apache document root
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configure Apache virtual host
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot ${APACHE_DOCUMENT_ROOT}\n\
    <Directory ${APACHE_DOCUMENT_ROOT}>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/html \
    && chmod -R 750 /var/www/config \
    && chmod -R 750 /var/www/logs

# Expose port 80
EXPOSE 80

# Copy entrypoint script
COPY docker/scripts/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Use entrypoint script to set up environment
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]