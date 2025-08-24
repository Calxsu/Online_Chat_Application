<?php
declare(strict_types=1);

session_start();
require_once '../classes/Admin.php';
require_once '../classes/User.php';
require_once '../classes/Room.php';

header('Content-Type: application/json');

$user = new User();
if (!isset($_SESSION['user_id']) || !$user->isAdmin((int)$_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$admin = new Admin();
$userClass = new User();
$roomClass = new Room();
$response = [];

// Handle GET requests
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'get_reports':
            $response = $admin->getReportsWithDetails();
            break;
        case 'get_users':
            $response = $userClass->getAll();
            break;
        case 'get_rooms':
            $response = $roomClass->getAllForAdmin();
            break;
        default:
            $response = ['error' => 'Invalid action'];
    }
    echo json_encode($response);
    exit;
}

// Handle POST requests
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'process_report':
            if (isset($_POST['report_id'], $_POST['decision'])) {
                $reportId = (int)$_POST['report_id'];
                $decision = $_POST['decision'];
                $notes = $_POST['notes'] ?? '';
                $response = $admin->processReport($reportId, $decision, $notes);
            } else {
                $response = ['error' => 'Missing required parameters'];
            }
            break;
            
        case 'ban_user':
            if (isset($_POST['user_id'])) {
                $userId = (int)$_POST['user_id'];
                $response = $admin->directBanUser($userId);
            } else {
                $response = ['error' => 'User ID required'];
            }
            break;
            
        case 'ban_room':
            if (isset($_POST['room_id'])) {
                $roomId = (int)$_POST['room_id'];
                $response = $admin->directBanRoom($roomId);
            } else {
                $response = ['error' => 'Room ID required'];
            }
            break;
            
        default:
            $response = ['error' => 'Invalid action'];
    }
}

echo json_encode($response);
?>