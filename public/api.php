<?php
// This file should be placed at the root of your web directory
// It will redirect requests to the appropriate API version

// Get the requested path
$requestPath = $_SERVER['REQUEST_URI'] ?? '/';

// Remove query string if present
$path = parse_url($requestPath, PHP_URL_PATH);

// Strip /api prefix if present
$path = preg_replace('/^\/api/', '', $path);

// Route to the appropriate version
if ($path === '' || $path === '/') {
    header('Location: /api/v1/random');
    exit;
}

// If it's not the root path, let the actual endpoint handle it
require_once __DIR__ . rtrim($path, '/') . '/index.php';
?> 