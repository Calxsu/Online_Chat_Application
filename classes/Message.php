<?php
declare(strict_types=1);

require_once 'Database.php';

class Message extends Database {
    public function __construct() {
        parent::__construct();
    }

    public function get(int $roomId, int $limit = 10): array {
        try {
            $sql = 'SELECT m.id, m.room_id, m.user_id, m.message, m.attachment, m.is_deleted, m.created_at, m.deleted_at,
                           COALESCE(u.username, "Unknown User") as username 
                    FROM messages m 
                    LEFT JOIN users u ON m.user_id = u.id
                    WHERE m.room_id = ?
                    ORDER BY m.id DESC LIMIT ?';
            
            $stmt = $this->query($sql, [$roomId, $limit]);
            $messages = $stmt->fetchAll();
            
            // Process each message to ensure proper data types
            foreach ($messages as &$message) {
                $message['id'] = (int)$message['id'];
                $message['user_id'] = (int)$message['user_id'];
                $message['room_id'] = (int)$message['room_id'];
                $message['is_deleted'] = (int)($message['is_deleted'] ?? 0);
                $message['message'] = $message['message'] ?? '';
                $message['attachment'] = $message['attachment'] ?? null;
                $message['username'] = $message['username'] ?? 'Unknown User';
                
                if (isset($message['created_at']) && $message['created_at']) {
                    $message['created_at'] = date('M d, H:i', strtotime($message['created_at']));
                } else {
                    $message['created_at'] = date('M d, H:i');
                }

                // Add deleted timestamp if available
                if (isset($message['deleted_at']) && $message['deleted_at']) {
                    $message['deleted_at'] = date('M d, H:i', strtotime($message['deleted_at']));
                } else {
                    $message['deleted_at'] = null;
                }
            }
            
            return $messages;
            
        } catch (Exception $e) {
            error_log("Error in Message::get(): " . $e->getMessage());
            return [];
        }
    }

    public function getNew(int $roomId, int $lastId): array {
        try {
            // Get new messages AND recently deleted messages
            $sql = 'SELECT m.id, m.room_id, m.user_id, m.message, m.attachment, m.is_deleted, m.created_at, m.deleted_at,
                           COALESCE(u.username, "Unknown User") as username 
                    FROM messages m 
                    LEFT JOIN users u ON m.user_id = u.id
                    WHERE m.room_id = ? AND (
                        m.id > ? OR 
                        (m.deleted_at IS NOT NULL AND m.deleted_at > NOW() - INTERVAL 10 SECOND)
                    )
                    ORDER BY m.id ASC';
            
            $stmt = $this->query($sql, [$roomId, $lastId]);
            $messages = $stmt->fetchAll();
            
            foreach ($messages as &$message) {
                $message['id'] = (int)$message['id'];
                $message['user_id'] = (int)$message['user_id'];
                $message['room_id'] = (int)$message['room_id'];
                $message['is_deleted'] = (int)($message['is_deleted'] ?? 0);
                $message['message'] = $message['message'] ?? '';
                $message['attachment'] = $message['attachment'] ?? null;
                $message['username'] = $message['username'] ?? 'Unknown User';
                
                if (isset($message['created_at']) && $message['created_at']) {
                    $message['created_at'] = date('M d, H:i', strtotime($message['created_at']));
                } else {
                    $message['created_at'] = date('M d, H:i');
                }

                if (isset($message['deleted_at']) && $message['deleted_at']) {
                    $message['deleted_at'] = date('M d, H:i', strtotime($message['deleted_at']));
                    $message['recently_deleted'] = true; // Flag for real-time update
                } else {
                    $message['deleted_at'] = null;
                    $message['recently_deleted'] = false;
                }
            }
            
            return $messages;
            
        } catch (Exception $e) {
            error_log("Error in Message::getNew(): " . $e->getMessage());
            return [];
        }
    }

