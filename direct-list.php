<?php
// Simple script to list cat images directly

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors to users

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

// Randomize the list
shuffle($files);

// Set content type to JSON
header('Content-Type: application/json');

// Return the list of cat images
echo json_encode($files);
?> 