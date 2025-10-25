<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;
$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Usuario y contraseña requeridos']);
    exit;
}

$MAX_ATTEMPTS = 3;
$LOCK_MINUTES = 15;
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$stmt = $pdo->prepare("SELECT * FROM login_attempts WHERE username = ? AND ip = ?");
$stmt->execute([$username, $ip]);
$attempt = $stmt->fetch();

if ($attempt && $attempt['attempts'] >= $MAX_ATTEMPTS) {
    $last = strtotime($attempt['last_attempt']);
    if (time() - $last < ($LOCK_MINUTES * 60)) {
        http_response_code(429);
        echo json_encode(['error' => 'Cuenta bloqueada temporalmente. Intenta de nuevo más tarde.']);
        exit;
    } else {
        $r = $pdo->prepare("UPDATE login_attempts SET attempts = 0 WHERE id = ?");
        $r->execute([$attempt['id']]);
    }
}

$stmt = $pdo->prepare("SELECT id, username, password, rol FROM usuarios WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

$ok = false;
if ($user) {
    $stored = $user['password'];
    if (password_verify($password, $stored)) $ok = true;
    if (!$ok && $password === $stored) $ok = true;
    if ($ok) {
        $pdo->prepare("DELETE FROM login_attempts WHERE username = ? AND ip = ?")->execute([$username,$ip]);
        if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
            $h = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?")->execute([$h, $user['id']]);
        }
        $_SESSION['user'] = ['id'=>$user['id'],'username'=>$user['username'],'rol'=>$user['rol'],'branch_id' => $user['branch_id'] ?? null];
        echo json_encode(['ok'=>true,'user'=>$_SESSION['user']]);
        exit;
    }
}

if ($attempt) {
    $pdo->prepare("UPDATE login_attempts SET attempts = attempts + 1 WHERE id = ?")->execute([$attempt['id']]);
} else {
    $pdo->prepare("INSERT INTO login_attempts (username, ip, attempts) VALUES (?,?,1)")->execute([$username,$ip]);
}
http_response_code(401);
echo json_encode(['error'=>'Credenciales inválidas']);
