<?php
session_start();
require_once '../classes/Auth.php';
header('Content-Type: application/json');

$auth = new Auth();
$data = $_POST;
$response = [];

if (isset($data['action'])) {
    if ($data['action'] === 'login' && isset($data['username'], $data['password'])) {
        $response = $auth->login($data['username'], $data['password']);
    } elseif ($data['action'] === 'register' && isset($data['username'], $data['email'], $data['password'])) {
        $response = $auth->register($data['username'], $data['email'], $data['password']);
    } elseif ($data['action'] === 'logout') {
        $auth->logout();
        $response = ['success' => true];
    }
}

echo json_encode($response);
