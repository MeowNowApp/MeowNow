<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MeowNow\Utils\Logger;
use MeowNow\Utils\UploadHandler;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../config');
$dotenv->load();

// Initialize logger and upload handler
$logger = new Logger();
$uploadHandler = new UploadHandler($logger);

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['key'])) {
        try {
            if ($_POST['action'] === 'approve') {
                $result = $uploadHandler->approveImage($_POST['key']);
                $message = 'Image approved successfully';
            } elseif ($_POST['action'] === 'reject') {
                $uploadHandler->rejectImage($_POST['key']);
                $message = 'Image rejected successfully';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get pending images
try {
    $pendingImages = $uploadHandler->getPendingImages();
} catch (Exception $e) {
    $error = $e->getMessage();
    $pendingImages = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Uploads - MeowNow Admin</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/components.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>MeowNow Admin</h1>
            <nav>
                <ul class="nav-list">
                    <li class="nav-item"><a href="/">Home</a></li>
                    <li class="nav-item"><a href="/upload.html">Upload</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <div class="card">
                <?php if (isset($message)): ?>
                    <div class="upload-status success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="upload-status error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if (empty($pendingImages)): ?>
                    <p>No pending images to review.</p>
                <?php else: ?>
                    <div class="image-grid">
                        <?php foreach ($pendingImages as $image): ?>
                            <div class="image-card">
                                <div class="cat-image-container">
                                    <img src="<?php echo htmlspecialchars($image['url']); ?>" 
                                         alt="Pending upload"
                                         class="cat-image">
                                </div>
                                <div class="image-actions">
                                    <form method="POST" class="action-form">
                                        <input type="hidden" name="key" value="<?php echo htmlspecialchars($image['key']); ?>">
                                        <button type="submit" name="action" value="approve" class="button approve">Approve</button>
                                        <button type="submit" name="action" value="reject" class="button reject">Reject</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 