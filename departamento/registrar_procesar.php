<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../includes/password.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: registrar.php'); exit; }

if (!verificarCsrf($_POST['csrf_token'] ?? null)) {
    header('Location: registrar.php?error=csrf'); exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$cedula = trim($_POST['cedula'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$departamento = trim($_POST['departamento'] ?? '');
$usuario = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirmation = $_POST['password_confirmation'] ?? '';
$cedula = strtoupper($cedula);

if (!$nombre || !$apellido || !$cedula || !$telefono || !filter_var($correo, FILTER_VALIDATE_EMAIL) || !$departamento || !$usuario || !$password || !$passwordConfirmation) {
    header('Location: registrar.php?error=1'); exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (excedeLimiteAuth($pdo, 'registro-ip-hour', $ip, 10, 3600)
    || excedeLimiteAuth($pdo, 'registro-ip-day', $ip, 30, 86400)
    || excedeLimiteAuth($pdo, 'registro-email-hour', strtolower($correo), 3, 3600)
    || excedeLimiteAuth($pdo, 'registro-user-hour', strtolower($usuario), 3, 3600)) {
    header('Location: registrar.php?error=8'); exit;
}

if (($_POST['acepta_terminos'] ?? '') !== '1') {
    header('Location: registrar.php?error=7'); exit;
}

if (!cedulaValida($cedula) || !telefonoValido($telefono)) {
    header('Location: registrar.php?error=6'); exit;
}

if ($password !== $passwordConfirmation) {
    header('Location: registrar.php?error=4'); exit;
}

if (!passwordValida($password)) {
    header('Location: registrar.php?error=3'); exit;
}

$base = urlAplicacion();
if ($base === null) {
    emitirLogError('APP_URL no está configurada para confirmación de cuenta.');
    header('Location: registrar.php?error=5'); exit;
}

try {
    $pdo->beginTransaction();

    $role = $pdo->query("SELECT id_roles FROM roles WHERE nombre='departamento' LIMIT 1")->fetchColumn();
    if (!$role) throw new RuntimeException('Rol departamento no existe. Ejecute la migración SQL.');

    $q = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario=?");
    $q->execute([$usuario]);
    if ($q->fetchColumn()) throw new RuntimeException('duplicado');

    $q = $pdo->prepare("SELECT COUNT(*) FROM perfiles_usuarios WHERE cedula=? OR correo=?");
    $q->execute([$cedula, $correo]);
    if ($q->fetchColumn()) throw new RuntimeException('duplicado');

    // El usuario escribe el departamento. Si ya existe, se reutiliza; si no, se crea.
    $q = $pdo->prepare("SELECT id_departamentos FROM departamentos WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?)) LIMIT 1");
    $q->execute([$departamento]);
    $departamentoId = $q->fetchColumn();

    if (!$departamentoId) {
        $q = $pdo->prepare("INSERT INTO departamentos (nombre, estado) VALUES (?, 'activo')");
        $q->execute([$departamento]);
        $departamentoId = (int)$pdo->lastInsertId();
    } else {
        $q = $pdo->prepare("SELECT estado FROM departamentos WHERE id_departamentos=?");
        $q->execute([$departamentoId]);
        if ($q->fetchColumn() !== 'activo') throw new RuntimeException('departamento_inactivo');
    }

    $q = $pdo->prepare("INSERT INTO usuarios(usuario,contrasena,rol_id,ultima_actividad) VALUES(?,?,?,NULL)");
    $q->execute([$usuario, password_hash($password, PASSWORD_DEFAULT), $role]);
    $uid = (int)$pdo->lastInsertId();

    $q = $pdo->prepare("INSERT INTO perfiles_usuarios(usuario_id,nombre,apellido,cedula,correo,confirmar_correo,telefono,departamento_id) VALUES(?,?,?,?,?,0,?,?)");
    $q->execute([$uid, $nombre, $apellido, $cedula, $correo, $telefono, $departamentoId]);

    // Crear token de confirmación antes de enviar el correo.
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $pdo->prepare("DELETE FROM confirmaciones_correo WHERE usuario_id=?")->execute([$uid]);
    $pdo->prepare("INSERT INTO confirmaciones_correo (usuario_id, token_hash, expira_en) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))")
        ->execute([$uid, $hash]);

    $pdo->commit();

    $url = $base . '/departamento/confirmar_correo.php?token=' . urlencode($token);

    if (!enviarCorreoConfirmacion($correo, $nombre . ' ' . $apellido, $url)) {
        // La cuenta queda creada pero se informa para poder reenviar desde el flujo de confirmación.
        header('Location: ../index.php?registro=2'); exit;
    }

    header('Location: ../index.php?registro=1'); exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    emitirLogError('Registro de cuenta fallido.', ['code' => $e->getCode()]);
    header('Location: registrar.php?error=2'); exit;
}
