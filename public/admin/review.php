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
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .image-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .image-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .image-actions {
            padding: 1rem;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        .button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        .button-approve {
            background: #4CAF50;
            color: white;
        }
        .button-reject {
            background: #f44336;
            color: white;
        }
        .message {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .message-success {
            background: #dff0d8;
            color: #3c763d;
        }
        .message-error {
            background: #f2dede;
            color: #a94442;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>Review Uploads</h1>
        
        <?php if (isset($message)): ?>
            <div class="message message-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="message message-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (empty($pendingImages)): ?>
            <p>No pending images to review.</p>
        <?php else: ?>
            <div class="image-grid">
                <?php foreach ($pendingImages as $image): ?>
                    <div class="image-card">
                        <img src="<?php echo htmlspecialchars($image['url']); ?>" alt="Pending upload">
                        <div class="image-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="key" value="<?php echo htmlspecialchars($image['key']); ?>">
                                <button type="submit" name="action" value="approve" class="button button-approve">Approve</button>
                                <button type="submit" name="action" value="reject" class="button button-reject">Reject</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 