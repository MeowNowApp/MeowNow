<?php
$directory = './cats';
$images = array_values(array_filter(scandir($directory), function($file) {
    return preg_match('/\.(jpg|jpeg|png)$/i', $file);
}));
header('Content-Type: application/json');
echo json_encode($images);
?>