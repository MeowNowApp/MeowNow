<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$catsDir = './cats/';
$allowedExtensions = ['jpg', 'jpeg', 'png'];

// Function to get EXIF orientation
function getExifOrientation($imagePath) {
    if (!function_exists('exif_read_data')) {
        return 'EXIF extension not available';
    }
    
    try {
        if (strtolower(pathinfo($imagePath, PATHINFO_EXTENSION)) === 'png') {
            return 'PNG (no EXIF data)';
        }
        
        $exif = @exif_read_data($imagePath);
        if (!$exif || !isset($exif['Orientation'])) {
            return 'No orientation data';
        }
        
        $orientation = $exif['Orientation'];
        $orientationText = [
            1 => 'Normal (1)',
            2 => 'Flipped horizontally (2)',
            3 => 'Rotated 180° (3)',
            4 => 'Flipped vertically (4)',
            5 => 'Rotated 90° CW and flipped vertically (5)',
            6 => 'Rotated 90° CW (6)',
            7 => 'Rotated 90° CW and flipped horizontally (7)',
            8 => 'Rotated 90° CCW (8)'
        ];
        
        return isset($orientationText[$orientation]) ? $orientationText[$orientation] : "Unknown ($orientation)";
    } catch (Exception $e) {
        return 'Error reading EXIF: ' . $e->getMessage();
    }
}

// Process image rotation if requested
if (isset($_POST['rotate']) && isset($_POST['image'])) {
    $imagePath = $catsDir . basename($_POST['image']);
    $degrees = intval($_POST['degrees']);
    
    if (file_exists($imagePath) && in_array(strtolower(pathinfo($imagePath, PATHINFO_EXTENSION)), $allowedExtensions)) {
        // Create a backup filename
        $backupPath = $imagePath . '.bak';
        
        // Try to create a backup
        if (!copy($imagePath, $backupPath)) {
            $backupError = "Warning: Could not create backup file. Will try to rotate without backup.";
        }
        
        // Load the image
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        if ($extension == 'jpg' || $extension == 'jpeg') {
            $source = imagecreatefromjpeg($imagePath);
        } else if ($extension == 'png') {
            $source = imagecreatefrompng($imagePath);
        } else {
            $source = false;
        }
        
        if ($source) {
            // Rotate the image
            $rotated = imagerotate($source, $degrees, 0);
            
            // Create a temporary file first
            $tempPath = $imagePath . '.tmp';
            $saveSuccess = false;
            
            // Save to temporary file first
            if ($extension == 'jpg' || $extension == 'jpeg') {
                $saveSuccess = imagejpeg($rotated, $tempPath, 95);
            } else if ($extension == 'png') {
                $saveSuccess = imagepng($rotated, $tempPath, 9);
            }
            
            // If save to temp was successful, try to replace the original
            if ($saveSuccess) {
                if (rename($tempPath, $imagePath)) {
                    $message = "Image rotated successfully.";
                    if (!isset($backupError)) {
                        $message .= " A backup was created as {$_POST['image']}.bak";
                    }
                } else {
                    $message = "Error: Could not replace the original file. Rotated image saved as {$_POST['image']}.tmp";
                }
            } else {
                $message = "Error: Could not save the rotated image.";
            }
            
            // Free memory
            imagedestroy($source);
            imagedestroy($rotated);
        } else {
            $message = "Failed to load image for rotation.";
        }
    } else {
        $message = "Invalid image file.";
    }
}

// Get all image files from the directory
$files = [];
if (is_dir($catsDir)) {
    $dir = new DirectoryIterator($catsDir);
    foreach ($dir as $fileInfo) {
        if ($fileInfo->isFile()) {
            $extension = strtolower(pathinfo($fileInfo->getFilename(), PATHINFO_EXTENSION));
            if (in_array($extension, $allowedExtensions)) {
                $files[] = $fileInfo->getFilename();
            }
        }
    }
}

