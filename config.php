<?php
// This file loads environment variables from .env file
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable('/var/www/config');
$dotenv->load();

// Define constants from environment variables
define('AWS_ACCESS_KEY', $_ENV['AWS_ACCESS_KEY'] ?? '');
define('AWS_SECRET_KEY', $_ENV['AWS_SECRET_KEY'] ?? '');
define('AWS_REGION', $_ENV['AWS_REGION'] ?? 'us-east-1');
define('AWS_BUCKET_RAW', $_ENV['AWS_BUCKET_RAW'] ?? '');
define('AWS_BUCKET_COMPRESSED', $_ENV['AWS_BUCKET_COMPRESSED'] ?? '');

// Define the new constants from environment variables
define('S3_RAW_BUCKET', $_ENV['S3_RAW_BUCKET'] ?? $_ENV['AWS_BUCKET_RAW'] ?? '');
define('S3_COMPRESSED_BUCKET', $_ENV['S3_COMPRESSED_BUCKET'] ?? $_ENV['AWS_BUCKET_COMPRESSED'] ?? '');
define('S3_PREFIX', $_ENV['S3_PREFIX'] ?? '');
define('MAX_UPLOAD_SIZE', (int)($_ENV['MAX_UPLOAD_SIZE'] ?? 50000000));
define('MAX_TOTAL_UPLOAD', (int)($_ENV['MAX_TOTAL_UPLOAD'] ?? 250000000));
define('LOG_DIRECTORY', $_ENV['LOG_DIRECTORY'] ?? __DIR__ . '/logs/');
?> 