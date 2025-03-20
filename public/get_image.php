<?php
// Load AWS SDK
require_once __DIR__ . '/../vendor/autoload.php';

// AWS S3 Configuration
$awsRegion = getenv('AWS_REGION') ?: 'us-east-1';
$s3CompressedBucket = getenv('S3_COMPRESSED_BUCKET') ?: 'meownowcompressed';
$awsAccessKey = getenv('AWS_ACCESS_KEY_ID');
$awsSecretKey = getenv('AWS_SECRET_ACCESS_KEY');

// Get the image key from the request
$key = isset($_GET['key']) ? $_GET['key'] : '';

if (empty($key)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Missing image key');
}

try {
    // Create an S3 client
    $s3 = new Aws\S3\S3Client([
        'version' => 'latest',
        'region'  => $awsRegion,
        'credentials' => [
            'key'    => $awsAccessKey,
            'secret' => $awsSecretKey,
        ]
    ]);

    // Get the object from S3
    $result = $s3->getObject([
        'Bucket' => $s3CompressedBucket,
        'Key'    => $key
    ]);

    // Set the content type header
    $contentType = $result['ContentType'] ?? 'image/jpeg';
    header('Content-Type: ' . $contentType);
    
    // Set cache headers
    header('Cache-Control: public, max-age=3600');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

    // Output the image data
    echo $result['Body'];
} catch (Exception $e) {
    error_log("Error fetching image from S3: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit('Error fetching image');
} 