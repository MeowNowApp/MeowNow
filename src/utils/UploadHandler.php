<?php

namespace MeowNow\Utils;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class UploadHandler {
    use AwsConfigTrait;

    private S3Client $s3Client;
    private string $bucket;
    private string $prefix;
    private Logger $logger;
    private int $maxFileSize;
    private array $allowedTypes;

    public function __construct(Logger $logger) {
        $this->bucket = $this->getBucketName();
        $this->prefix = $this->getBucketPrefix();
        $this->logger = $logger;
        $this->maxFileSize = (int)(getenv('MAX_UPLOAD_SIZE') ?: 10 * 1024 * 1024); // 10MB default
        $this->allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        $this->s3Client = new S3Client($this->getAwsConfig());
    }

    public function handleUpload(array $file): array {
        try {
            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('cat_') . '.' . $extension;
            
            // Construct the key with proper prefix handling
            $key = $this->prefix ? $this->prefix . '/' . $filename : $filename;

            // Upload to S3
            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'Body'   => fopen($file['tmp_name'], 'rb'),
                'ContentType' => $file['type']
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

    private function validateFile(array $file): void {
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

    private function getUploadErrorMessage(int $error): string {
        return match($error) {
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
            default => 'Unknown upload error'
        };
    }
} 