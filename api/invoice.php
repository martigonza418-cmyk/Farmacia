<?php

require_once __DIR__ . '/db.php';
header('Content-Type: text/html; charset=utf-8');

$venta_id = isset($_GET['venta_id']) ? intval($_GET['venta_id']) : 0;
if (!$venta_id) { echo 'venta_id requerido'; exit; }

$stmt = $pdo->prepare("SELECT v.*, u.username FROM ventas v LEFT JOIN usuarios u ON u.id = v.usuario_id WHERE v.id = ?");
$stmt->execute([$venta_id]);
$venta = $stmt->fetch();
if (!$venta) { echo 'Venta no encontrada'; exit; }

$items = $pdo->prepare("SELECT vi.*, p.nombre_comercial FROM venta_items vi LEFT JOIN productos p ON p.id = vi.producto_id WHERE vi.venta_id = ?");
$items->execute([$venta_id]);
$rows = $items->fetchAll();

?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><title>Factura #<?=htmlspecialchars($venta_id)?></title>
<style>body{font-family:Arial,Helvetica,sans-serif}table{width:100%;border-collapse:collapse}td,th{border:1px solid #ddd;padding:8px}</style>
</head><body>
<h2>Factura #<?=htmlspecialchars($venta_id)?></h2>
<p>Fecha: <?=htmlspecialchars($venta['created_at'])?><br>Vendedor: <?=htmlspecialchars($venta['username'])?></p>
<table><thead><tr><th>Producto</th><th>Cantidad</th><th>Precio unit.</th><th>Subtotal</th></tr></thead><tbody>
<?php foreach($rows as $r): ?>
<tr>
<td><?=htmlspecialchars($r['nombre_comercial'])?></td>
<td><?=intval($r['cantidad'])?></td>
<td><?=number_format($r['precio_unit'],2)?></td>
<td><?=number_format($r['cantidad']*$r['precio_unit'],2)?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr><td colspan="3" style="text-align:right"><strong>Total</strong></td><td><?=number_format($venta['total'],2)?></td></tr></tfoot></table>
</body></html>
<?php
