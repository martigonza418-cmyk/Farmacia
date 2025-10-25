<?php
header("Access-Control-Allow-Origin: https://pos-c7b3etcee5bnczbm.westus-01.azurewebsites.net");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Responder a preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'db.php';
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$id_caja = $data["id_caja"];
$total = $data["total"];
$productos = $data["productos"];

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO ventas (id_caja, fecha, total) VALUES (?, NOW(), ?)");
    $stmt->bind_param("id", $id_caja, $total);
    $stmt->execute();
    $id_venta = $stmt->insert_id;

    $stmt_det = $conn->prepare("INSERT INTO detalle_venta (id_venta, producto, cantidad, precio, subtotal) VALUES (?,?,?,?,?)");
    foreach ($productos as $p) {
        $stmt_det->bind_param("isidd", $id_venta, $p["nombre"], $p["cantidad"], $p["precio"], $p["subtotal"]);
        $stmt_det->execute();
    }

    $conn->commit();
    echo json_encode(["ok" => true, "id_venta" => $id_venta]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}

$conn->close();
?>
