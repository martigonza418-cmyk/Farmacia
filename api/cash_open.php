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
$monto_apertura = $data["monto_apertura"];

$sql = "INSERT INTO cajas (fecha_apertura, monto_apertura) VALUES (NOW(), ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("d", $monto_apertura);
$stmt->execute();

echo json_encode(["ok" => true, "id_caja" => $stmt->insert_id]);
$stmt->close();
$conn->close();
?>
