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
    }
    echo json_encode($response);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'process_report' && isset($_POST['report_id'], $_POST['decision'])) {
    $reportId = (int)$_POST['report_id'];
    $decision = $_POST['decision'];
    $notes = $_POST['notes'] ?? '';
    
    $response = $admin->processReport($reportId, $decision, $notes);
}

echo json_encode($response);
