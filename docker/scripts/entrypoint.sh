#!/bin/bash
set -e

# Create .env file from environment variables
echo "AWS_ACCESS_KEY=${AWS_ACCESS_KEY}" > /var/www/config/.env
echo "AWS_SECRET_KEY=${AWS_SECRET_KEY}" >> /var/www/config/.env
echo "AWS_REGION=${AWS_REGION}" >> /var/www/config/.env
echo "AWS_BUCKET_RAW=${AWS_BUCKET_RAW}" >> /var/www/config/.env
echo "AWS_BUCKET_COMPRESSED=${AWS_BUCKET_COMPRESSED}" >> /var/www/config/.env
echo "S3_RAW_BUCKET=${S3_RAW_BUCKET:-$AWS_BUCKET_RAW}" >> /var/www/config/.env
echo "S3_COMPRESSED_BUCKET=${S3_COMPRESSED_BUCKET:-$AWS_BUCKET_COMPRESSED}" >> /var/www/config/.env
echo "S3_PREFIX=${S3_PREFIX}" >> /var/www/config/.env
echo "MAX_UPLOAD_SIZE=${MAX_UPLOAD_SIZE:-50000000}" >> /var/www/config/.env
echo "MAX_TOTAL_UPLOAD=${MAX_TOTAL_UPLOAD:-250000000}" >> /var/www/config/.env
echo "LOG_DIRECTORY=${LOG_DIRECTORY:-/var/www/logs/}" >> /var/www/config/.env

# Set proper permissions on .env file
chown www-data:www-data /var/www/config/.env
chmod 600 /var/www/config/.env

# First arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
    set -- apache2-foreground "$@"
fi

exec "$@" 