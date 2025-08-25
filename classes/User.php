<?php
declare(strict_types=1);

require_once 'Database.php';

class User extends Database {
    public function __construct() {
        parent::__construct();
    }

    public function isAdmin(int $userId): bool {
        $stmt = $this->query('SELECT is_admin FROM users WHERE id = ?', [$userId]);
        $user = $stmt->fetch();
        return (bool)($user['is_admin'] ?? false);
    }

    public function updateLastSeen(int $userId): void {
        $this->query('UPDATE users SET last_seen = CURRENT_TIMESTAMP WHERE id = ?', [$userId]);
    }

    public function ban(int $userId): void {
        $this->query('UPDATE users SET is_banned = 1 WHERE id = ?', [$userId]);
    }

    public function getAll(): array {
        return $this->query('SELECT id, username, email, warnings, is_banned, created_at FROM users WHERE id != 0 ORDER BY username')->fetchAll();
    }

    public function getUserInfo(int $userId): ?array {
        $stmt = $this->query('SELECT id, username, warnings, is_banned FROM users WHERE id = ?', [$userId]);
        return $stmt->fetch() ?: null;
    }
}
