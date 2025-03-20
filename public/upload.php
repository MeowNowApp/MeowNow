<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MeowNow\Utils\Logger;
use MeowNow\Utils\UploadHandler;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->load();

// Initialize logger and upload handler
$logger = new Logger();
$uploadHandler = new UploadHandler($logger);

// Set response headers
header('Content-Type: application/json');

// Check if file was uploaded
if (!isset($_FILES['image'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No file uploaded'
    ]);
    exit;
}

try {
    // Handle the upload
    $result = $uploadHandler->handleUpload($_FILES['image']);
    
    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'url' => $result['url']
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}