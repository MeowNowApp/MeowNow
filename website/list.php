<?php
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$bucket = $_ENV['AWS_BUCKET_NAME'];

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
        'Bucket' => $bucket,
    ]);

    $files = [];
    if (isset($objects['Contents'])) {
        foreach ($objects['Contents'] as $object) {
            $files[] = $s3->getObjectUrl($bucket, $object['Key']);
        }
    }

    echo json_encode($files);
} catch (AwsException $e) {
    echo "Error fetching files: " . $e->getMessage();
}
?>