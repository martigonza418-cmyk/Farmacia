<?php
$venta_id = isset($_GET['venta_id']) ? intval($_GET['venta_id']) : 0;
if (!$venta_id) { echo 'venta_id requerido'; exit; }
$html_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST']
    . dirname($_SERVER['REQUEST_URI'])
    . '/invoice.php?venta_id=' . $venta_id;
\$tmp = sys_get_temp_dir() . '/invoice_' . \$venta_id . '.pdf';
\$cmd = 'wkhtmltopdf ' . escapeshellarg(\$html_url) . ' ' . escapeshellarg(\$tmp) . ' 2>&1';
exec(\$cmd, \$out, \$rc);
if (\$rc === 0 && file_exists(\$tmp)) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="factura_' . \$venta_id . '.pdf"');
    readfile(\$tmp);
    unlink(\$tmp);
    exit;
} else {
    header('Location: ' . dirname(\$_SERVER['REQUEST_URI']) . '/invoice.php?venta_id=' . \$venta_id);
    exit;
}
