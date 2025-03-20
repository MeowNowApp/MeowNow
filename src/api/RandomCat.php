<?php

namespace MeowNow\Api;

use MeowNow\Utils\ImageHandler;
use MeowNow\Utils\Logger;

class RandomCat {
    private $imageHandler;
    private $logger;

    public function __construct() {
        $this->imageHandler = new ImageHandler();
        $this->logger = new Logger();
    }

    public function getRandomCat($format = 'redirect', $width = null, $height = null) {
        try {
            // Log the request
            $this->logger->logRequest($_SERVER);

            // Get and validate width/height parameters
            if ($width !== null) {
                $width = filter_var($width, FILTER_VALIDATE_INT);
                if ($width === false || $width < 1 || $width > 2000) {
                    throw new \Exception('Invalid width parameter. Must be between 1 and 2000.');
                }
            }

            if ($height !== null) {
                $height = filter_var($height, FILTER_VALIDATE_INT);
                if ($height === false || $height < 1 || $height > 2000) {
                    throw new \Exception('Invalid height parameter. Must be between 1 and 2000.');
                }
            }

            $imageData = $this->imageHandler->getRandomImage($width, $height);
            
            switch ($format) {
                case 'json':
                    return [
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
                    ];
                case 'url':
                    return $imageData['url'];
                case 'image':
                    header('Content-Type: ' . $imageData['contentType']);
                    header('Content-Length: ' . $imageData['contentLength']);
                    readfile($imageData['url']);
                    exit;
                default:
                    header('Location: ' . $imageData['url']);
                    exit;
            }
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
} 