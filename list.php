<?php
// Load environment variables from .env file
require 'vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Optional: validate required environment variables
$dotenv->required(['AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY', 'AWS_REGION', 'S3_BUCKET']);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// AWS S3 Configuration from environment variables
$awsRegion = $_ENV['AWS_REGION'];
$s3Bucket = $_ENV['S3_BUCKET'];
$s3Prefix = $_ENV['S3_PREFIX'] ?? 'cats/';

// Local file that stores the list of uploaded images
$catListFile = 'cat_list.json';

// Function to get cat images from S3
function getCatsFromS3() {
    global $awsRegion, $s3Bucket, $s3Prefix;
    
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
        
        // List objects in the bucket with the specified prefix
        $result = $s3->listObjects([
            'Bucket' => $s3Bucket,
            'Prefix' => $s3Prefix
        ]);
        
        $cats = [];
        
        // Process the results
        if (isset($result['Contents'])) {
            foreach ($result['Contents'] as $object) {
                // Skip the prefix directory itself
                if ($object['Key'] !== $s3Prefix) {
                    $cats[] = [
                        'key' => $object['Key'],
                        'url' => $s3->getObjectUrl($s3Bucket, $object['Key']),
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

// Function to get cat images from the local JSON file
function getCatsFromLocalFile() {
    global $catListFile;
    
    if (file_exists($catListFile)) {
        $catList = json_decode(file_get_contents($catListFile), true);
        return $catList ?: [];
    }
    
    return [];
}

// Try to get cats from S3 first, fall back to local file if that fails
$cats = getCatsFromS3();

// If S3 failed or returned no results, try the local file
if (empty($cats)) {
    $cats = getCatsFromLocalFile();
}

// If we have cats, return them as JSON
if (!empty($cats)) {
    // For backward compatibility with the old format, just return the URLs
    $urls = array_map(function($cat) {
        return $cat['filename']; // For backward compatibility, return just the filename
    }, $cats);
    
    echo json_encode($urls);
} else {
    // Return an empty array if no cats were found
    echo json_encode([]);
}
?> 