<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/fpdf/fpdf.php';

$venta_id = isset($_GET['venta_id']) ? intval($_GET['venta_id']) : 0;
if (!$venta_id) { echo 'venta_id requerido'; exit; }

$stmt = $pdo->prepare("
    SELECT v.*, u.username 
    FROM ventas v 
    LEFT JOIN usuarios u ON u.id = v.usuario_id 
    WHERE v.id = ?
");
$stmt->execute([$venta_id]);
$venta = $stmt->fetch();
if (!$venta) { echo 'Venta no encontrada'; exit; }

$items = $pdo->prepare("
    SELECT vi.*, p.nombre_comercial 
    FROM venta_items vi 
    LEFT JOIN productos p ON p.id = vi.producto_id 
    WHERE vi.venta_id = ?
");
$items->execute([$venta_id]);
$rows = $items->fetchAll();
$isService = count($rows) === 0;
$nota = trim($venta['note'] ?? '');

class PDF_Doc extends FPDF {
    function Header() {
        $this->Image('https://hospitalhilariogalindo.org/wp-content/uploads/2024/12/logo_mc.png', 10, 10, 45);
        $this->SetFont('Arial','B',14);
        $this->Cell(0,8,utf8_decode('HOSPITAL HILARIO GALINDO'),0,1,'R');
        $this->SetFont('Arial','',10);
        $this->Cell(0,6,utf8_decode('Dirección: Retalhuleu, Guatemala'),0,1,'R');
        $this->Cell(0,6,'Tel: (502) 7771-1234 | Email: info@hospitalhilariogalindo.org',0,1,'R');
        $this->Ln(5);
        $this->Cell(0,0,'','T');
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial','I',9);
        $this->Cell(0,10,utf8_decode('Gracias por su preferencia.'),0,1,'C');
    }
}

$pdf = new PDF_Doc();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);

if ($isService) {
    $pdf->Cell(0,10,utf8_decode("Recibo de Servicio #$venta_id"),0,1,'C');
} else {
    $pdf->Cell(0,10,utf8_decode("Factura #$venta_id"),0,1,'C');
}

$pdf->SetFont('Arial','',11);
$pdf->Ln(2);
$pdf->Cell(0,6,'Fecha: '.date('d/m/Y H:i', strtotime($venta['created_at'])),0,1,'C');
$pdf->Ln(8);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,7,utf8_decode('Datos del Cliente'),0,1);
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,6,utf8_decode('Cliente: Consumidor Final'),0,1);
$pdf->Ln(5);

if (!$isService) {

    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(240,240,240);
    $pdf->Cell(80,8,utf8_decode('Producto'),1,0,'C',true);
    $pdf->Cell(25,8,'Cantidad',1,0,'C',true);
    $pdf->Cell(35,8,'Precio',1,0,'C',true);
    $pdf->Cell(40,8,'Total',1,1,'C',true);

    $pdf->SetFont('Arial','',11);
    foreach ($rows as $r) {
        $subtotal = $r['cantidad'] * $r['precio_unit'];
        $pdf->Cell(80,8,utf8_decode($r['nombre_comercial']),1);
        $pdf->Cell(25,8,$r['cantidad'],1,0,'C');
        $pdf->Cell(35,8,'Q '.number_format($r['precio_unit'],2),1,0,'R');
        $pdf->Cell(40,8,'Q '.number_format($subtotal,2),1,1,'R');
    }
}

if ($isService && $nota !== '') {
    $pdf->Ln(10);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,7,utf8_decode('Motivo del Servicio'),0,1);

    $pdf->SetFont('Arial','',11);
    $pdf->MultiCell(140,8,utf8_decode($nota),1,'L');
    
    $pdf->SetXY(150, $pdf->GetY() - 8); 
    $pdf->Cell(40,8,'Q '.number_format($venta['total'],2),1,1,'R');
} else {
    $pdf->Ln(10);
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(140,8,utf8_decode('Total a pagar:'),0,0,'R');
    $pdf->Cell(40,8,'Q '.number_format($venta['total'],2),0,1,'R');
}

$pdf->Ln(10);
$pdf->SetFont('Arial','I',10);

if ($isService) {
    $pdf->MultiCell(0,6,utf8_decode(
        'Este recibo corresponde al pago de consulta u otro servicio médico prestado en el Hospital Hilario Galindo.'
    ),0,'C');
} else {
    $pdf->MultiCell(0,6,utf8_decode(
        'Este documento no tiene validez fiscal. Es una representación interna del sistema de ventas.'
    ),0,'C');
}

$pdf->Output('I', ($isService?'recibo_':'factura_').$venta_id.'.pdf');
