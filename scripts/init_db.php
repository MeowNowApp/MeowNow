<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Create database directory if it doesn't exist
$dbDir = __DIR__ . '/../data';
if (!file_exists($dbDir)) {
    mkdir($dbDir, 0755, true);
}

// Initialize SQLite database
$db = new SQLite3($dbDir . '/users.db');

// Create users table
$db->exec('
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        is_root BOOLEAN DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
');

// Create root user if it doesn't exist
$rootUsername = getenv('ROOT_USERNAME') ?: 'root';
$rootPassword = getenv('ROOT_PASSWORD') ?: 'root123'; // Default password, should be changed
$hashedPassword = password_hash($rootPassword, PASSWORD_DEFAULT);

$stmt = $db->prepare('INSERT OR IGNORE INTO users (username, password, is_root) VALUES (:username, :password, 1)');
$stmt->bindValue(':username', $rootUsername, SQLITE3_TEXT);
$stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
$stmt->execute();

echo "Database initialized successfully!\n";
echo "Root user created with username: $rootUsername\n";
echo "Please change the root password in production!\n"; 