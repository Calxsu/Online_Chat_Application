<?php
session_start();
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();

// Determine if AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Helper to output JSON
function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Process POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $result = $auth->login($username, $password);
        if ($isAjax) {
            return jsonResponse($result);
        }
        if (!empty($result['success'])) {
            header('Location: ../index.php');
        } else {
            header('Location: ../index.php?error=' . urlencode($result['error']));
        }
        exit;
    } elseif ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $result = $auth->register($username, $email, $password);
        if ($isAjax) {
            return jsonResponse($result);
        }
        if (!empty($result['success'])) {
            header('Location: ../index.php?registered=1');
        } else {
            header('Location: ../index.php?error=' . urlencode($result['error']));
        }
        exit;
    } elseif ($action === 'logout') {
        $auth->logout();
        if ($isAjax) {
            return jsonResponse(['success' => true]);
        }
        header('Location: ../index.php');
        exit;
    } else {
        if ($isAjax) {
            return jsonResponse(['error' => 'Invalid action']);
        }
        header('Location: ../index.php?error=' . urlencode('Invalid request'));
        exit;
    }
}

// If reached directly, redirect back
if (!$isAjax) {
    header('Location: ../index.php');
    exit;
}
?>
