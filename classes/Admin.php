<?php
declare(strict_types=1);

require_once 'Database.php';
require_once 'User.php';
require_once 'Room.php';
require_once 'Message.php';
require_once 'Report.php';

class Admin extends Database {
    
    public function processReport(int $reportId, string $action, string $notes = ''): array {
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
        // Ban the user - they can never login again with same credentials
        $this->query('UPDATE users SET is_banned = 1 WHERE id = ?', [$userId]);
        
        // Get user info
        $stmt = $this->query('SELECT username, email FROM users WHERE id = ?', [$userId]);
        $user = $stmt->fetch();
        $username = $user['username'] ?? 'Unknown User';
        
        // Ban all rooms created by this user
        $stmt = $this->query('SELECT id, name FROM rooms WHERE created_by = ?', [$userId]);
        $userRooms = $stmt->fetchAll();
        
        foreach ($userRooms as $room) {
            // Ban the room
            $this->query('UPDATE rooms SET is_banned = 1 WHERE id = ?', [$room['id']]);
            
            // Add system message about the ban
            $systemMsg = "⚠️ This room has been permanently banned because the creator ({$username}) was banned for violating community guidelines.";
            $this->query('INSERT INTO messages (room_id, user_id, message, is_deleted, created_at) VALUES (?, 0, ?, 0, NOW())', 
                [$room['id'], $systemMsg]);
        }
        
        // Delete all messages from this user (soft delete)
        $this->query('UPDATE messages SET is_deleted = 1, deleted_at = NOW() WHERE user_id = ?', [$userId]);
        
        // Log the ban
        error_log("User banned: ID=$userId, Username=$username, Email={$user['email']}, Rooms banned: " . count($userRooms));
        
        // Update report status
        $this->query('UPDATE reports SET status = "approved", action_taken = "ban", admin_notes = ? WHERE id = ?', 
            [$notes, $reportId]);
            
        return [
            'success' => true, 
            'message' => "User '{$username}' permanently banned. " . count($userRooms) . " rooms closed and all messages deleted."
        ];
    }

    private function banRoom(int $roomId, int $reportId, string $notes): array {
        // Get room info
        $stmt = $this->query('SELECT name FROM rooms WHERE id = ?', [$roomId]);
        $room = $stmt->fetch();
        $roomName = $room['name'] ?? 'Unknown Room';
        
        // Ban the room
        $this->query('UPDATE rooms SET is_banned = 1 WHERE id = ?', [$roomId]);
        
        // Add a final system message to the room
        $systemMsg = "⚠️ This room has been permanently banned by an administrator for violating community guidelines. No new messages can be sent.";
        $this->query('INSERT INTO messages (room_id, user_id, message, is_deleted, created_at) VALUES (?, 0, ?, 0, NOW())', 
            [$roomId, $systemMsg]);
        
        // Update report status
        $this->query('UPDATE reports SET status = "approved", action_taken = "ban", admin_notes = ? WHERE id = ?', 
            [$notes, $reportId]);
            
        return ['success' => true, 'message' => "Room '{$roomName}' has been permanently banned"];
    }

