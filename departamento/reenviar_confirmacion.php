<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/mail.php';

requerirRol(['departamento']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: confirmar_correo.php');
    exit;
}

if (!verificarCsrf($_POST['csrf_token'] ?? null)) {
    header('Location: confirmar_correo.php?estado=error');
    exit;
}

$uid = (int)$_SESSION['usuario_id'];
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (excedeLimiteAuth($pdo, 'reenviar-confirmacion-ip-hour', $ip, 10, 3600)) {
    header('Location: confirmar_correo.php?estado=error');
    exit;
}

$q = $pdo->prepare('SELECT p.correo,p.nombre,p.apellido,p.confirmar_correo FROM perfiles_usuarios p WHERE p.usuario_id=? LIMIT 1');
$q->execute([$uid]);
$p = $q->fetch();
if (!$p) { header('Location: confirmar_correo.php?estado=error'); exit; }
if ((int)$p['confirmar_correo'] === 1) { header('Location: dashboard.php'); exit; }
if (excedeLimiteAuth($pdo, 'reenviar-confirmacion-account-hour', (string)$uid, 3, 3600)
    || excedeLimiteAuth($pdo, 'reenviar-confirmacion-email-hour', strtolower((string)$p['correo']), 3, 3600)) {
    header('Location: confirmar_correo.php?estado=error');
    exit;
}

$base = urlAplicacion();
if ($base === null) {
    emitirLogError('APP_URL no está configurada para confirmación de cuenta.');
    header('Location: confirmar_correo.php?estado=error'); exit;
}

$token = bin2hex(random_bytes(32));
$hash = hash('sha256', $token);
$pdo->beginTransaction();
try {
    $pdo->prepare('DELETE FROM confirmaciones_correo WHERE usuario_id=?')->execute([$uid]);
    $pdo->prepare('INSERT INTO confirmaciones_correo (usuario_id, token_hash, expira_en) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))')->execute([$uid,$hash]);
    $pdo->commit();

    $url = $base . '/departamento/confirmar_correo.php?token=' . urlencode($token);
    $ok = enviarCorreoConfirmacion($p['correo'], $p['nombre'].' '.$p['apellido'], $url);
    header('Location: confirmar_correo.php?estado=' . ($ok ? 'enviado' : 'error')); exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    emitirLogError('Reenvío de confirmación fallido.', ['usuario_id' => $uid, 'code' => $e->getCode()]);
    header('Location: confirmar_correo.php?estado=error'); exit;
}
