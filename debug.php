<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$catsDir = './cats/';
$allowedExtensions = ['jpg', 'jpeg', 'png'];

echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Loading Diagnostics</title>
    <style>
        body { font-family: sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #333; }
        .section { margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .error { color: red; }
        .success { color: green; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        img { max-width: 200px; max-height: 200px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Image Loading Diagnostics</h1>';

// Check if the cats directory exists
echo '<div class="section">
    <h2>Directory Check</h2>';
if (is_dir($catsDir)) {
    echo '<p class="success">✓ The cats directory exists at: ' . realpath($catsDir) . '</p>';
} else {
    echo '<p class="error">✗ The cats directory does not exist at: ' . realpath(dirname(__FILE__)) . '/' . $catsDir . '</p>';
}

// Check if the directory is readable
if (is_readable($catsDir)) {
    echo '<p class="success">✓ The cats directory is readable</p>';
} else {
    echo '<p class="error">✗ The cats directory is not readable. Check permissions.</p>';
}
echo '</div>';

// Check for image files
echo '<div class="section">
    <h2>Image Files Check</h2>';
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

if (count($files) > 0) {
    echo '<p class="success">✓ Found ' . count($files) . ' image files in the cats directory</p>';
    echo '<p>First 5 images:</p>';
    echo '<ul>';
    $count = 0;
    foreach ($files as $file) {
        if ($count++ < 5) {
            echo '<li>' . htmlspecialchars($file) . '</li>';
        } else {
            break;
        }
    }
    echo '</ul>';
} else {
    echo '<p class="error">✗ No image files found in the cats directory</p>';
}
echo '</div>';

// Check PHP configuration
echo '<div class="section">
    <h2>PHP Configuration</h2>
    <p>PHP Version: ' . phpversion() . '</p>';

// Check GD extension
if (extension_loaded('gd')) {
    echo '<p class="success">✓ GD extension is loaded</p>';
} else {
    echo '<p class="error">✗ GD extension is not loaded. This is required for image processing.</p>';
}

// Check EXIF extension
if (extension_loaded('exif')) {
    echo '<p class="success">✓ EXIF extension is loaded</p>';
} else {
    echo '<p class="warning">⚠ EXIF extension is not loaded. This may affect image orientation detection.</p>';
}
echo '</div>';

// Test list.php
echo '<div class="section">
    <h2>API Test: list.php</h2>';
$listUrl = 'list.php';
$listContent = @file_get_contents($listUrl);
if ($listContent !== false) {
    echo '<p class="success">✓ Successfully accessed list.php</p>';
    $jsonData = json_decode($listContent, true);
    if ($jsonData !== null) {
        echo '<p class="success">✓ list.php returns valid JSON</p>';
        if (isset($jsonData['images']) && is_array($jsonData['images'])) {
            echo '<p class="success">✓ list.php returns image data with ' . count($jsonData['images']) . ' images</p>';
            if (count($jsonData['images']) > 0) {
                echo '<p>Sample image data:</p>';
                echo '<pre>' . htmlspecialchars(json_encode($jsonData['images'][0], JSON_PRETTY_PRINT)) . '</pre>';
            }
        } else {
            echo '<p class="error">✗ list.php does not return the expected image data structure</p>';
        }
    } else {
        echo '<p class="error">✗ list.php does not return valid JSON</p>';
        echo '<p>Raw output:</p>';
        echo '<pre>' . htmlspecialchars($listContent) . '</pre>';
    }
} else {
    echo '<p class="error">✗ Failed to access list.php</p>';
}
echo '</div>';

// Test serve-image.php with a sample image
echo '<div class="section">
    <h2>Image Serving Test</h2>';
if (count($files) > 0) {
    $testImage = $files[0];
    $imageUrl = 'serve-image.php?img=' . urlencode($testImage);
    echo '<p>Testing image: ' . htmlspecialchars($testImage) . '</p>';
    echo '<p>URL: ' . htmlspecialchars($imageUrl) . '</p>';
    
    // Try to get headers
    $headers = @get_headers($imageUrl);
    if ($headers) {
        $statusLine = $headers[0];
        if (strpos($statusLine, '200') !== false) {
            echo '<p class="success">✓ serve-image.php returns HTTP 200 OK for the test image</p>';
        } else {
            echo '<p class="error">✗ serve-image.php returns non-200 status: ' . htmlspecialchars($statusLine) . '</p>';
        }
    } else {
        echo '<p class="error">✗ Could not get headers from serve-image.php</p>';
    }
    
    // Display the test image
    echo '<p>Test image display:</p>';
    echo '<img src="' . htmlspecialchars($imageUrl) . '" alt="Test image">';
} else {
    echo '<p class="warning">⚠ No images available to test serve-image.php</p>';
}
echo '</div>';

// Check for JavaScript errors
echo '<div class="section">
    <h2>JavaScript Test</h2>
    <p>Open your browser\'s developer console (F12) to check for JavaScript errors.</p>
    <p>The script below will attempt to fetch from list.php and display the result:</p>
    <div id="js-test-result">Testing...</div>
    <script>
        (function() {
            const resultElement = document.getElementById("js-test-result");
            resultElement.textContent = "Fetching from list.php...";
            
            fetch("list.php?random=true&limit=1")
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    resultElement.innerHTML = `<span class="success">✓ Successfully fetched data from list.php</span><br>`;
                    if (data.images && data.images.length > 0) {
                        resultElement.innerHTML += `<span class="success">✓ Found ${data.images.length} images</span><br>`;
                        resultElement.innerHTML += `First image: ${data.images[0].filename}<br>`;
                        resultElement.innerHTML += `URL: ${data.images[0].url}<br>`;
                        resultElement.innerHTML += `<img src="${data.images[0].url}" alt="Test image">`;
                    } else {
                        resultElement.innerHTML += `<span class="warning">⚠ No images found in the response</span>`;
                    }
                })
                .catch(error => {
                    resultElement.innerHTML = `<span class="error">✗ Error: ${error.message}</span>`;
                });
        })();
    </script>
</div>';

// Provide recommendations
echo '<div class="section">
    <h2>Recommendations</h2>
    <ul>
        <li>Check that all required files exist: list.php, serve-image.php, script.js</li>
        <li>Verify that the cats directory contains image files</li>
        <li>Check file permissions on the cats directory and image files</li>
        <li>Look for JavaScript errors in the browser console</li>
        <li>Verify that the server can execute PHP files</li>
        <li>Check that the GD and EXIF extensions are enabled in PHP</li>
    </ul>
</div>';

echo '</body>
</html>';
?> 