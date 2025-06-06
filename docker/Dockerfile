FROM php:8.3-apache

# Build arguments for AWS and application configuration
ARG AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY AWS_REGION AWS_BUCKET_RAW AWS_BUCKET_COMPRESSED S3_RAW_BUCKET S3_COMPRESSED_BUCKET S3_PREFIX MAX_UPLOAD_SIZE MAX_TOTAL_UPLOAD LOG_DIRECTORY

# Set environment variables from build arguments
ENV AWS_ACCESS_KEY_ID=$AWS_ACCESS_KEY_ID \
    AWS_SECRET_ACCESS_KEY=$AWS_SECRET_ACCESS_KEY \
    AWS_REGION=$AWS_REGION \
    AWS_BUCKET_RAW=$AWS_BUCKET_RAW \
    AWS_BUCKET_COMPRESSED=$AWS_BUCKET_COMPRESSED \
    S3_RAW_BUCKET=$S3_RAW_BUCKET \
    S3_COMPRESSED_BUCKET=$S3_COMPRESSED_BUCKET \
    S3_PREFIX=$S3_PREFIX \
    MAX_UPLOAD_SIZE=$MAX_UPLOAD_SIZE \
    MAX_TOTAL_UPLOAD=$MAX_TOTAL_UPLOAD \
    LOG_DIRECTORY=$LOG_DIRECTORY \
    APACHE_DOCUMENT_ROOT=/var/www/html

# Install system dependencies and configure PHP
# - Install required libraries for image processing and compression
# - Configure and install PHP extensions
# - Enable Apache modules for URL rewriting and headers
# - Install Composer for PHP dependency management
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    unzip \
    git \
    libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_sqlite zip exif \
    && a2enmod rewrite headers \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/lib/apt/lists/*

# Set up application directory structure and permissions
# - Create necessary directories for web files, config, logs, source code, and data
# - Set ownership to www-data (Apache user)
# - Set appropriate permissions (755 for public, 750 for private)
WORKDIR /var/www
RUN mkdir -p html config logs src data \
    && chown -R www-data:www-data /var/www \
    && chmod -R 755 html \
    && chmod -R 750 config logs data

# Copy application files into container
# - Public web files go to html directory
# - Configuration files to config directory
# - Source code to src directory
# - Entrypoint script for container initialization
COPY public/ html/
COPY docker/config config/
COPY src/ src/
COPY composer.json ./
COPY docker/scripts/entrypoint.sh /usr/local/bin/

# Configure application and Apache
# - Install PHP dependencies via Composer
# - Make entrypoint script executable
# - Configure Apache virtual host and document root
RUN composer update --no-dev --optimize-autoloader \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && echo '<VirtualHost *:80>\n\
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

# Expose port 80 for web traffic
EXPOSE 80

# Set up container entrypoint and default command
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]