<?php
// This file is outside the web root and inaccessible to end users

// Load environment variables from .env file
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// AWS Configuration
define('AWS_ACCESS_KEY', $_ENV['AWS_ACCESS_KEY'] ?? '');
define('AWS_SECRET_KEY', $_ENV['AWS_SECRET_KEY'] ?? '');
define('AWS_REGION', $_ENV['AWS_REGION'] ?? 'us-east-1');

// S3 Configuration
define('S3_RAW_BUCKET', $_ENV['S3_RAW_BUCKET'] ?? $_ENV['AWS_BUCKET_RAW'] ?? '');
define('S3_COMPRESSED_BUCKET', $_ENV['S3_COMPRESSED_BUCKET'] ?? $_ENV['AWS_BUCKET_COMPRESSED'] ?? '');
define('S3_PREFIX', $_ENV['S3_PREFIX'] ?? '');

// Upload Limits
define('MAX_UPLOAD_SIZE', (int)($_ENV['MAX_UPLOAD_SIZE'] ?? 50000000));  // 50MB default
define('MAX_TOTAL_UPLOAD', (int)($_ENV['MAX_TOTAL_UPLOAD'] ?? 250000000));  // 250MB default

// Paths
define('LOG_DIRECTORY', $_ENV['LOG_DIRECTORY'] ?? __DIR__ . '/../logs/');
?> 