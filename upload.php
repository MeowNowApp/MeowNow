<?php
// Load environment variables from .env file
require 'vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Optional: validate required environment variables
$dotenv->required(['AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY', 'AWS_REGION', 'S3_BUCKET']);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Override PHP settings (if allowed)
ini_set('upload_max_filesize', '35M');
ini_set('post_max_size', '40M');

// Configuration from environment variables
$maxTotalSize = $_ENV['MAX_TOTAL_UPLOAD'] ?? 250 * 1024 * 1024; // 250MB total
$maxFileSize = $_ENV['MAX_UPLOAD_SIZE'] ?? 50 * 1024 * 1024; // 50MB per file
$allowedTypes = ['image/jpeg', 'image/png']; // Allowed MIME types
$allowedExtensions = ['jpg', 'jpeg', 'png']; // Allowed file extensions
$logDir = $_ENV['LOG_DIRECTORY'] ?? getcwd() . '/logs/'; // Log directory
$logFile = $logDir . 'upload.log'; // Log file for tracking uploads and errors

// AWS S3 Configuration from environment variables
$awsRegion = $_ENV['AWS_REGION'];
$s3Bucket = $_ENV['S3_BUCKET'];
$s3Prefix = $_ENV['S3_PREFIX'] ?? 'cats/';

// Ensure the log directory exists and is writable
if (!is_dir($logDir)) {
    if (!mkdir($logDir, 0755, true)) {
        die('Error: Failed to create log directory.');
    }
}
if (!is_writable($logDir)) {
    die('Error: Log directory is not writable.');
}

// Log function to write messages to the log file
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']; // Get the user's IP address (handles proxies)
    $logEntry = "[$timestamp] [IP: $ip] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Function to translate error codes into human-readable messages
function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return "The uploaded file exceeds the upload_max_filesize directive in php.ini.";
        case UPLOAD_ERR_FORM_SIZE:
            return "The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form.";
        case UPLOAD_ERR_PARTIAL:
            return "The uploaded file was only partially uploaded.";
        case UPLOAD_ERR_NO_FILE:
            return "No file was uploaded.";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Missing a temporary folder.";
        case UPLOAD_ERR_CANT_WRITE:
            return "Failed to write file to disk.";
        case UPLOAD_ERR_EXTENSION:
            return "A PHP extension stopped the file upload.";
        default:
            return "Unknown upload error.";
    }
}

// Function to upload a file to S3
function uploadToS3($filePath, $s3Key) {
    global $awsRegion, $s3Bucket;
    
    try {
        // Create an S3 client with credentials from environment variables
        $s3 = new Aws\S3\S3Client([
            'version' => 'latest',
            'region'  => $awsRegion,
            'credentials' => [
                'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
                'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
            ]
        ]);
        
        // Upload the file to S3
        $result = $s3->putObject([
            'Bucket' => $s3Bucket,
            'Key'    => $s3Key,
            'SourceFile' => $filePath,
            'ACL'    => 'public-read', // Make the file publicly accessible
            'ContentType' => mime_content_type($filePath)
        ]);
        
        // Return the URL of the uploaded file
        return $result['ObjectURL'];
    } catch (Exception $e) {
        logMessage("S3 upload error: " . $e->getMessage());
        return false;
    }
}

// Output the HTML header with CSS
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Cat Images</title>
    <link rel="stylesheet" href="./style.css">
    <link rel="stylesheet" href="./form.css">
</head>
<body>
    <div class="upload-container">
        <h1>Upload Cat Images</h1>';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['catImage'])) {
    $files = $_FILES['catImage'];

    // Normalize the $_FILES array for both single and multiple uploads
    if (!is_array($files['name'])) {
        // Single file upload: Convert to a multi-file format
        $files = [
            'name' => [$files['name']],
            'type' => [$files['type']],
            'tmp_name' => [$files['tmp_name']],
            'error' => [$files['error']],
            'size' => [$files['size']]
        ];
    }

    // Ensure proper array structure for multiple uploads
    $fileCount = count($files['name']);

    // Calculate total upload size
    $totalSize = array_sum($files['size']);
    if ($totalSize > $maxTotalSize) {
        $errorMessage = 'Error: Total upload size exceeds the 250MB limit.';
        logMessage($errorMessage);
        echo '<div class="error">' . htmlspecialchars($errorMessage) . '</div>';
    } else {
        for ($i = 0; $i < $fileCount; $i++) {
            $fileName = $files['name'][$i];
            $fileTmpName = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $fileError = $files['error'][$i];

            // Check for upload errors
            if ($fileError !== UPLOAD_ERR_OK) {
                $errorMessage = "Upload failed for $fileName: " . getUploadErrorMessage($fileError);
                echo '<div class="error">' . htmlspecialchars($errorMessage) . '</div>';
                logMessage($errorMessage);
                continue;
            }

            // Validate file size
            if ($fileSize > $maxFileSize) {
                $errorMessage = "Error: File $fileName exceeds the 50MB limit.";
                echo '<div class="error">' . htmlspecialchars($errorMessage) . '</div>';
                logMessage($errorMessage);
                continue;
            }

            // Validate file type (MIME type and extension)
            $fileType = mime_content_type($fileTmpName);
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($fileType, $allowedTypes) || !in_array($fileExtension, $allowedExtensions)) {
                $errorMessage = "Error: Only JPG and PNG files are allowed ($fileName).";
                echo '<div class="error">' . htmlspecialchars($errorMessage) . '</div>';
                logMessage($errorMessage);
                continue;
            }

            // Generate a unique name for the S3 object
            $newFileName = uniqid('cat_', true) . '.' . $fileExtension;
            $s3Key = $s3Prefix . $newFileName;
            
            // Upload the file to S3
            $s3Url = uploadToS3($fileTmpName, $s3Key);
            
            if ($s3Url) {
                $successMessage = "Upload successful for $fileName. <a href='$s3Url' target='_blank'>View uploaded file</a>.";
                echo '<div class="success">' . $successMessage . '</div>';
                logMessage("Uploaded $fileName to S3: $s3Url");
                
                // Add the file to the list of available cat images
                $catListFile = 'cat_list.json';
                $catList = [];
                if (file_exists($catListFile)) {
                    $catList = json_decode(file_get_contents($catListFile), true) ?: [];
                }
                $catList[] = [
                    'key' => $s3Key,
                    'url' => $s3Url,
                    'filename' => $newFileName,
                    'uploaded' => date('Y-m-d H:i:s')
                ];
                file_put_contents($catListFile, json_encode($catList, JSON_PRETTY_PRINT));
            } else {
                $errorMessage = "Error: Failed to upload $fileName to S3.";
                echo '<div class="error">' . htmlspecialchars($errorMessage) . '</div>';
                logMessage($errorMessage);
            }
        }
    }
}

// Output the HTML form
echo '
        <form action="upload.php" method="post" enctype="multipart/form-data">
            <label for="catImage" class="custom-file-upload">
                Choose Cat Images
            </label>
            <input type="file" name="catImage[]" id="catImage" multiple accept="image/jpeg, image/png">
            <div id="file-name-display" class="file-name"></div>
            <button type="submit">Upload</button>
        </form>
    </div>

    <script src="./upload.js"></script>
    <footer>
        <p><a href="index.html">Back to Cat Gallery</a></p>
    </footer>
</body>
</html>';
?>