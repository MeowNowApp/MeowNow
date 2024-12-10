<?php
require __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Dotenv\Dotenv;

header('Content-Type: application/json');

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$bucket = $_ENV['AWS_BUCKET_NAME'];
$region = $_ENV['AWS_REGION'];
$s3BaseUrl = "https://$bucket.s3.$region.amazonaws.com/";

try {
    $s3 = new S3Client([
        'region' => $region,
        'version' => 'latest',
        'credentials' => [
            'key' => $_ENV['AWS_ACCESS_KEY_ID'],
            'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
        ],
    ]);

    $objects = $s3->listObjectsV2([
        'Bucket' => $bucket,
    ]);

    $files = [];
    if (isset($objects['Contents'])) {
        foreach ($objects['Contents'] as $object) {
            $files[] = $s3BaseUrl . $object['Key'];
        }
    }

    echo json_encode($files, JSON_UNESCAPED_SLASHES);;
} catch (AwsException $e) {
    http_response_code(500);
    echo json_encode(['error' => "Error fetching files: " . $e->getMessage()]);
}
?>