    private function banMessage(int $messageId, int $reportId, string $notes): array {
        // When a message is banned, it gets deleted/unsent
        // Get message details first
        $stmt = $this->query('SELECT m.*, u.username FROM messages m 
                             LEFT JOIN users u ON m.user_id = u.id 
                             WHERE m.id = ?', [$messageId]);
        $message = $stmt->fetch();
        
        if (!$message) {
            return ['error' => 'Message not found'];
        }
        
        // Delete the message (soft delete with timestamp)
        $this->query('UPDATE messages SET is_deleted = 1, deleted_at = NOW() WHERE id = ?', [$messageId]);
        
        // If there's an attachment, delete the file
        if ($message['attachment']) {
            $filePath = dirname(__DIR__) . '/uploads/' . $message['attachment'];
            if (file_exists($filePath)) {
                unlink($filePath);
                error_log("Deleted banned message attachment: " . $message['attachment']);
            }
        }
        
        // Consider warning the user who sent the message
        $this->query('UPDATE users SET warnings = warnings + 1 WHERE id = ?', [$message['user_id']]);
        
        // Check if user should be auto-banned after too many warnings
        $stmt = $this->query('SELECT warnings FROM users WHERE id = ?', [$message['user_id']]);
        $user = $stmt->fetch();
        if ($user && $user['warnings'] >= 3) {
            // Auto-ban user after 3 warnings
            $this->query('UPDATE users SET is_banned = 1 WHERE id = ?', [$message['user_id']]);
            error_log("User auto-banned after 3 warnings: ID=" . $message['user_id']);
        }
        
        // Update report status
        $this->query('UPDATE reports SET status = "approved", action_taken = "ban", admin_notes = ? WHERE id = ?', 
            [$notes, $reportId]);
            
        return [
            'success' => true, 
            'message' => 'Message deleted and user warned. User: ' . ($message['username'] ?? 'Unknown') . 
                        ' now has ' . ($user['warnings'] ?? 0) . ' warning(s)'
        ];
    }

    private function warnEntity(string $type, int $entityId, int $reportId, string $notes): array {
        if ($type === 'user') {
            // Add warning to user
            $this->query('UPDATE users SET warnings = warnings + 1 WHERE id = ?', [$entityId]);
            
            // Get updated warning count
            $stmt = $this->query('SELECT username, warnings FROM users WHERE id = ?', [$entityId]);
            $user = $stmt->fetch();
            
            $message = "User '{$user['username']}' warned (Total warnings: {$user['warnings']})";
            
            // Auto-ban if 3 or more warnings
            if ($user['warnings'] >= 3) {
                $this->query('UPDATE users SET is_banned = 1 WHERE id = ?', [$entityId]);
                $message .= " - User auto-banned after 3 warnings!";
            }
            
            $action = 'warning';
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

    // Get reports with full details including message content and room context
    public function getReportsWithDetails(): array {
        $sql = "SELECT r.*,
                CASE 
                    WHEN r.report_type = 'user' THEN u.username
                    WHEN r.report_type = 'room' THEN rm.name
                    WHEN r.report_type = 'message' THEN CONCAT(mu.username, ' in \"', room.name, '\"')
                END as entity_name,
                CASE 
                    WHEN r.report_type = 'message' THEN m.message
                    ELSE NULL
                END as message_content,
                CASE 
                    WHEN r.report_type = 'message' THEN m.created_at
                    ELSE NULL
                END as message_date,
                ru.username as reporter_name,
                room.name as room_name,
                CASE 
                    WHEN r.report_type = 'user' THEN u.is_banned
                    WHEN r.report_type = 'room' THEN rm.is_banned
                    WHEN r.report_type = 'message' THEN m.is_deleted
                    ELSE 0
                END as is_already_actioned
                FROM reports r
                LEFT JOIN users u ON r.report_type = 'user' AND r.entity_id = u.id
                LEFT JOIN rooms rm ON r.report_type = 'room' AND r.entity_id = rm.id
                LEFT JOIN messages m ON r.report_type = 'message' AND r.entity_id = m.id
                LEFT JOIN users mu ON m.user_id = mu.id
                LEFT JOIN rooms room ON m.room_id = room.id
                LEFT JOIN users ru ON r.reported_by = ru.id
                WHERE r.status = 'pending'
                ORDER BY r.created_at DESC";
                
        return $this->query($sql)->fetchAll();
    }
    
    // Direct ban functions (for admin panel)
    public function directBanUser(int $userId): array {
        // Check if user exists and not already banned
        $stmt = $this->query('SELECT username, is_banned FROM users WHERE id = ?', [$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['error' => 'User not found'];
        }
        
        if ($user['is_banned']) {
            return ['error' => 'User is already banned'];
        }
        
        // Ban the user
        $this->query('UPDATE users SET is_banned = 1 WHERE id = ?', [$userId]);
        
        // Ban all their rooms
        $this->query('UPDATE rooms SET is_banned = 1 WHERE created_by = ?', [$userId]);
        
        // Delete all their messages
        $this->query('UPDATE messages SET is_deleted = 1, deleted_at = NOW() WHERE user_id = ?', [$userId]);
        
        return ['success' => true, 'message' => "User '{$user['username']}' has been banned"];
    }
    
    public function directBanRoom(int $roomId): array {
        // Check if room exists and not already banned
        $stmt = $this->query('SELECT name, is_banned FROM rooms WHERE id = ?', [$roomId]);
        $room = $stmt->fetch();
        
        if (!$room) {
            return ['error' => 'Room not found'];
        }
        
        if ($room['is_banned']) {
            return ['error' => 'Room is already banned'];
        }
        
        // Ban the room
        $this->query('UPDATE rooms SET is_banned = 1 WHERE id = ?', [$roomId]);
        
        // Add system message
        $systemMsg = "⚠️ This room has been banned by an administrator.";
        $this->query('INSERT INTO messages (room_id, user_id, message, is_deleted, created_at) VALUES (?, 0, ?, 0, NOW())', 
            [$roomId, $systemMsg]);
        
        return ['success' => true, 'message' => "Room '{$room['name']}' has been banned"];
    }
}
?>