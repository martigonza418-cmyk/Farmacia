<?php
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
    exit;
}
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
session_start();

header('Content-Type: application/json');

if (isset($_SESSION['user'])) {
    echo json_encode([
        'ok' => true,
        'user' => $_SESSION['user']
    ]);
} else {
    echo json_encode(['ok' => false]);
}