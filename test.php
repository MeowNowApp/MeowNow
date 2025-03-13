<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$catsDir = './cats/';
$allowedExtensions = ['jpg', 'jpeg', 'png'];

// Get all image files from the directory
$files = [];
if (is_dir($catsDir)) {
    $dir = new DirectoryIterator($catsDir);
    foreach ($dir as $fileInfo) {
        if ($fileInfo->isFile()) {
            $extension = strtolower(pathinfo($fileInfo->getFilename(), PATHINFO_EXTENSION));
            if (in_array($extension, $allowedExtensions)) {
                $files[] = $fileInfo->getFilename();
            }
        }
    }
}

echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct Image Test</title>
    <style>
        body { font-family: sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #333; }
        .image-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .image-card { border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
        img { max-width: 100%; height: auto; display: block; margin-bottom: 10px; }
        .filename { font-size: 0.8rem; word-break: break-all; }
    </style>
</head>
<body>
    <h1>Direct Image Test</h1>
    <p>This page tests direct access to images in the cats directory.</p>';

if (count($files) > 0) {
    echo '<p>Found ' . count($files) . ' images. Displaying up to 20:</p>';
    echo '<div class="image-grid">';
    
    $count = 0;
    foreach ($files as $file) {
        if ($count++ < 20) {
            $directUrl = $catsDir . $file;
            echo '<div class="image-card">';
            echo '<img src="' . htmlspecialchars($directUrl) . '" alt="' . htmlspecialchars($file) . '">';
            echo '<div class="filename">' . htmlspecialchars($file) . '</div>';
            echo '</div>';
        } else {
            break;
        }
    }
    
    echo '</div>';
} else {
    echo '<p>No image files found in the cats directory.</p>';
}

echo '<p><a href="index.html">Back to main page</a> | <a href="debug.php">Run diagnostics</a></p>';
echo '</body>
</html>';
?> 