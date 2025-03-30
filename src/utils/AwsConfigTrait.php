<?php

namespace MeowNow\Utils;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

trait AwsConfigTrait {
    protected function getAwsConfig(): array {
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
        
        return $config;
    }

    protected function getBucketName(): string {
        $bucket = getenv('AWS_BUCKET_NAME') 
            ?: getenv('S3_RAW_BUCKET')
            ?: getenv('AWS_BUCKET_RAW')
            ?: 'meownowraw';
            
        if (!$bucket) {
            throw new \Exception('AWS bucket name is not configured. Please set AWS_BUCKET_NAME, S3_RAW_BUCKET, or AWS_BUCKET_RAW environment variable.');
        }

        return $bucket;
    }

    protected function getBucketPrefix(): string {
        return trim(getenv('AWS_BUCKET_PREFIX') ?: getenv('S3_PREFIX') ?: '', '/');
    }
} 