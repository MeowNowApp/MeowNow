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
$compressionQuality = 60; // JPEG compression quality (0-100, lower = smaller file)

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

// Function to compress and resize an image
function compressAndResizeImage($sourcePath, $destinationPath, $quality, $scale) {
    // Get image information
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }
    
    $mime = $imageInfo['mime'];
    
    // Create image resource based on file type
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$image) {
        return false;
    }
    
    // Fix orientation for JPEG images (based on EXIF data)
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        try {
            $exif = @exif_read_data($sourcePath);
            if ($exif && isset($exif['Orientation'])) {
                $orientation = $exif['Orientation'];
                
                // Based on the orientation, rotate or flip the image
                switch ($orientation) {
                    case 2: // Horizontal flip
                        imageflip($image, IMG_FLIP_HORIZONTAL);
                        break;
                    case 3: // 180 degree rotation
                        $image = imagerotate($image, 180, 0);
                        break;
                    case 4: // Vertical flip
                        imageflip($image, IMG_FLIP_VERTICAL);
                        break;
                    case 5: // Vertical flip + 90 rotate right
                        imageflip($image, IMG_FLIP_VERTICAL);
                        $image = imagerotate($image, -90, 0);
                        break;
                    case 6: // 90 degree rotate right
                        $image = imagerotate($image, -90, 0);
                        break;
                    case 7: // Horizontal flip + 90 rotate right
                        imageflip($image, IMG_FLIP_HORIZONTAL);
                        $image = imagerotate($image, -90, 0);
                        break;
                    case 8: // 90 degree rotate left
                        $image = imagerotate($image, 90, 0);
                        break;
                }
            }
        } catch (Exception $e) {
            // If there's an error reading EXIF data, continue without orientation fix
            logMessage("EXIF read error for " . basename($sourcePath) . ": " . $e->getMessage());
        }
    }
    
    // Calculate new dimensions
    $width = imagesx($image);
    $height = imagesy($image);
    $newWidth = round($width * $scale);
    $newHeight = round($height * $scale);
    
    // Create a new image with the new dimensions
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Handle transparency for PNG images
    if ($mime === 'image/png') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize the image
    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save the image
    $success = false;
    switch ($mime) {
        case 'image/jpeg':
            $success = imagejpeg($newImage, $destinationPath, $quality);
            break;
        case 'image/png':
            // PNG quality is 0-9, convert from 0-100 scale
            $pngQuality = round((100 - $quality) / 11.1);
            $success = imagepng($newImage, $destinationPath, $pngQuality);
            break;
    }
    
    // Free up memory
    imagedestroy($image);
    imagedestroy($newImage);
    
    return $success;
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
                // Compress and resize the uploaded image
                $tempPath = $targetPath . '.temp';
                rename($targetPath, $tempPath);
                
                if (compressAndResizeImage($tempPath, $targetPath, $compressionQuality, $resizeScale)) {
                    // Get file sizes for logging
                    $originalSize = filesize($tempPath);
                    $compressedSize = filesize($targetPath);
                    $sizeReduction = round(($originalSize - $compressedSize) / $originalSize * 100);
                    
                    // Remove the temporary file
                    unlink($tempPath);
                    
                    $successMessage = "Upload successful for $fileName. Compressed from " . 
                                     round($originalSize / 1024) . "KB to " . 
                                     round($compressedSize / 1024) . "KB ($sizeReduction% reduction). " .
                                     "<a href='$targetPath' target='_blank'>View uploaded file</a>.";
                    echo '<div class="success">' . $successMessage . '</div>';
                    logMessage($successMessage);
                } else {
                    // If compression fails, keep the original file
                    rename($tempPath, $targetPath);
                    $successMessage = "Upload successful for $fileName (compression failed). <a href='$targetPath' target='_blank'>View uploaded file</a>.";
                    echo '<div class="success">' . $successMessage . '</div>';
                    logMessage($successMessage);
                }
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
    <footer>
        <p><a href="index.html">Back to Cat Gallery</a></p>
    </footer>
</body>
</html>';
?>