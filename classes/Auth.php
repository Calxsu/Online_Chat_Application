<?php
declare(strict_types=1);

require_once 'Database.php';

class Auth extends Database {
    public function __construct() {
        parent::__construct();
    }

    public function check(): bool {
        return isset($_SESSION['user_id']);
    }

    public function register(string $username, string $email, string $password): array {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($username) < 3 || strlen($password) < 6) {
            return ['error' => 'Invalid input'];
        }
        
        // Check if username or email already exists or is banned
        $stmt = $this->query('SELECT id, is_banned FROM users WHERE username = ? OR email = ?', [$username, $email]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            if ($existingUser['is_banned']) {
                return ['error' => 'This account has been permanently banned'];
            }
            return ['error' => 'Username or email already exists'];
        }
        
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->query('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)', [$username, $email, $hash]);
        return ['success' => true];
    }

    public function login(string $identifier, string $password): array {
        // Check both username and email
        $stmt = $this->query('SELECT * FROM users WHERE username = ? OR email = ?', [$identifier, $identifier]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['error' => 'Invalid credentials'];
        }
        
        // Check if user is banned FIRST
        if ($user['is_banned']) {
            return ['error' => 'This account has been permanently banned. Access denied.'];
        }
        
        // Then verify password
        if (!password_verify($password, $user['password_hash'])) {
            return ['error' => 'Invalid credentials'];
        }
        
        // Login successful
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        
        return ['success' => true];
    }

    public function logout(): void {
        $_SESSION = [];
        session_destroy();
    }
}
?>