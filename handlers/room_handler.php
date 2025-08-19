<?php
declare(strict_types=1);

session_start();
require_once '../classes/Room.php';
require_once '../classes/User.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$room = new Room();
$user = new User();
$response = [];

// GET requests
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'get_room_info' && isset($_GET['room_id'])) {
        $roomInfo = $room->getRoomInfo((int)$_GET['room_id']);
        if ($roomInfo) {
            $roomInfo['is_creator'] = $room->isRoomCreator((int)$_GET['room_id'], (int)$_SESSION['user_id']);
            $response = $roomInfo;
        } else {
            $response = ['error' => 'Room not found'];
        }
    } elseif ($_GET['action'] === 'get_rooms') {
        // Get all rooms available to user
        $response = $room->getByUser((int)$_SESSION['user_id']);
    }
    echo json_encode($response);
    exit;
}

// POST requests
$data = $_POST;
if (isset($data['action'])) {
    if ($data['action'] === 'create' && isset($data['name'], $data['visibility'])) {
        $response = $room->create($data['name'], (int)$_SESSION['user_id'], $data['visibility']);
    } elseif ($data['action'] === 'invite' && isset($data['room_id'], $data['username'])) {
        if (!$room->isRoomCreator((int)$data['room_id'], (int)$_SESSION['user_id'])) {
            $response = ['error' => 'Only room creator can invite users'];
        } else {
            $inviteeId = $room->getUserIdByUsername($data['username']);
            if (!$inviteeId) {
                $response = ['error' => 'User not found'];
            } else {
                $response = $room->inviteUser((int)$data['room_id'], (int)$_SESSION['user_id'], $inviteeId);
                if ($response['success']) {
                    $response['invited_user_id'] = $inviteeId;
                }
            }
        }
    } elseif ($data['action'] === 'can_join' && isset($data['room_id'])) {
        $canJoin = $room->canUserJoin((int)$data['room_id'], (int)$_SESSION['user_id']);
        $response = ['can_join' => $canJoin];
    } elseif ($data['action'] === 'leave' && isset($data['room_id'])) {
        // Optional server-side leave tracking
        // For now, we'll just return success since we're handling it client-side
        $response = ['success' => true, 'message' => 'Left room successfully'];
    }
}

echo json_encode($response);
