<?php

namespace MeowNow\Api;

use MeowNow\Utils\ImageHandler;
use MeowNow\Utils\Logger;

class RandomCat {
    private ImageHandler $imageHandler;
    private Logger $logger;

    public function __construct() {
        $this->imageHandler = new ImageHandler();
        $this->logger = new Logger();
    }

    public function getRandomCat(string $format = 'redirect', ?int $width = null, ?int $height = null): array|string {
        try {
            // Log the request
            $this->logger->logRequest($_SERVER);

            // Get and validate width/height parameters
            if ($width !== null) {
                $width = filter_var($width, FILTER_VALIDATE_INT);
                if ($width === false || $width < 1 || $width > 2000) {
                    throw new \InvalidArgumentException('Invalid width parameter. Must be between 1 and 2000.');
                }
            }

            if ($height !== null) {
                $height = filter_var($height, FILTER_VALIDATE_INT);
                if ($height === false || $height < 1 || $height > 2000) {
                    throw new \InvalidArgumentException('Invalid height parameter. Must be between 1 and 2000.');
                }
            }

            $imageData = $this->imageHandler->getRandomImage($width, $height);
            
            return match($format) {
                'json' => [
                    'success' => true,
                    'image' => [
                        'key' => $imageData['key'],
                        'url' => $imageData['url'],
                        'filename' => $imageData['filename'],
                        'lastModified' => $imageData['lastModified'],
                        'width' => $imageData['width'],
                        'height' => $imageData['height']
                    ],
                    'api_version' => 'v1'
                ],
                'url' => $imageData['url'],
                'image' => $this->outputImage($imageData),
                default => $this->redirectToImage($imageData['url'])
            };
        } catch (\Exception $e) {
            if ($format === 'json') {
                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
            throw $e;
        }
    }

    private function outputImage(array $imageData): void {
        header('Content-Type: ' . $imageData['contentType']);
        header('Content-Length: ' . $imageData['contentLength']);
        readfile($imageData['url']);
        exit;
    }

    private function redirectToImage(string $url): void {
        header('Location: ' . $url);
        exit;
    }
} 