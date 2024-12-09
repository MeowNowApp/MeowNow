<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$directory = './cats';
$images = array_values(array_filter(scandir($directory), function($file) {
    return preg_match('/\.(jpg|jpeg|png)$/i', $file);
}));

header('Content-Type: application/json');
echo json_encode($images);
?>