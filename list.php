<?php
header('Content-Type: application/json');

$directory = './cats';

if (!is_dir($directory)) {
    http_response_code(500);
    echo json_encode(['error' => 'Directory not found.']);
    exit;
}

$files = array_values(array_filter(scandir($directory), function ($file) use ($directory) {
    return is_file("$directory/$file") && preg_match('/\.(jpg|png)$/i', $file);
}));

echo json_encode($files);
