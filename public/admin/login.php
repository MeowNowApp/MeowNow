<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MeowNow\Utils\Logger;
use MeowNow\Utils\UserManager;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../config');
$dotenv->load();

session_start();

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: /admin/review.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $logger = new Logger();
    $userManager = new UserManager($logger);
    
    if ($user = $userManager->authenticate($username, $password)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_root'] = $user['is_root'];
        header('Location: /admin/review.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - MeowNow</title>
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
                </ul>
            </nav>
        </header>

        <main>
            <div class="card">
                <h2>Admin Login</h2>
                
                <?php if ($error): ?>
                    <div class="upload-status error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" class="upload-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required class="form-input">
                    </div>

                    <button type="submit" class="button">Login</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html> 