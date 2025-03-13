FROM php:8.3-apache
# Or use a different PHP version like php:8.1-apache

# Install dependencies for GD and other extensions
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libxpm-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Configure and install GD extension with desired options
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    && docker-php-ext-install -j$(nproc) gd

# Install EXIF extension
RUN docker-php-ext-install exif

# Install other useful extensions
RUN docker-php-ext-install zip mysqli pdo pdo_mysql

# Enable the extensions
RUN docker-php-ext-enable gd exif