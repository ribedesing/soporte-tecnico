<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

if (!verificarCsrf($_POST['csrf_token'] ?? null)) {
    header('Location: ../index.php?error=4');
    exit;
}

$usuario = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';

if ($usuario === '' || $password === '') {
    header('Location: ../index.php?error=1');
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (excedeLimiteAuth($pdo, 'login-ip-minute', $ip, 8, 60)
    || excedeLimiteAuth($pdo, 'login-ip-hour', $ip, 60, 3600)
    || excedeLimiteAuth($pdo, 'login-account-hour', strtolower($usuario), 12, 3600)) {
    header('Location: ../index.php?error=1');
    exit;
}

$stmt = $pdo->prepare('
    SELECT
        u.id_usuarios,
        u.usuario,
        u.contrasena,
        u.version_sesion,
        u.ultima_actividad,
        u.estado,
        u.rol_id,
        r.nombre AS rol,
        p.nombre,
        p.apellido,
        p.correo,
        p.confirmar_correo,
        p.departamento_id,
        d.nombre AS departamento
    FROM usuarios u
    JOIN roles r ON r.id_roles = u.rol_id
    LEFT JOIN perfiles_usuarios p ON p.usuario_id = u.id_usuarios
    LEFT JOIN departamentos d ON d.id_departamentos = p.departamento_id
    WHERE u.usuario = :usuario
    LIMIT 1
');
$stmt->execute(['usuario' => $usuario]);
$user = $stmt->fetch();

/* Mismo coste criptográfico aunque la cuenta no exista: evita enumeración temporal. */
$dummyHash = '$2y$12$PGhEPpd2MxIYGK6ZgZ8zF.1CrZuh7zxwFl255cdWZjWcZCVf46hvm';
$hashAComparar = is_string($user['contrasena'] ?? null) && $user['contrasena'] !== ''
    ? $user['contrasena']
    : $dummyHash;
$passwordValida = password_verify($password, $hashAComparar);

if (!$user || $user['estado'] !== 'activo' || !$passwordValida) {
    header('Location: ../index.php?error=1');
    exit;
}

if ($user['rol'] === 'departamento' && (int)($user['confirmar_correo'] ?? 0) !== 1) {
    header('Location: ../index.php?error=1');
    exit;
}

$update = $pdo->prepare('UPDATE usuarios SET ultima_actividad = NOW() WHERE id_usuarios = ?');
$update->execute([$user['id_usuarios']]);

session_regenerate_id(true);
$_SESSION['usuario_id'] = $user['id_usuarios'];
$_SESSION['version_sesion'] = (int)$user['version_sesion'];
$_SESSION['usuario'] = $user['usuario'];
$_SESSION['rol'] = $user['rol'];
$_SESSION['nombre_completo'] = trim(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? '')) ?: $user['usuario'];
$_SESSION['departamento_id'] = $user['departamento_id'] ?? null;
$_SESSION['departamento_nombre'] = $user['departamento'] ?? '';

$ruta = rutaDashboardPorRol($user['rol']);
if ($ruta === null) {
    // Rol desconocido: no se abre sesión.
    $_SESSION = [];
    header('Location: ../index.php?error=1');
    exit;
}

$_SESSION['inicio_sesion'] = time();
$_SESSION['ultimo_acceso'] = time();

if ($user['rol'] === 'administrador') {
    $pdo->prepare('INSERT INTO auditoria_accesos(usuario_id,accion,detalle,ip) VALUES(?,?,?,?)')
        ->execute([$user['id_usuarios'], 'inicio_sesion', 'Inicio de sesión de administrador', $_SERVER['REMOTE_ADDR'] ?? null]);
}

header('Location: ../' . $ruta . '/dashboard.php');
exit;
