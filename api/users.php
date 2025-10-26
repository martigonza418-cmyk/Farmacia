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
require_once __DIR__ . '/check_role.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

try {
    if ($method === 'GET') {
        require_role('admin');
        $stmt = $pdo->query('SELECT id, username, rol, created_at FROM usuarios ORDER BY id ASC');
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($method === 'POST') {
        require_role('admin');
        $username = $input['username'] ?? null;
        $password = $input['password'] ?? null;
        $rol = $input['rol'] ?? 'user';
        if (!$username || !$password) { http_response_code(400); echo json_encode(['error'=>'username & password required']); exit; }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO usuarios (username,password,rol) VALUES (?,?,?)');
        $stmt->execute([$username,$hash,$rol]);
        echo json_encode(['ok'=>true,'id'=>$pdo->lastInsertId()]);
        exit;
    }

    if ($method === 'PUT') {
        require_role('admin');
        $id = $input['id'] ?? null;
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'id required']); exit; }
        $sets = [];
        $params = [];
        if (isset($input['username'])) { $sets[]='username=?'; $params[]=$input['username']; }
        if (isset($input['rol'])) { $sets[]='rol=?'; $params[]=$input['rol']; }
        if (isset($input['password'])) { $sets[]='password=?'; $params[]=password_hash($input['password'], PASSWORD_DEFAULT); }
        if (count($sets)===0) { echo json_encode(['mensaje'=>'Nada que actualizar']); exit; }
        $params[] = $id;
        $sql = 'UPDATE usuarios SET ' . implode(',', $sets) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($method === 'DELETE') {
        require_role('admin');
        $id = $input['id'] ?? ($_GET['id'] ?? null);
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'id required']); exit; }
        $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error'=>'Método no permitido']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>$e->getMessage()]);
}