// Sort files by name
sort($files);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Orientation Fix</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2 {
            color: #333;
        }
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .image-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            background-color: #f9f9f9;
        }
        .image-container {
            position: relative;
            margin-bottom: 10px;
        }
        img {
            max-width: 100%;
            height: auto;
            display: block;
            margin-bottom: 10px;
        }
        .image-info {
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .orientation {
            font-weight: bold;
            color: #007bff;
        }
        .controls {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        button {
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .message {
            background-color: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 10px 15px;
            margin-bottom: 20px;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px 15px;
            margin-bottom: 20px;
        }
        .css-fix {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f0f0f0;
            border-radius: 5px;
        }
        code {
            background-color: #e9ecef;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            background-color: #f0f0f0;
            cursor: pointer;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
        }
        .tab.active {
            background-color: #fff;
            border-bottom: 1px solid #fff;
            margin-bottom: -1px;
            z-index: 1;
        }
        .tab-content {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 0 5px 5px 5px;
        }
        .debug-info {
            margin-top: 20px;
            padding: 10px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.8rem;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <h1>Image Orientation Fix</h1>
    
    <?php if (isset($backupError)): ?>
    <div class="warning">
        <?php echo htmlspecialchars($backupError); ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($message)): ?>
    <div class="message">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>
    
    <div class="tabs">
        <div class="tab active" onclick="showTab('fix-tab')">Fix Orientation</div>
        <div class="tab" onclick="showTab('info-tab')">Information</div>
        <div class="tab" onclick="showTab('debug-tab')">Debug</div>
    </div>
    
    <div id="fix-tab" class="tab-content">
        <p>This tool helps you identify and fix images with incorrect orientation. Select an image to rotate it manually.</p>
        
        <div class="image-grid">
            <?php foreach ($files as $file): ?>
            <div class="image-card">
                <div class="image-container">
                    <img src="<?php echo htmlspecialchars($catsDir . $file); ?>?t=<?php echo time(); ?>" alt="<?php echo htmlspecialchars($file); ?>" class="orientation-fix">
                </div>
                <div class="image-info">
                    <strong><?php echo htmlspecialchars($file); ?></strong><br>
                    Orientation: <span class="orientation"><?php echo getExifOrientation($catsDir . $file); ?></span>
                </div>
                <form method="post" class="controls">
                    <input type="hidden" name="image" value="<?php echo htmlspecialchars($file); ?>">
                    <select name="degrees">
                        <option value="270">Rotate 90° Clockwise</option>
                        <option value="180">Rotate 180°</option>
                        <option value="90">Rotate 90° Counter-Clockwise</option>
                    </select>
                    <button type="submit" name="rotate">Rotate Image</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div id="info-tab" class="tab-content" style="display: none;">
        <h2>About Image Orientation</h2>
        
        <div class="css-fix">
            <h3>CSS Fix (Already Applied)</h3>
            <p>The following CSS has been added to your style.css file to fix orientation issues in browsers that support it:</p>
            <pre><code>img {
    image-orientation: from-image;
}

.orientation-fix {
    image-orientation: from-image;
}</code></pre>
            <p>This tells browsers to respect the EXIF orientation data in the images.</p>
        </div>
        
        <h3>Common Orientation Issues</h3>
        <p>Image orientation issues typically happen because:</p>
        <ul>
            <li>Cameras store orientation data in EXIF metadata</li>
            <li>Some browsers and applications don't read this metadata</li>
            <li>Different devices handle orientation differently</li>
        </ul>
        
        <h3>Orientation Values</h3>
        <p>The EXIF orientation tag can have these values:</p>
        <ul>
            <li><strong>1</strong> - Normal orientation (no rotation needed)</li>
            <li><strong>2</strong> - Flipped horizontally</li>
            <li><strong>3</strong> - Rotated 180°</li>
            <li><strong>4</strong> - Flipped vertically</li>
            <li><strong>5</strong> - Rotated 90° CW and flipped vertically</li>
            <li><strong>6</strong> - Rotated 90° CW (most common issue)</li>
            <li><strong>7</strong> - Rotated 90° CW and flipped horizontally</li>
            <li><strong>8</strong> - Rotated 90° CCW</li>
        </ul>
        
        <h3>Manual Fix vs. CSS Fix</h3>
        <p>The CSS fix (<code>image-orientation: from-image</code>) works in most modern browsers but not all. For maximum compatibility, you can use this tool to manually rotate problematic images.</p>
        
        <h3>Permission Issues</h3>
        <p>If you're seeing permission errors, you have a few options:</p>
        <ol>
            <li>Change permissions on your images folder with: <code>chmod -R 664 ./cats/</code></li>
            <li>Change ownership of your images folder with: <code>chown -R www-data:www-data ./cats/</code></li>
            <li>Use the "Safe Mode" tool (<code>orientation-fix-safe.php</code>) which creates new files instead of modifying existing ones</li>
        </ol>
    </div>
    
    <div id="debug-tab" class="tab-content" style="display: none;">
        <h2>Debug Information</h2>
        
        <h3>PHP Configuration</h3>
        <div class="debug-info">
            PHP Version: <?php echo phpversion(); ?>
            
            GD Extension: <?php echo extension_loaded('gd') ? 'Loaded' : 'Not loaded'; ?>
            
            EXIF Extension: <?php echo extension_loaded('exif') ? 'Loaded' : 'Not loaded'; ?>
            
            File Uploads: <?php echo ini_get('file_uploads') ? 'Enabled' : 'Disabled'; ?>
            
            Upload Max Filesize: <?php echo ini_get('upload_max_filesize'); ?>
            
            Post Max Size: <?php echo ini_get('post_max_size'); ?>
            
            Memory Limit: <?php echo ini_get('memory_limit'); ?>
        </div>
        
        <h3>Directory Permissions</h3>
        <div class="debug-info">
            Cats Directory: <?php echo $catsDir; ?>
            
            Directory Exists: <?php echo is_dir($catsDir) ? 'Yes' : 'No'; ?>
            
            Directory Readable: <?php echo is_readable($catsDir) ? 'Yes' : 'No'; ?>
            
            Directory Writable: <?php echo is_writable($catsDir) ? 'Yes' : 'No'; ?>
            
            Directory Permissions: <?php echo file_exists($catsDir) ? substr(sprintf('%o', fileperms($catsDir)), -4) : 'N/A'; ?>
            
            Directory Owner: <?php echo function_exists('posix_getpwuid') && file_exists($catsDir) ? posix_getpwuid(fileowner($catsDir))['name'] : 'Unknown'; ?>
            
            Web Server User: <?php echo function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'Unknown'; ?>
        </div>
        
        <h3>Test File Operations</h3>
        <?php
        $testFile = $catsDir . 'test_write_' . time() . '.txt';
        $writeTest = @file_put_contents($testFile, 'Test write operation');
        if ($writeTest !== false) {
            echo '<p style="color: green;">✓ Successfully created test file</p>';
            @unlink($testFile);
            echo '<p style="color: green;">✓ Successfully deleted test file</p>';
        } else {
            echo '<p style="color: red;">✗ Failed to create test file</p>';
        }
        ?>
    </div>
    
    <script>
        function showTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show the selected tab content
            document.getElementById(tabId).style.display = 'block';
            
            // Add active class to the clicked tab
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html> 