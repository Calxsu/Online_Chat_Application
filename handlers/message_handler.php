<?php
declare(strict_types=1);

session_start();
require_once '../classes/Message.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('log_errors', 1);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized - Please login']);
    exit;
}

$message = new Message();
$response = [];

error_log("Message handler called with action: " . ($_GET['action'] ?? $_POST['action'] ?? 'none'));
error_log("User ID from session: " . $_SESSION['user_id']);

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'get' && isset($_GET['room_id'])) {
        try {
            $roomId = intval($_GET['room_id']);
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            
            error_log("Getting messages for room: $roomId, limit: $limit");
            
            $messages = $message->get($roomId, $limit);
            
            error_log("Retrieved " . count($messages) . " messages for user " . $_SESSION['user_id']);
            
            // Add debug info to each message
            foreach ($messages as &$msg) {
                error_log("Message ID {$msg['id']}: user_id={$msg['user_id']}, current_user=" . $_SESSION['user_id'] . ", isOwn=" . ($msg['user_id'] == $_SESSION['user_id'] ? 'true' : 'false'));
            }
            
            echo json_encode($messages);
            exit;
            
        } catch (Exception $e) {
            error_log("Exception in get messages: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to load messages: ' . $e->getMessage()]);
            exit;
        }
    }

    if ($_GET['action'] === 'poll' && isset($_GET['room_id'], $_GET['last_id'])) {
        $roomId = intval($_GET['room_id']);
        $lastId = intval($_GET['last_id']);
        
        $start = time();
        while (time() - $start < 2) {
            $new = $message->getNew($roomId, $lastId);
            if (!empty($new)) {
                echo json_encode($new);
                exit;
            }
            usleep(500000);
        }
        echo json_encode([]);
        exit;
    }
}

if (isset($_POST['action'])) {
    if ($_POST['action'] === 'send' && isset($_POST['room_id'])) {
        try {
            $roomId = intval($_POST['room_id']);
            $messageText = isset($_POST['message']) ? trim($_POST['message']) : '';
            $attachment = null;

            error_log("Sending message to room: $roomId, text: '$messageText', user: " . $_SESSION['user_id']);

            // Handle file upload
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['attachment'];
                
                if ($file['size'] > 5 * 1024 * 1024) {
                    throw new Exception('File too large (max 5MB)');
                }
                
                $uploadDir = dirname(__DIR__) . '/uploads/';
                if (!file_exists($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        throw new Exception('Cannot create uploads directory');
                    }
                }
                
                $originalName = basename($file['name']);
                $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $mimeType = $file['type'];
                
                $allowedTypes = [
                    'image/jpeg' => ['jpg', 'jpeg'],
                    'image/png' => ['png'],
                    'image/gif' => ['gif'],
                    'application/pdf' => ['pdf']
                ];
                
                $isValidType = false;
                foreach ($allowedTypes as $mime => $extensions) {
                    if ($mimeType === $mime && in_array($fileExtension, $extensions)) {
                        $isValidType = true;
                        break;
                    }
                }
                
                if (!$isValidType) {
                    throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and PDF files are allowed.');
                }
                
                $uniqueFileName = uniqid('file_', true) . '.' . $fileExtension;
                $uploadPath = $uploadDir . $uniqueFileName;
                
                if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    throw new Exception('Failed to save uploaded file');
                }
                
                $attachment = $uniqueFileName;
                error_log("File saved as: $uniqueFileName");
            }
            
            if (empty($messageText) && empty($attachment)) {
                throw new Exception('Message or file attachment is required');
            }
            
            $result = $message->send($roomId, $_SESSION['user_id'], $messageText, $attachment);
            echo json_encode($result);
            exit;
            
        } catch (Exception $e) {
            error_log("Exception in send message: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
    
    if ($_POST['action'] === 'delete' && isset($_POST['message_id'])) {
        try {
            $messageId = intval($_POST['message_id']);
            $userId = intval($_SESSION['user_id']);
            
            error_log("Attempting to delete message $messageId by user $userId");
            
            $result = $message->delete($messageId, $userId);
            
            error_log("Delete result: " . json_encode($result));
            
            echo json_encode($result);
            exit;
            
        } catch (Exception $e) {
            error_log("Exception in delete message: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to delete message: ' . $e->getMessage()]);
            exit;
        }
    }
}

echo json_encode(['error' => 'Invalid request']);
?>
