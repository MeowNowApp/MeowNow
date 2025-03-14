<?php
// Load environment variables from .env file
require 'vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Optional: validate required environment variables
$dotenv->required(['AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY', 'AWS_REGION', 'S3_COMPRESSED_BUCKET']);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// AWS S3 Configuration from environment variables
$awsRegion = $_ENV['AWS_REGION'];
$s3CompressedBucket = $_ENV['S3_COMPRESSED_BUCKET'];

// Local file that stores the list of uploaded images (cache and fallback)
$catListFile = 'cat_list.json';
$cacheExpiryTime = 3600; // Cache expiry time in seconds (1 hour)

// Function to get cat images from S3 compressed bucket
function getCatsFromS3() {
    global $awsRegion, $s3CompressedBucket;
    
    try {
        // Create an S3 client with credentials from environment variables
        $s3 = new Aws\S3\S3Client([
            'version' => 'latest',
            'region'  => $awsRegion,
            'credentials' => [
                'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
                'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
            ]
        ]);
        
        // List all objects in the compressed bucket
        $result = $s3->listObjects([
            'Bucket' => $s3CompressedBucket
        ]);
        
        $cats = [];
        
        // Process the results
        if (isset($result['Contents'])) {
            foreach ($result['Contents'] as $object) {
                // Only include image files
                if (preg_match('/\.(jpe?g|png)$/i', $object['Key'])) {
                    $cats[] = [
                        'key' => $object['Key'],
                        'url' => $s3->getObjectUrl($s3CompressedBucket, $object['Key']),
                        'filename' => basename($object['Key']),
                        'lastModified' => $object['LastModified']->format('Y-m-d H:i:s')
                    ];
                }
            }
        }
        
        return $cats;
    } catch (Exception $e) {
        // Log the error
        error_log("S3 list error: " . $e->getMessage());
        return [];
    }
}

// Function to get cat images from the local JSON file (cache/fallback)
function getCatsFromLocalFile() {
    global $catListFile;
    
    if (file_exists($catListFile)) {
        $catList = json_decode(file_get_contents($catListFile), true);
        return $catList ?: [];
    }
    
    return [];
}

// Function to save cat images to the local JSON file (cache)
function saveCatsToLocalFile($cats) {
    global $catListFile;
    
    // Add timestamp for cache management
    $data = [
        'timestamp' => time(),
        'cats' => $cats
    ];
    
    // Save to file
    file_put_contents($catListFile, json_encode($data, JSON_PRETTY_PRINT));
}

// Check if we have a valid cache
$useCache = false;
if (file_exists($catListFile)) {
    $cacheData = json_decode(file_get_contents($catListFile), true);
    
    // Check if cache is valid (not expired)
    if (isset($cacheData['timestamp']) && 
        (time() - $cacheData['timestamp'] < $cacheExpiryTime)) {
        $useCache = true;
        $cats = $cacheData['cats'];
    }
}

// If cache is not valid, fetch from S3
if (!$useCache) {
    $cats = getCatsFromS3();
    
    // If we got cats from S3, update the cache
    if (!empty($cats)) {
        saveCatsToLocalFile($cats);
    } else {
        // If S3 failed, try to use the cache even if expired
        $cacheData = getCatsFromLocalFile();
        if (isset($cacheData['cats'])) {
            $cats = $cacheData['cats'];
        }
    }
}

// If we have cats, return them as JSON
if (!empty($cats)) {
    // Return the full cat objects with URLs
    echo json_encode($cats);
} else {
    // Return an empty array if no cats were found
    echo json_encode([]);
}
?> 