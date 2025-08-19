<?php
declare(strict_types=1);

require_once 'Database.php';

class Auth extends Database {
    public function __construct() {
        parent::__construct();  // Explicitly call parent's constructor to fix argument error
    }

    public function check(): bool {
        return isset($_SESSION['user_id']);
    }

    public function register(string $username, string $email, string $password): array {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($username) < 3 || strlen($password) < 6) {
            return ['error' => 'Invalid input'];
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->query('SELECT id FROM users WHERE username = ? OR email = ?', [$username, $email]);
        if ($stmt->fetch()) {
            return ['error' => 'User exists'];
        }
        $this->query('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)', [$username, $email, $hash]);
        return ['success' => true];
    }

    public function login(string $identifier, string $password): array {
        $stmt = $this->query('SELECT * FROM users WHERE username = ? OR email = ?', [$identifier, $identifier]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash']) && !$user['is_banned']) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return ['success' => true];
        }
        return ['error' => 'Invalid credentials or banned'];
    }

    public function logout(): void {
        $_SESSION = [];
        session_destroy();
    }
}
