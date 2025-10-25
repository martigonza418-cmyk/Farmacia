<?php
include 'db.php';
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$monto_apertura = $data["monto_apertura"];

$sql = "INSERT INTO cajas (fecha_apertura, monto_apertura) VALUES (NOW(), ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("d", $monto_apertura);
$stmt->execute();

echo json_encode(["ok" => true, "id_caja" => $stmt->insert_id]);
$stmt->close();
$conn->close();
?>
