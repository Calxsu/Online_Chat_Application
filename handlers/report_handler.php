<?php
declare(strict_types=1);

session_start();
require_once '../classes/Report.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$report = new Report();
$data = $_POST;
$response = [];

if (isset($data['action']) && $data['action'] === 'create' && isset($data['type'], $data['entity_id'], $data['reason'])) {
    $reason = htmlspecialchars($data['reason'], ENT_QUOTES, 'UTF-8');
    $response = $report->create($data['type'], (int)$data['entity_id'], (int)$_SESSION['user_id'], $reason);
}

echo json_encode($response);
