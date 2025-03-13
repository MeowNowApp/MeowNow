<?php
header('Content-Type: application/json');

$directory = './cats';

if (!is_dir($directory)) {
    http_response_code(500);
    echo json_encode(['error' => 'Directory not found.']);
    exit;
}

$files = array_values(array_filter(scandir($directory), function ($file) use ($directory) {
    return is_file("$directory/$file") && preg_match('/\.(jpg|png|jpeg)$/i', $file);
}));

// Improved randomization using Fisher-Yates shuffle
for ($i = count($files) - 1; $i > 0; $i--) {
    $j = random_int(0, $i);
    [$files[$i], $files[$j]] = [$files[$j], $files[$i]];
}

echo json_encode($files);