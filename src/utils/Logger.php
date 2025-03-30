<?php

namespace MeowNow\Utils;

class Logger {
    private string $logDir;
    private string $apiLogFile;
    private string $uploadLogFile;

    public function __construct() {
        $this->logDir = getenv('LOG_DIRECTORY') ?: __DIR__ . '/../../logs';
        $this->apiLogFile = $this->logDir . '/api.log';
        $this->uploadLogFile = $this->logDir . '/uploads.log';
        
        $this->initializeLogDirectory();
    }

    private function initializeLogDirectory(): void {
        if (!is_dir($this->logDir)) {
            if (!mkdir($this->logDir, 0755, true)) {
                throw new \RuntimeException("Failed to create log directory: {$this->logDir}");
            }
        }
    }

    public function logRequest(array $request): void {
        $maskedIp = $this->maskIpAddress($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $logEntry = $this->formatLogEntry([
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $maskedIp,
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        file_put_contents($this->apiLogFile, $logEntry, FILE_APPEND);
    }

    private function maskIpAddress(string $ip): string {
        return preg_replace('/\.\d+\.\d+$/', '.xxx.xxx', $ip);
    }

    private function formatLogEntry(array $data): string {
        return sprintf(
            "[%s] %s - %s %s - User-Agent: %s\n",
            $data['timestamp'],
            $data['ip'],
            $data['method'],
            $data['uri'],
            $data['userAgent']
        );
    }

    public function logUpload(array $file, bool $success, string $message = ''): void {
        // Get IP address and mask last two octets
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $maskedIp = $this->maskIpAddress($ip);

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

    private function formatFileSize(int $bytes): string {
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