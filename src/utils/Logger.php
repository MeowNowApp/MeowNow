<?php

namespace MeowNow\Utils;

class Logger {
    private $logDir;
    private $apiLogFile;
    private $uploadLogFile;

    public function __construct() {
        $this->logDir = __DIR__ . '/../../logs';
        $this->apiLogFile = $this->logDir . '/api.log';
        $this->uploadLogFile = $this->logDir . '/uploads.log';
        
        // Ensure logs directory exists
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public function logRequest($request) {
        // Get IP address and mask last two octets
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $maskedIp = preg_replace('/\.\d+\.\d+$/', '.xxx.xxx', $ip);

        // Get request details
        $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $timestamp = date('Y-m-d H:i:s');

        // Create log entry
        $logEntry = sprintf(
            "[%s] %s - %s %s - User-Agent: %s\n",
            $timestamp,
            $maskedIp,
            $method,
            $uri,
            $userAgent
        );

        // Write to log file
        file_put_contents($this->apiLogFile, $logEntry, FILE_APPEND);
    }

    public function logUpload($file, $success, $message = '') {
        // Get IP address and mask last two octets
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $maskedIp = preg_replace('/\.\d+\.\d+$/', '.xxx.xxx', $ip);

        // Get upload details
        $filename = $file['name'] ?? 'unknown';
        $size = $file['size'] ?? 0;
        $type = $file['type'] ?? 'unknown';
        $timestamp = date('Y-m-d H:i:s');

        // Format file size
        $formattedSize = $this->formatFileSize($size);

        // Create log entry
        $logEntry = sprintf(
            "[%s] %s - Upload: %s - Size: %s - Type: %s - Status: %s%s\n",
            $timestamp,
            $maskedIp,
            $filename,
            $formattedSize,
            $type,
            $success ? 'Success' : 'Failed',
            $message ? " - Message: $message" : ''
        );

        // Write to log file
        file_put_contents($this->uploadLogFile, $logEntry, FILE_APPEND);
    }

    private function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
} 