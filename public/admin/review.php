<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/auth.php';

use MeowNow\Utils\Logger;
use MeowNow\Utils\UploadHandler;
use MeowNow\Utils\UserManager;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../config');
$dotenv->load();

// Initialize logger and handlers
$logger = new Logger();
$uploadHandler = new UploadHandler($logger);
$userManager = new UserManager($logger);

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /admin/login.php');
    exit;
}

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

// Check if current user is root
$isRoot = $userManager->isRoot($_SESSION['user_id']);
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
                    <?php if ($isRoot): ?>
                        <li class="nav-item"><a href="/admin/users.php" class="button">Manage Users</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a href="/admin/review.php?logout=1" class="button reject">Logout</a></li>
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