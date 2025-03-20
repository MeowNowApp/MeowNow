<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use MeowNow\Api\RandomCat;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../../config');
$dotenv->load();

// Initialize the API
$api = new RandomCat();

// Get the requested format
$format = $_GET['format'] ?? 'redirect';

// Handle the request
try {
    $result = $api->getRandomCat($format);
    
    if ($format === 'json') {
        header('Content-Type: application/json');
        echo json_encode($result);
    } elseif ($format === 'url') {
        header('Content-Type: text/plain');
        echo $result;
    }
} catch (Exception $e) {
    if ($format === 'json') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo 'Error: ' . $e->getMessage();
    }
}
?> 