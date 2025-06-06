<?php
namespace MeowNow\Utils;

class UserManager {
    private $db;
    private $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
        $dbPath = __DIR__ . '/../../data/users.db';
        if (!file_exists(dirname($dbPath))) {
            mkdir(dirname($dbPath), 0755, true);
        }
        $this->db = new \SQLite3($dbPath);
        
        // Create users table if it doesn't exist
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                is_root BOOLEAN DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // Ensure root user exists
        $this->ensureRootUser();
    }

    private function ensureRootUser() {
        $rootUsername = getenv('ROOT_USERNAME') ?: 'root';
        $rootPassword = getenv('ROOT_PASSWORD') ?: 'root123';
        
        // Check if root user exists
        $stmt = $this->db->prepare('SELECT id FROM users WHERE username = :username');
        $stmt->bindValue(':username', $rootUsername, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        if (!$result->fetchArray()) {
            // Create root user if it doesn't exist
            $this->createUser($rootUsername, $rootPassword, true);
        }
    }

    public function authenticate($username, $password) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        if ($user = $result->fetchArray(SQLITE3_ASSOC)) {
            if (password_verify($password, $user['password'])) {
                return [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'is_root' => (bool)$user['is_root']
                ];
            }
        }
        return false;
    }

    public function createUser($username, $password, $isRoot = false) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare('INSERT INTO users (username, password, is_root) VALUES (:username, :password, :is_root)');
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
            $stmt->bindValue(':is_root', $isRoot ? 1 : 0, SQLITE3_INTEGER);
            return $stmt->execute();
        } catch (\Exception $e) {
            $this->logger->error("Failed to create user: " . $e->getMessage());
            return false;
        }
    }

    public function deleteUser($userId) {
        try {
            $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');
            $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
            return $stmt->execute();
        } catch (\Exception $e) {
            $this->logger->error("Failed to delete user: " . $e->getMessage());
            return false;
        }
    }

    public function getAllUsers() {
        $result = $this->db->query('SELECT id, username, is_root, created_at FROM users ORDER BY created_at DESC');
        $users = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $users[] = [
                'id' => $row['id'],
                'username' => $row['username'],
                'is_root' => (bool)$row['is_root'],
                'created_at' => $row['created_at']
            ];
        }
        return $users;
    }

    public function isRoot($userId) {
        // Validate user ID is a positive integer
        if (!is_numeric($userId) || $userId <= 0) {
            return false;
        }
        
        $stmt = $this->db->prepare('SELECT is_root FROM users WHERE id = :id');
        $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            return (bool)$row['is_root'];
        }
        return false;
    }
} 