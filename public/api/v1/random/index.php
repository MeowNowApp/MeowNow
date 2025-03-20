<?php

require_once '/var/www/vendor/autoload.php';

use MeowNow\Api\RandomCat;

// Configure AWS
$awsConfig = [
    'version' => 'latest',
    'region'  => $_ENV['AWS_REGION'] ?? 'us-east-1'
];

// Add credentials
if (!empty($_ENV['AWS_ACCESS_KEY_ID']) && !empty($_ENV['AWS_SECRET_ACCESS_KEY'])) {
    $awsConfig['credentials'] = [
        'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
        'secret' => $_ENV['AWS_SECRET_ACCESS_KEY']
    ];
}

try {
    // Initialize the RandomCat API with AWS configuration
    $api = new RandomCat($awsConfig);
    
    // Get the requested format from query parameters
    $format = $_GET['format'] ?? 'image';
    
    // Handle the request based on format
    switch ($format) {
        case 'json':
            header('Content-Type: application/json');
            echo json_encode($api->getRandomCat('json'));
            break;
            
        case 'url':
            header('Content-Type: text/plain');
            echo $api->getRandomCat('url');
            break;
            
        case 'image':
        default:
            // Redirect to the image URL
            $imageData = $api->getRandomCat('url');
            header('Location: ' . $imageData);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'config' => array_merge($awsConfig, ['credentials' => '***']),
        'debug' => [
            'env_vars' => [
                'AWS_REGION' => $_ENV['AWS_REGION'] ?? 'not set',
                'AWS_ACCESS_KEY_ID' => !empty($_ENV['AWS_ACCESS_KEY_ID']) ? 'set' : 'not set',
                'AWS_SECRET_ACCESS_KEY' => !empty($_ENV['AWS_SECRET_ACCESS_KEY']) ? 'set' : 'not set'
            ]
        ]
    ]);
}
?> 