<?php
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
