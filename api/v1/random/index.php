<?php
// Update the relative path to account for new location
require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

// Update path to .env file
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Set headers for API responses
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Get request parameters
$format = $_GET['format'] ?? 'redirect';
$width = $_GET['width'] ?? null;
$height = $_GET['height'] ?? null;

// Define cache settings
$cacheFile = __DIR__ . '/../../cat_list.json';
$cacheExpiry = 3600; // 1 hour

// Configure AWS S3
$s3Config = [
    'version' => 'latest',
    'region'  => $_ENV['AWS_REGION'],
    'credentials' => [
        'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
        'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
    ],
];

$s3 = new Aws\S3\S3Client($s3Config);
$bucket = $_ENV['AWS_COMPRESSED_BUCKET'];

// Function to get cats from S3
function getCatsFromS3($s3, $bucket) {
    try {
        $objects = $s3->listObjects([
            'Bucket' => $bucket
        ]);
        
        return array_map(function($object) use ($bucket) {
            return [
                'key' => $object['Key'],
                'url' => "https://{$bucket}.s3.amazonaws.com/{$object['Key']}",
                'filename' => basename($object['Key']),
                'lastModified' => $object['LastModified']->format('Y-m-d H:i:s')
            ];
        }, $objects['Contents'] ?? []);
    } catch (Exception $e) {
        return [];
    }
}

// Function to get cats from local cache file
function getCatsFromLocalFile($cacheFile) {
    if (file_exists($cacheFile)) {
        return json_decode(file_get_contents($cacheFile), true);
    }
    return [];
}

// Function to save cats to local cache file
function saveCatsToLocalFile($cats, $cacheFile) {
    file_put_contents($cacheFile, json_encode($cats));
}

// Get cat list (from cache if available and fresh, otherwise from S3)
$cats = [];
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheExpiry)) {
    $cats = getCatsFromLocalFile($cacheFile);
} else {
    $cats = getCatsFromS3($s3, $bucket);
    if (!empty($cats)) {
        saveCatsToLocalFile($cats, $cacheFile);
    }
}

// If no cats found, return error
if (empty($cats)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'No images available',
        'api_version' => 'v1'
    ]);
    exit;
}

// Get random cat
$randomCat = $cats[array_rand($cats)];

// Apply width/height parameters if provided
if ($width || $height) {
    $url = $randomCat['url'];
    $params = [];
    if ($width) $params[] = "w=$width";
    if ($height) $params[] = "h=$height";
    if (!empty($params)) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . implode('&', $params);
    }
    $randomCat['url'] = $url;
}

// Return response based on format
switch ($format) {
    case 'json':
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'image' => $randomCat,
            'api_version' => 'v1'
        ]);
        break;

    case 'url':
        header('Content-Type: text/plain');
        echo $randomCat['url'];
        break;

    case 'image':
        $imageContent = file_get_contents($randomCat['url']);
        if ($imageContent === false) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to fetch image',
                'api_version' => 'v1'
            ]);
            exit;
        }
        header('Content-Type: image/jpeg');
        header('Content-Length: ' . strlen($imageContent));
        echo $imageContent;
        break;

    case 'redirect':
    default:
        header('Location: ' . $randomCat['url']);
        break;
} 