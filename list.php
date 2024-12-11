<?php
require __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Dotenv\Dotenv;

header('Content-Type: application/json');

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$bucket = 'randomcatcompressed';

try {
    $s3 = new S3Client([
        'region' => $_ENV['AWS_REGION'],
        'version' => 'latest',
        'credentials' => [
            'key' => $_ENV['AWS_ACCESS_KEY_ID'],
            'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
        ],
    ]);

    $objects = $s3->listObjectsV2([
        'Bucket' => $bucket
    ]);

    $files = [];
    if (isset($objects['Contents'])) {
        foreach ($objects['Contents'] as $object) {
            // Extract just the filename from the full path
            $files[] = basename($object['Key']);
        }
    }

    echo json_encode($files);
} catch (AwsException $e) {
    http_response_code(500);
    echo json_encode(['error' => "Error fetching files: " . $e->getMessage()]);
}
?>