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

require_once __DIR__.'/db.php';
require_once __DIR__.'/check_role.php';
require_role('admin'); 

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!$input || empty($input['items']) || !is_array($input['items'])) {
    http_response_code(400);
    echo json_encode(['error'=>'Datos inválidos']);
    exit;
}

try {
    $pdo->beginTransaction();

    $supplier_id = $input['supplier_id'] ?? null;
    $branch_id = $input['branch_id'] ?? null;
    $invoice = $input['invoice_number'] ?? null;
    $notes = $input['notes'] ?? null;
    $user_id = $_SESSION['user']['id'] ?? null;

    $total = 0;
    foreach ($input['items'] as $it) {
        $qty = floatval($it['quantity']);
        $price = floatval($it['unit_price']);
        $total += $qty * $price;
    }

    $stmt = $pdo->prepare("INSERT INTO purchases (supplier_id, branch_id, invoice_number, total, created_by, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$supplier_id, $branch_id, $invoice, $total, $user_id, $notes]);
    $purchase_id = $pdo->lastInsertId();

    $ins = $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
    foreach ($input['items'] as $it) {
        $product_id = $it['product_id'];
        $qty = floatval($it['quantity']);
        $price = floatval($it['unit_price']);
        $sub = $qty * $price;
        $ins->execute([$purchase_id, $product_id, $qty, $price, $sub]);
        $pdo->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?")->execute([$qty, $product_id]);
    }

    $pdo->commit();
    echo json_encode(['ok'=>true, 'purchase_id'=>$purchase_id]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error'=>'Error registrando compra: '.$e->getMessage()]);
}