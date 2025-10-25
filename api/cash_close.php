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
$total_ventas = $data["total_ventas"];
$monto_apertura = $data["monto_apertura"];

$monto_cierre = $monto_apertura + $total_ventas;
$diferencia = $monto_cierre - $monto_apertura - $total_ventas;

$sql = "UPDATE cajas 
        SET fecha_cierre = NOW(), total_ventas=?, monto_cierre=?, diferencia=?, estado='CERRADA'
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("dddi", $total_ventas, $monto_cierre, $diferencia, $id_caja);
$stmt->execute();

echo json_encode(["ok" => true]);
$stmt->close();
$conn->close();
?>
