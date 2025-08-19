<?php
declare(strict_types=1);

require_once 'Database.php';

class Room extends Database {
    public function __construct() {
        parent::__construct();
    }

    public function getAll(): array {
        return $this->query('SELECT r.*, u.username as creator_name FROM rooms r 
                           LEFT JOIN users u ON r.created_by = u.id 
                           WHERE r.is_banned = 0 AND r.visibility = "public"')->fetchAll();
    }

    public function getByUser(int $userId): array {
        return $this->query('SELECT r.*, u.username as creator_name FROM rooms r 
                           LEFT JOIN users u ON r.created_by = u.id 
                           WHERE r.is_banned = 0 AND (r.visibility = "public" OR r.created_by = ? OR EXISTS(SELECT 1 FROM room_invitations ri WHERE ri.room_id = r.id AND ri.invited_user_id = ?))', 
                           [$userId, $userId])->fetchAll();
    }

    public function getAllForAdmin(): array {
        return $this->query('SELECT r.*, u.username as creator_name FROM rooms r 
                           LEFT JOIN users u ON r.created_by = u.id 
                           ORDER BY r.created_at DESC')->fetchAll();
    }

    public function create(string $name, int $createdBy, string $visibility = 'public'): array {
        if (strlen($name) < 3) {
            return ['error' => 'Invalid name'];
        }
        if (!in_array($visibility, ['public', 'private'], true)) {
            return ['error' => 'Invalid visibility'];
        }
        $this->query('INSERT INTO rooms (name, created_by, visibility) VALUES (?, ?, ?)', [$name, $createdBy, $visibility]);
        return ['success' => true, 'room_id' => $this->pdo->lastInsertId()];
    }

    public function ban(int $roomId): void {
        $this->query('UPDATE rooms SET is_banned = 1 WHERE id = ?', [$roomId]);
    }

    public function isUserInvited(int $roomId, int $userId): bool {
        $stmt = $this->query('SELECT 1 FROM room_invitations WHERE room_id = ? AND invited_user_id = ?', [$roomId, $userId]);
        return (bool)$stmt->fetchColumn();
    }

    public function inviteUser(int $roomId, int $inviterId, int $inviteeId): array {
        $stmt = $this->query('SELECT created_by, visibility FROM rooms WHERE id = ?', [$roomId]);
        $room = $stmt->fetch();
        if (!$room) {
            return ['error' => 'Room not found'];
        }
        if ($room['created_by'] !== $inviterId) {
            return ['error' => 'Only room creator can invite users'];
        }
        if ($room['visibility'] !== 'private') {
            return ['error' => 'Invitations only allowed for private rooms'];
        }
        if ($this->isUserInvited($roomId, $inviteeId)) {
            return ['error' => 'User already invited'];
        }
        $this->query('INSERT INTO room_invitations (room_id, invited_user_id) VALUES (?, ?)', [$roomId, $inviteeId]);
        return ['success' => true];
    }

    public function canUserJoin(int $roomId, int $userId): bool {
        $stmt = $this->query('SELECT visibility, created_by, is_banned FROM rooms WHERE id = ?', [$roomId]);
        $room = $stmt->fetch();
        if (!$room || $room['is_banned']) {
            return false;
        }
        if ($room['visibility'] === 'public') {
            return true;
        }
        if ($room['created_by'] === $userId) {
            return true;
        }
        return $this->isUserInvited($roomId, $userId);
    }

    public function getUserIdByUsername(string $username): ?int {
        $stmt = $this->query('SELECT id FROM users WHERE username = ?', [$username]);
        return $stmt->fetchColumn() ?: null;
    }

    public function getRoomInfo(int $roomId): ?array {
        $stmt = $this->query('SELECT r.*, u.username as creator_name FROM rooms r 
                           LEFT JOIN users u ON r.created_by = u.id WHERE r.id = ?', [$roomId]);
        return $stmt->fetch() ?: null;
    }

    public function isRoomCreator(int $roomId, int $userId): bool {
        $stmt = $this->query('SELECT created_by FROM rooms WHERE id = ?', [$roomId]);
        $room = $stmt->fetch();
        return $room && $room['created_by'] === $userId;
    }
}
