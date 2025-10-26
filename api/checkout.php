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
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__.'/db.php';
require_once __DIR__.'/check_role.php';
require_role('cashier');

$raw = file_get_contents('php://input');
$input = json_decode($raw,true);

$customer_type = $input['customer_type'] ?? null;
$customer_document = $input['customer_document'] ?? null;
$note = $input['note'] ?? null;
$branch_id = $input['branch_id'] ?? null;

if(!$input || !isset($input['items']) || !is_array($input['items'])){
    http_response_code(400);
    echo json_encode(['error'=>'Datos inválidos']);
    exit;
}

$items = $input['items'];
$total = $input['total'] ?? 0.0;

try{
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO ventas (usuario_id,total) VALUES (?,?)");
    $stmt->execute([$_SESSION['user']['id'],$total]);
    $venta_id = $pdo->lastInsertId();

    $it_stmt = $pdo->prepare("INSERT INTO venta_items (venta_id,producto_id,cantidad,precio_unit) VALUES (?,?,?,?)");
    $stock_stmt = $pdo->prepare("UPDATE productos SET stock=stock-? WHERE id=? AND stock>=?");

    foreach($items as $it){
        $pid = intval($it['producto_id']);
        $qty = intval($it['cantidad']);
        $price = floatval($it['precio_unit']);
        $stock_stmt->execute([$qty,$pid,$qty]);
        if($stock_stmt->rowCount()===0){
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error'=>"Stock insuficiente para producto ID $pid"]);
            exit;
        }
        $it_stmt->execute([$venta_id,$pid,$qty,$price]);
    }
    $pdo->commit();
    /* guardar metadatos de la venta (nit, cui, consumidor, nota, sucursal) */
try{
    $meta_stmt = $pdo->prepare("CREATE TABLE IF NOT EXISTS venta_meta (id INT AUTO_INCREMENT PRIMARY KEY, venta_id INT, meta JSON, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $meta_stmt->execute();
    $ins_meta = $pdo->prepare("INSERT INTO venta_meta (venta_id, meta) VALUES (?,?)");
    $meta = json_encode(['customer_type'=>$customer_type,'customer_document'=>$customer_document,'note'=>$note,'branch_id'=>$branch_id], JSON_UNESCAPED_UNICODE);
    $ins_meta->execute([$venta_id, $meta]);
}catch(Exception $me){ /* no interrumpir venta por meta */ }

echo json_encode(['ok'=>true,'venta_id'=>$venta_id]);
}catch(Exception $e){
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error'=>'Error servidor: '.$e->getMessage()]);
}
