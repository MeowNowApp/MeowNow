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
    private string $pendingPrefix;
    private string $approvedPrefix;
    private string $compressedBucket;

    public function __construct(Logger $logger) {
        $this->bucket = $this->getBucketName();
        $this->prefix = $this->getBucketPrefix();
        $this->logger = $logger;
        $this->maxFileSize = (int)(getenv('MAX_UPLOAD_SIZE') ?: 10 * 1024 * 1024); // 10MB default
        $this->allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $this->pendingPrefix = 'pending/';
        $this->approvedPrefix = 'approved/';
        $this->compressedBucket = getenv('S3_COMPRESSED_BUCKET') ?: getenv('AWS_BUCKET_COMPRESSED') ?: $this->bucket;
        
        $this->s3Client = new S3Client($this->getAwsConfig());
    }

    public function handleUpload(array $file): array {
        try {
            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('cat_') . '.' . $extension;
            
            // Construct the key with pending prefix
            $key = $this->pendingPrefix . $filename;

            // Upload to S3 pending folder
            $this->s3Client->putObject([
                'Bucket' => $this->compressedBucket,
                'Key'    => $key,
                'Body'   => fopen($file['tmp_name'], 'rb'),
                'ContentType' => $file['type']
            ]);

            // Log successful upload
            $this->logger->logUpload($file, true);

            return [
                'success' => true,
                'message' => 'Image uploaded successfully and pending review',
                'key' => $key
            ];
        } catch (\Exception $e) {
            // Log failed upload
            $this->logger->logUpload($file, false, $e->getMessage());
            throw $e;
        }
    }

    public function approveImage(string $key): array {
        try {
            // Get the filename without the pending prefix
            $filename = basename($key);
            $newKey = $this->approvedPrefix . $filename;

            // Copy from pending to approved
            $this->s3Client->copyObject([
                'Bucket' => $this->compressedBucket,
                'CopySource' => $this->compressedBucket . '/' . $key,
                'Key' => $newKey
            ]);

            // Delete from pending
            $this->s3Client->deleteObject([
                'Bucket' => $this->compressedBucket,
                'Key' => $key
            ]);

            return [
                'success' => true,
                'url' => $this->s3Client->getObjectUrl($this->compressedBucket, $newKey),
                'key' => $newKey
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to approve image: ' . $e->getMessage());
        }
    }

    public function rejectImage(string $key): bool {
        try {
            // Delete from pending
            $this->s3Client->deleteObject([
                'Bucket' => $this->compressedBucket,
                'Key' => $key
            ]);
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Failed to reject image: ' . $e->getMessage());
        }
    }

    public function getPendingImages(): array {
        try {
            // Temporarily use compressed bucket for testing
            $compressedBucket = getenv('S3_COMPRESSED_BUCKET') ?: getenv('AWS_BUCKET_COMPRESSED') ?: $this->bucket;
            
            $result = $this->s3Client->listObjects([
                'Bucket' => $compressedBucket,
                'Prefix' => '' // List all images for testing
            ]);

            $images = [];
            $contents = $result->get('Contents');
            
            if ($contents) {
                foreach ($contents as $object) {
                    // Skip any potential folders
                    if (substr($object['Key'], -1) === '/') {
                        continue;
                    }
                    
                    $images[] = [
                        'key' => $object['Key'],
                        'url' => $this->s3Client->getObjectUrl($compressedBucket, $object['Key']),
                        'lastModified' => $object['LastModified']->format('Y-m-d H:i:s')
                    ];
                }
            }
            return $images;
        } catch (\Exception $e) {
            throw new \Exception('Failed to get images: ' . $e->getMessage());
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