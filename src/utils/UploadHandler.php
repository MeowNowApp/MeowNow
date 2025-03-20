<?php

namespace MeowNow\Utils;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class UploadHandler {
    private $s3Client;
    private $bucket;
    private $prefix;
    private $logger;
    private $maxFileSize;
    private $allowedTypes;

    public function __construct(Logger $logger) {
        $this->bucket = getenv('AWS_BUCKET_NAME');
        $this->prefix = getenv('AWS_BUCKET_PREFIX');
        $this->logger = $logger;
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
        $this->allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
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

    public function handleUpload($file) {
        try {
            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('cat_') . '.' . $extension;
            $key = $this->prefix . '/' . $filename;

            // Upload to S3
            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'Body'   => fopen($file['tmp_name'], 'rb'),
                'ContentType' => $file['type'],
                'ACL'    => 'public-read'
            ]);

            // Log successful upload
            $this->logger->logUpload($file, true);

            return [
                'success' => true,
                'url' => $result['ObjectURL'],
                'key' => $key
            ];
        } catch (\Exception $e) {
            // Log failed upload
            $this->logger->logUpload($file, false, $e->getMessage());
            throw $e;
        }
    }

    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('File upload failed: ' . $this->getUploadErrorMessage($file['error']));
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            throw new \Exception('File too large. Maximum size is ' . ($this->maxFileSize / 1024 / 1024) . 'MB');
        }

        // Check file type
        if (!in_array($file['type'], $this->allowedTypes)) {
            throw new \Exception('Invalid file type. Allowed types: ' . implode(', ', $this->allowedTypes));
        }

        // Additional security checks
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \Exception('Invalid upload');
        }
    }

    private function getUploadErrorMessage($error) {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }
} 