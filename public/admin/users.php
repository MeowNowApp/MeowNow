<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/auth.php';

use MeowNow\Utils\Logger;
use MeowNow\Utils\UserManager;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../config');
$dotenv->load();

// Initialize logger and user manager
$logger = new Logger();
$userManager = new UserManager($logger);

// Check if current user is root
if (!$userManager->isRoot($_SESSION['user_id'])) {
    header('Location: /admin/review.php');
    exit;
}

// Handle user management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'create':
                    if (isset($_POST['username']) && isset($_POST['password'])) {
                        $success = $userManager->createUser(
                            $_POST['username'],
                            $_POST['password'],
                            isset($_POST['is_root'])
                        );
                        $message = $success ? 'User created successfully' : 'Failed to create user';
                    }
                    break;
                    
                case 'delete':
                    if (isset($_POST['user_id'])) {
                        $success = $userManager->deleteUser($_POST['user_id']);
                        $message = $success ? 'User deleted successfully' : 'Failed to delete user';
                    }
                    break;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get all users
$users = $userManager->getAllUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - MeowNow Admin</title>
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
                    <li class="nav-item"><a href="/admin/review.php">Review</a></li>
                    <li class="nav-item"><a href="/admin/review.php?logout=1" class="button reject">Logout</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <div class="card">
                <h2>User Management</h2>
                
                <?php if (isset($message)): ?>
                    <div class="upload-status success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="upload-status error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" class="upload-form">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_root" class="checkbox-input">
                            Root User
                        </label>
                    </div>

                    <button type="submit" class="button">Create User</button>
                </form>

                <div class="users-list">
                    <h3>Existing Users</h3>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo $user['is_root'] ? 'Root' : 'Admin'; ?></td>
                                    <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="button reject" onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 