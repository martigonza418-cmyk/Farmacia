<?php


session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__.'/db.php';
require_once __DIR__.'/check_role.php';
require_role('cashier');

$raw = file_get_contents('php://input');
$in = json_decode($raw, true);

$sale_id = $in['sale_id'] ?? null;
$purchase_id = $in['purchase_id'] ?? null;
$amount = floatval($in['amount'] ?? 0);
$payment_type = $in['payment_type'] ?? 'efectivo'; 
$details = $in['details'] ?? null;
$is_credit = !empty($in['is_credit']) ? 1 : 0;
$branch_id = $in['branch_id'] ?? null;
$user_id = $_SESSION['user']['id'] ?? null;

if (!$amount || (!$sale_id && !$purchase_id)) {
    http_response_code(400);
    echo json_encode(['error'=>'Datos incompletos']);
    exit;
}

try {
    $pdo->beginTransaction();

    $ins = $pdo->prepare("INSERT INTO payments (sale_id, purchase_id, branch_id, user_id, amount, payment_type, payment_method_details, is_credit) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $ins->execute([$sale_id, $purchase_id, $branch_id, $user_id, $amount, $payment_type, $details, $is_credit]);
    $payment_id = $pdo->lastInsertId();

    if ($sale_id) {
        $upd = $pdo->prepare("UPDATE ventas SET total_pending = GREATEST(total_pending - ?, 0) WHERE id = ?");
        $upd->execute([$amount, $sale_id]);
    }

    $pdo->commit();
    echo json_encode(['ok'=>true,'payment_id'=>$payment_id]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error'=>'Error registrando pago: '.$e->getMessage()]);
}
