<?php
declare(strict_types=1);

require_once 'Database.php';
require_once 'User.php';
require_once 'Room.php';
require_once 'Message.php';
require_once 'Report.php';

class Admin extends Database {
    public function __construct() {
        parent::__construct();
    }

    public function processReport(int $reportId, string $action, string $notes = ''): array {
        // Get report details
        $stmt = $this->query('SELECT * FROM reports WHERE id = ?', [$reportId]);
        $report = $stmt->fetch();
        if (!$report) {
            return ['error' => 'Report not found'];
        }

        $entityId = $report['entity_id'];
        $reportType = $report['report_type'];

        switch ($action) {
            case 'ban':
                return $this->banEntity($reportType, $entityId, $reportId, $notes);
            case 'warning':
                return $this->warnEntity($reportType, $entityId, $reportId, $notes);
            case 'reject':
                return $this->rejectReport($reportId, $notes);
            default:
                return ['error' => 'Invalid action'];
        }
    }

    private function banEntity(string $type, int $entityId, int $reportId, string $notes): array {
        try {
            if ($type === 'user') {
                return $this->banUser($entityId, $reportId, $notes);
            } elseif ($type === 'room') {
                return $this->banRoom($entityId, $reportId, $notes);
            } elseif ($type === 'message') {
                return $this->banMessage($entityId, $reportId, $notes);
            }
            return ['error' => 'Invalid entity type'];
        } catch (Exception $e) {
            return ['error' => 'Ban failed: ' . $e->getMessage()];
        }
    }

    private function banUser(int $userId, int $reportId, string $notes): array {
        // Ban the user
        (new User())->ban($userId);
        
        // Get user info
        $stmt = $this->query('SELECT username FROM users WHERE id = ?', [$userId]);
        $user = $stmt->fetch();
        $username = $user['username'] ?? 'Unknown User';
        
        // Close all rooms created by this user and add system message
        $stmt = $this->query('SELECT id, name FROM rooms WHERE created_by = ?', [$userId]);
        $userRooms = $stmt->fetchAll();
        
        foreach ($userRooms as $room) {
            // Ban the room
            $this->query('UPDATE rooms SET is_banned = 1 WHERE id = ?', [$room['id']]);
            
            // Add system message
            $systemMsg = "⚠️ This room has been closed because the creator ($username) was banned.";
            $this->query('INSERT INTO system_messages (room_id, message) VALUES (?, ?)', 
                [$room['id'], $systemMsg]);
        }
        
        // Update report status
        $this->query('UPDATE reports SET status = "approved", action_taken = "ban", admin_notes = ? WHERE id = ?', 
            [$notes, $reportId]);
        
        return ['success' => true, 'message' => "User banned and {count($userRooms)} rooms closed"];
    }

    private function banRoom(int $roomId, int $reportId, string $notes): array {
        // Ban the room
        (new Room())->ban($roomId);
        
        // Update report status
        $this->query('UPDATE reports SET status = "approved", action_taken = "ban", admin_notes = ? WHERE id = ?', 
            [$notes, $reportId]);
        
        return ['success' => true, 'message' => 'Room banned'];
    }

    private function banMessage(int $messageId, int $reportId, string $notes): array {
        // Delete the message (soft delete)
        (new Message())->delete($messageId, 0); // Admin override
        
        // Update report status
        $this->query('UPDATE reports SET status = "approved", action_taken = "ban", admin_notes = ? WHERE id = ?', 
            [$notes, $reportId]);
        
        return ['success' => true, 'message' => 'Message deleted'];
    }

    private function warnEntity(string $type, int $entityId, int $reportId, string $notes): array {
        if ($type === 'user') {
            // Add warning to user
            $this->query('UPDATE users SET warnings = warnings + 1 WHERE id = ?', [$entityId]);
            $action = 'warning';
            $message = 'User warned';
        } else {
            return ['error' => 'Can only warn users'];
        }
        
        // Update report status
        $this->query('UPDATE reports SET status = "approved", action_taken = ?, admin_notes = ? WHERE id = ?', 
            [$action, $notes, $reportId]);
        
        return ['success' => true, 'message' => $message];
    }

    private function rejectReport(int $reportId, string $notes): array {
        $this->query('UPDATE reports SET status = "rejected", action_taken = "rejected", admin_notes = ? WHERE id = ?', 
            [$notes, $reportId]);
        
        return ['success' => true, 'message' => 'Report rejected'];
    }

    public function getReportsWithDetails(): array {
        $sql = "SELECT r.*, 
                CASE 
                    WHEN r.report_type = 'user' THEN u.username
                    WHEN r.report_type = 'room' THEN rm.name
                    WHEN r.report_type = 'message' THEN CONCAT(mu.username, ': ', SUBSTRING(m.message, 1, 50))
                END as entity_name,
                ru.username as reporter_name
                FROM reports r
                LEFT JOIN users u ON r.report_type = 'user' AND r.entity_id = u.id
                LEFT JOIN rooms rm ON r.report_type = 'room' AND r.entity_id = rm.id
                LEFT JOIN messages m ON r.report_type = 'message' AND r.entity_id = m.id
                LEFT JOIN users mu ON m.user_id = mu.id
                LEFT JOIN users ru ON r.reported_by = ru.id
                WHERE r.status = 'pending'
                ORDER BY r.created_at DESC";
        
        return $this->query($sql)->fetchAll();
    }
}
