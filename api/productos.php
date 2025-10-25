<?php
header("Access-Control-Allow-Origin: https://pos-c7b3etcee5bnczbm.westus-01.azurewebsites.net");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Responder a preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/check_role.php';
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

try {
    if ($method === 'GET') {
        $q = isset($_GET['q']) ? "%{$_GET['q']}%" : '%';
        $sql = "SELECT * FROM productos
                WHERE nombre_comercial LIKE ? COLLATE utf8mb4_general_ci
                   OR principio_activo LIKE ? COLLATE utf8mb4_general_ci
                   OR presentacion LIKE ? COLLATE utf8mb4_general_ci
                   OR casa LIKE ? COLLATE utf8mb4_general_ci
                ORDER BY id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$q, $q, $q, $q]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows);
        exit;
    }

    if ($method === 'POST') {
        require_role('admin'); 
        $fields = ['nombre_comercial','presentacion','principio_activo','casa','expira','stock','precio_costo','precio_publico'];
        $vals = [];
        foreach ($fields as $f) $vals[$f] = $input[$f] ?? null;

        $sql = "INSERT INTO productos (nombre_comercial,presentacion,principio_activo,casa,expira,stock,precio_costo,precio_publico)
                VALUES (?,?,?,?,?,?,?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $vals['nombre_comercial'],$vals['presentacion'],$vals['principio_activo'],$vals['casa'],
            $vals['expira'],$vals['stock'],$vals['precio_costo'],$vals['precio_publico']
        ]);
        echo json_encode(['mensaje'=>'Producto creado','id'=>$pdo->lastInsertId()]);
        exit;
    }

    if ($method === 'PUT') {
        require_role('admin'); 
        if (!$id && isset($input['id'])) $id = $input['id'];
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'id requerido']); exit; }

        $fields = ['nombre_comercial','presentacion','principio_activo','casa','expira','stock','precio_costo','precio_publico'];
        $sets = [];
        $params = [];
        foreach ($fields as $f) {
            if (isset($input[$f])) {
                $sets[] = "$f = ?";
                $params[] = $input[$f];
            }
        }
        if (count($sets) === 0) { echo json_encode(['mensaje'=>'Nada que actualizar']); exit; }
        $params[] = $id;
        $sql = "UPDATE productos SET " . implode(',', $sets) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['mensaje'=>'Producto actualizado']);
        exit;
    }

    if ($method === 'DELETE') {
        require_role('admin');
        if (!$id && isset($input['id'])) $id = $input['id'];
        if (!$id) { http_response_code(400); echo json_encode(['error'=>'id requerido']); exit; }
        $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['mensaje'=>'Producto eliminado']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error'=>'Método no permitido']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>'Error servidor: '.$e->getMessage()]);
}
