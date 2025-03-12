<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Override PHP settings (if allowed)
ini_set('upload_max_filesize', '35M');
ini_set('post_max_size', '40M');

// Configuration
$maxTotalSize = 250 * 1024 * 1024; // 250MB total
$maxFileSize = 50 * 1024 * 1024; // 50MB per file
$allowedTypes = ['image/jpeg', 'image/png']; // Allowed MIME types
$allowedExtensions = ['jpg', 'jpeg', 'png']; // Allowed file extensions
$liveDir = './cats/'; // Upload directory: $PWD/cats
$logDir = getcwd() . '/logs/'; // Log directory: $PWD/logs
$logFile = $logDir . 'upload.log'; // Log file for tracking uploads and errors

// Ensure the upload directory exists and is writable
if (!is_dir($liveDir)) {
    if (!mkdir($liveDir, 0755, true)) {
        die('Error: Failed to create upload directory.');
    }
}
if (!is_writable($liveDir)) {
    die('Error: Upload directory is not writable.');
}

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

// Output the HTML header with CSS
echo '<!DOCTYPE html>
<html lang="en">
<head>
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
        $errorMessage = 'Error: Total upload size exceeds the 50MB limit.';
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
                $errorMessage = "Error: File $fileName exceeds the 10MB limit.";
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

            // Generate a unique name and save the file to the live folder
            $newFileName = uniqid('cat_', true) . '.' . $fileExtension;
            $targetPath = $liveDir . $newFileName;

            if (move_uploaded_file($fileTmpName, $targetPath)) {
                $successMessage = "Upload successful for $fileName. <a href='$targetPath' target='_blank'>View uploaded file</a>.";
                echo '<div class="success">' . $successMessage . '</div>';
                logMessage($successMessage);
            } else {
                $errorMessage = "Error: Failed to upload $fileName.";
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
</body>
</html>';
?>