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
        // Get required configuration with fallbacks
        $this->bucket = getenv('AWS_BUCKET_NAME') 
            ?: getenv('S3_RAW_BUCKET')
            ?: getenv('AWS_BUCKET_RAW')
            ?: 'meownowraw'; // Fallback to known bucket name
            
        if (!$this->bucket) {
            throw new \Exception('AWS bucket name is not configured. Please set AWS_BUCKET_NAME, S3_RAW_BUCKET, or AWS_BUCKET_RAW environment variable.');
        }

        $this->prefix = trim(getenv('AWS_BUCKET_PREFIX') ?: getenv('S3_PREFIX') ?: '', '/');
        $this->compressedBucket = getenv('S3_COMPRESSED_BUCKET') ?: 'meownowcompressed';
        
        // Get AWS credentials with consistent naming
        $awsKey = getenv('AWS_ACCESS_KEY') ?: getenv('AWS_ACCESS_KEY_ID');
        $awsSecret = getenv('AWS_SECRET_KEY') ?: getenv('AWS_SECRET_ACCESS_KEY');
        
        if (!$awsKey || !$awsSecret) {
            throw new \Exception('AWS credentials are not configured. Please set AWS_ACCESS_KEY/AWS_ACCESS_KEY_ID and AWS_SECRET_KEY/AWS_SECRET_ACCESS_KEY environment variables.');
        }
        
        $config = [
            'version' => 'latest',
            'region'  => getenv('AWS_REGION') ?: 'us-east-1',
            'credentials' => [
                'key'    => $awsKey,
                'secret' => $awsSecret,
            ]
        ];

        // Only add endpoint configuration if it's set
        if ($endpoint = getenv('AWS_ENDPOINT')) {
            $config['endpoint'] = $endpoint;
            $config['use_path_style_endpoint'] = true;
        }
        
        $this->s3Client = new S3Client($config);
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