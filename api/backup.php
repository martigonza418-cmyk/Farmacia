<?php
require_once __DIR__ . '/auth_check.php';
header('Content-Type: application/json; charset=utf-8');

session_start();
if ($_SESSION['user']['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error'=>'Solo admin']);
    exit;
}

$dbhost = '127.0.0.1';
$dbname = 'farmacia_pos';
$dbuser = 'root';
$dbpass = '';

$filename = 'backup_' . $dbname . '_' . date('Ymd_His') . '.sql';
$filepath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

$cmd = sprintf('mysqldump --host=%s --user=%s %s --result-file=%s', escapeshellarg($dbhost), escapeshellarg($dbuser), escapeshellarg($dbname), escapeshellarg($filepath));
if ($dbpass !== '') {
    $cmd = sprintf('mysqldump --host=%s --user=%s --password=%s %s --result-file=%s', escapeshellarg($dbhost), escapeshellarg($dbuser), escapeshellarg($dbpass), escapeshellarg($dbname), escapeshellarg($filepath));
}

exec($cmd, $out, $rc);
if ($rc !== 0) {
    http_response_code(500);
    echo json_encode(['error'=>'No se pudo ejecutar mysqldump. Comprueba que esté instalado y en PATH.']);
    exit;
}

header('Content-Description: File Transfer');
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="'.$filename.'"');
readfile($filepath);
unlink($filepath);
exit;
