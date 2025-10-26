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

require_once __DIR__ . '/db.php';
header('Content-Type: text/plain; charset=utf-8');

try {
    $stmt = $pdo->query("SELECT id, password FROM usuarios");
    $updated = 0;
    while ($row = $stmt->fetch()) {
        $id = $row['id'];
        $pw = $row['password'];
        if (strlen($pw) < 60 || (!str_starts_with($pw, '$2y$') && !str_starts_with($pw,'$2a$'))) {
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $u = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $u->execute([$hash, $id]);
            $updated++;
        }
    }
    echo "Migración completada. Registros actualizados: $updated\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
