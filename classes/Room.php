<?php
declare(strict_types=1);

require_once 'Database.php';

class Room extends Database {
    public function __construct() {
        parent::__construct();
    }

    public function getAll(): array {
        // Show all public rooms (including banned ones with their ban status)
        return $this->query('SELECT r.*, u.username as creator_name 
                           FROM rooms r 
                           LEFT JOIN users u ON r.created_by = u.id 
                           WHERE r.visibility = "public"
                           ORDER BY r.is_banned ASC, r.created_at DESC')->fetchAll();
    }

    public function getByUser(int $userId): array {
        // Show all rooms accessible to user (including banned ones with their ban status)
        return $this->query('SELECT r.*, u.username as creator_name 
                           FROM rooms r 
                           LEFT JOIN users u ON r.created_by = u.id 
                           WHERE r.visibility = "public" 
                              OR r.created_by = ? 
                              OR EXISTS(SELECT 1 FROM room_invitations ri WHERE ri.room_id = r.id AND ri.invited_user_id = ?)
                           ORDER BY r.is_banned ASC, r.created_at DESC', 
                           [$userId, $userId])->fetchAll();
    }

    public function getAllForAdmin(): array {
        return $this->query('SELECT r.*, u.username as creator_name 
                           FROM rooms r 
                           LEFT JOIN users u ON r.created_by = u.id 
                           ORDER BY r.is_banned DESC, r.created_at DESC')->fetchAll();
    }

    public function create(string $name, int $createdBy, string $visibility = 'public'): array {
        // Check if user is banned
        $userStmt = $this->query('SELECT is_banned FROM users WHERE id = ?', [$createdBy]);
        $user = $userStmt->fetch();
        
        if ($user && $user['is_banned']) {
            return ['error' => 'Banned users cannot create rooms'];
        }
        
        if (strlen($name) < 3) {
            return ['error' => 'Room name must be at least 3 characters'];
        }
        
        if (!in_array($visibility, ['public', 'private'], true)) {
            return ['error' => 'Invalid visibility setting'];
        }
        
        $this->query('INSERT INTO rooms (name, created_by, visibility, is_banned) VALUES (?, ?, ?, 0)', 
                    [$name, $createdBy, $visibility]);
        
        return ['success' => true, 'room_id' => $this->pdo->lastInsertId()];
    }

    public function ban(int $roomId): void {
        $this->query('UPDATE rooms SET is_banned = 1 WHERE id = ?', [$roomId]);
        
        // Add a system message to the room about the ban
        $this->query('INSERT INTO messages (room_id, user_id, message, is_deleted, created_at) 
                     VALUES (?, 0, "This room has been banned by an administrator. No new messages can be sent.", 0, NOW())', 
                     [$roomId]);
    }
    
    public function unban(int $roomId): void {
        $this->query('UPDATE rooms SET is_banned = 0 WHERE id = ?', [$roomId]);
        
        // Add a system message about the unban
        $this->query('INSERT INTO messages (room_id, user_id, message, is_deleted, created_at) 
                     VALUES (?, 0, "This room has been unbanned. You can now send messages again.", 0, NOW())', 
                     [$roomId]);
    }

    public function isUserInvited(int $roomId, int $userId): bool {
        $stmt = $this->query('SELECT 1 FROM room_invitations WHERE room_id = ? AND invited_user_id = ?', [$roomId, $userId]);
        return (bool)$stmt->fetchColumn();
    }

    public function inviteUser(int $roomId, int $inviterId, int $inviteeId): array {
        $stmt = $this->query('SELECT created_by, visibility, is_banned FROM rooms WHERE id = ?', [$roomId]);
        $room = $stmt->fetch();
        
        if (!$room) {
            return ['error' => 'Room not found'];
        }
        
        if ($room['is_banned']) {
            return ['error' => 'Cannot invite users to a banned room'];
        }
        
        if ($room['created_by'] !== $inviterId) {
            return ['error' => 'Only room creator can invite users'];
        }
        
        if ($room['visibility'] !== 'private') {
            return ['error' => 'Invitations only allowed for private rooms'];
        }
        
        // Check if invitee is banned
        $userStmt = $this->query('SELECT is_banned FROM users WHERE id = ?', [$inviteeId]);
        $inviteeUser = $userStmt->fetch();
        
        if ($inviteeUser && $inviteeUser['is_banned']) {
            return ['error' => 'Cannot invite banned users'];
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
        
        if (!$room) {
            return false;
        }
        
        // Users can view banned rooms but not interact with them
        // So we allow "joining" to see the room, but Message::send will block sending
        
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
        $result = $stmt->fetchColumn();
        return $result !== false ? (int)$result : null;
    }

    public function getRoomInfo(int $roomId): ?array {
        $stmt = $this->query('SELECT r.*, u.username as creator_name 
                             FROM rooms r 
                             LEFT JOIN users u ON r.created_by = u.id 
                             WHERE r.id = ?', [$roomId]);
        return $stmt->fetch() ?: null;
    }

    public function isRoomCreator(int $roomId, int $userId): bool {
        $stmt = $this->query('SELECT created_by FROM rooms WHERE id = ?', [$roomId]);
        $room = $stmt->fetch();
        return $room && $room['created_by'] === $userId;
    }
    
    public function isRoomBanned(int $roomId): bool {
        $stmt = $this->query('SELECT is_banned FROM rooms WHERE id = ?', [$roomId]);
        $room = $stmt->fetch();
        return $room && $room['is_banned'] == 1;
    }
}
?>