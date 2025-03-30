<?php

namespace MeowNow\Utils;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class ImageHandler {
    use AwsConfigTrait;

    private S3Client $s3Client;
    private string $bucket;
    private string $prefix;
    private string $compressedBucket;

    public function __construct() {
        $this->bucket = $this->getBucketName();
        $this->prefix = $this->getBucketPrefix();
        $this->compressedBucket = getenv('S3_COMPRESSED_BUCKET') ?: 'meownowcompressed';
        
        $this->s3Client = new S3Client($this->getAwsConfig());
    }

    public function getRandomImage(?int $width = null, ?int $height = null): array {
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

            // Get the object URL
            $url = $this->s3Client->getObjectUrl($bucket, $key);

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

    private function getCompressedImageKey(string $originalKey, ?int $width, ?int $height): ?string {
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

    private function generateCompressedKey(string $originalKey, ?int $width, ?int $height): string {
        $info = pathinfo($originalKey);
        $dimensions = '';
        if ($width) $dimensions .= "w{$width}";
        if ($height) $dimensions .= "h{$height}";
        return $info['dirname'] . '/' . $info['filename'] . $dimensions . '.' . $info['extension'];
    }
} 