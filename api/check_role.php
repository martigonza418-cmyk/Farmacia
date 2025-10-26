<?php

session_start();

function require_role($role_needed) {
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado']);
        exit;
    }

    $rol = $_SESSION['user']['rol'] ?? ($_SESSION['user']['role'] ?? 'user');

    $map = [
        'admin' => 3,
        'cashier' => 2,
        'user' => 1
    ];

    $have = $map[$rol] ?? 1;
    $need = $map[$role_needed] ?? 1;

    if ($have < $need) {
        http_response_code(403);
        echo json_encode(['error' => 'Permiso denegado']);
        exit;
    }
}
