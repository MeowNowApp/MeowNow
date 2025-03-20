<?php

namespace MeowNow\Utils;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class ImageHandler {
    private $s3Client;
    private $bucket;
    private $prefix;
    private $compressedBucket;

    public function __construct() {
        $this->bucket = getenv('AWS_BUCKET_NAME');
        $this->prefix = getenv('AWS_BUCKET_PREFIX');
        $this->compressedBucket = getenv('AWS_BUCKET_COMPRESSED');
        
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region'  => getenv('AWS_REGION'),
            'credentials' => [
                'key'    => getenv('AWS_ACCESS_KEY'),
                'secret' => getenv('AWS_SECRET_KEY'),
            ],
            'endpoint' => getenv('AWS_ENDPOINT'),
            'use_path_style_endpoint' => true
        ]);
    }

    public function getRandomImage($width = null, $height = null) {
        try {
            // List objects in the bucket
            $result = $this->s3Client->listObjects([
                'Bucket' => $this->bucket,
                'Prefix' => $this->prefix
            ]);

            $objects = $result->get('Contents');
            if (empty($objects)) {
                throw new \Exception('No images found in bucket');
            }

            // Get a random object
            $randomObject = $objects[array_rand($objects)];
            $key = $randomObject['Key'];
            
            // Get object metadata
            $metadata = $this->s3Client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $key
            ]);

            // If width or height is specified, try to get from compressed bucket
            if ($width || $height) {
                $compressedKey = $this->getCompressedImageKey($key, $width, $height);
                if ($compressedKey) {
                    $key = $compressedKey;
                    $bucket = $this->compressedBucket;
                } else {
                    $bucket = $this->bucket;
                }
            } else {
                $bucket = $this->bucket;
            }

            // Generate a presigned URL that expires in 1 hour
            $command = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key'    => $key,
                'Expires' => 3600 // 1 hour
            ]);

            $url = (string) $this->s3Client->createPresignedUrl($command, '+1 hour');

            return [
                'key' => $key,
                'url' => $url,
                'filename' => basename($key),
                'lastModified' => $randomObject['LastModified']->format('Y-m-d H:i:s'),
                'contentType' => $metadata['ContentType'],
                'contentLength' => $metadata['ContentLength'],
                'width' => $width,
                'height' => $height
            ];
        } catch (AwsException $e) {
            throw new \Exception('Failed to get random image: ' . $e->getMessage());
        }
    }

    private function getCompressedImageKey($originalKey, $width, $height) {
        // Check if a compressed version exists
        $compressedKey = $this->generateCompressedKey($originalKey, $width, $height);
        
        try {
            $this->s3Client->headObject([
                'Bucket' => $this->compressedBucket,
                'Key' => $compressedKey
            ]);
            return $compressedKey;
        } catch (AwsException $e) {
            return null;
        }
    }

    private function generateCompressedKey($originalKey, $width, $height) {
        $info = pathinfo($originalKey);
        $dimensions = '';
        if ($width) $dimensions .= "w{$width}";
        if ($height) $dimensions .= "h{$height}";
        return $info['dirname'] . '/' . $info['filename'] . $dimensions . '.' . $info['extension'];
    }
} 