    public function send(int $roomId, int $userId, string $message, ?string $attachment = null): array {
        try {
            // FIRST: Check if the room is banned
            $roomStmt = $this->query('SELECT is_banned FROM rooms WHERE id = ?', [$roomId]);
            $room = $roomStmt->fetch();
            
            if (!$room) {
                return ['error' => 'Room not found'];
            }
            
            if ($room['is_banned']) {
                return ['error' => 'This room has been banned. Messages cannot be sent.'];
            }
            
            // SECOND: Check if the user is banned
            $userStmt = $this->query('SELECT is_banned FROM users WHERE id = ?', [$userId]);
            $user = $userStmt->fetch();
            
            if (!$user) {
                return ['error' => 'User not found'];
            }
            
            if ($user['is_banned']) {
                return ['error' => 'Your account has been banned. You cannot send messages.'];
            }
            
            // Validate message content
            if (empty(trim($message)) && empty($attachment)) {
                return ['error' => 'Message or attachment is required'];
            }
            
            // Rate limiting
            if (isset($_SESSION['last_send']) && (time() - $_SESSION['last_send']) < 1) {
                return ['error' => 'Please wait before sending another message'];
            }
            
            // Insert the message
            $sql = 'INSERT INTO messages (room_id, user_id, message, attachment, created_at, is_deleted) 
                    VALUES (?, ?, ?, ?, NOW(), 0)';
            
            $this->query($sql, [$roomId, $userId, trim($message), $attachment]);

            $_SESSION['last_send'] = time();

            $messageId = $this->pdo->lastInsertId();
            error_log("Message inserted with ID: $messageId for room: $roomId, user: $userId");

            // Fetch the inserted message with joined username to return canonical data
            $stmt = $this->query('SELECT m.id, m.room_id, m.user_id, m.message, m.attachment, m.is_deleted, m.created_at, 
                                         COALESCE(u.username, "Unknown User") as username 
                                  FROM messages m 
                                  LEFT JOIN users u ON m.user_id = u.id 
                                  WHERE m.id = ?', [$messageId]);
            $row = $stmt->fetch();
            
            if ($row) {
                $row['id'] = (int)$row['id'];
                $row['user_id'] = (int)$row['user_id'];
                $row['room_id'] = (int)$row['room_id'];
                $row['is_deleted'] = (int)($row['is_deleted'] ?? 0);
                $row['message'] = $row['message'] ?? '';
                $row['attachment'] = $row['attachment'] ?? null;
                $row['username'] = $row['username'] ?? 'Unknown User';
                $row['created_at'] = isset($row['created_at']) ? date('M d, H:i', strtotime($row['created_at'])) : date('M d, H:i');
            }

            return ['success' => true, 'message_id' => $messageId, 'message' => $row];
            
        } catch (Exception $e) {
            error_log("Error in Message::send(): " . $e->getMessage());
            return ['error' => 'Failed to send message: ' . $e->getMessage()];
        }
    }

    public function delete(int $messageId, int $userId): array {
        try {
            error_log("Delete attempt: messageId=$messageId, userId=$userId");
            
            // Get message details with better error checking
            $stmt = $this->query('SELECT m.user_id, m.attachment, u.username, m.is_deleted 
                                 FROM messages m 
                                 LEFT JOIN users u ON m.user_id = u.id 
                                 WHERE m.id = ?', [$messageId]);
            $msg = $stmt->fetch();
            
            if (!$msg) {
                error_log("Message not found: $messageId");
                return ['error' => 'Message not found'];
            }
            
            if ($msg['is_deleted'] == 1) {
                error_log("Message already deleted: $messageId");
                return ['error' => 'Message already deleted'];
            }
            
            // Check if user can delete (owner or admin)
            $userStmt = $this->query('SELECT is_admin FROM users WHERE id = ?', [$userId]);
            $user = $userStmt->fetch();
            $isAdmin = $user && $user['is_admin'];
            
            if ($msg['user_id'] != $userId && !$isAdmin) {
                error_log("Not authorized to delete message $messageId");
                return ['error' => 'Not authorized to delete this message'];
            }
            
            // Soft delete the message with timestamp for real-time tracking
            $this->query('UPDATE messages SET is_deleted = 1, deleted_at = NOW() WHERE id = ?', [$messageId]);
            
            // If there's an attachment, you might want to delete the file too
            if ($msg['attachment']) {
                $filePath = dirname(__DIR__) . '/uploads/' . $msg['attachment'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                    error_log("Deleted attachment file: " . $msg['attachment']);
                }
            }
            
            error_log("Message $messageId soft deleted successfully with timestamp");
            
            return [
                'success' => true, 
                'message_id' => $messageId,
                'username' => $msg['username'] ?? 'Unknown User',
                'user_id' => isset($msg['user_id']) ? (int)$msg['user_id'] : null,
                'deleted_at' => date('M d, H:i')
            ];
            
        } catch (Exception $e) {
            error_log("Error in Message::delete(): " . $e->getMessage());
            return ['error' => 'Failed to delete message: ' . $e->getMessage()];
        }
    }
    
    // Check if a room is banned
    public function isRoomBanned(int $roomId): bool {
        try {
            $stmt = $this->query('SELECT is_banned FROM rooms WHERE id = ?', [$roomId]);
            $room = $stmt->fetch();
            return $room && $room['is_banned'];
        } catch (Exception $e) {
            error_log("Error checking room ban status: " . $e->getMessage());
            return false;
        }
    }
}
?>