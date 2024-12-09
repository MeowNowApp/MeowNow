<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$directory = './cats';

// Scan the directory for valid image files
$images = array_values(array_filter(scandir($directory), function($file) {
    return preg_match('/\.(jpe?g|png)$/i', $file); // Matches jpg, jpeg, JPG, JPEG, png, PNG
}));

// Set response type to JSON and return the list of images
header('Content-Type: application/json');
echo json_encode($images);
?>