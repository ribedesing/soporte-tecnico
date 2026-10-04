<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requerirRol(['analista']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: completar_perfil.php');
    exit;
}

if (!verificarCsrf($_POST['csrf_token'] ?? null)) {
    header('Location: completar_perfil.php?error=csrf');
    exit;
}

$nombre   = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$cedula   = trim($_POST['cedula'] ?? '');
$correo   = trim($_POST['correo'] ?? '');
$confirm  = trim($_POST['confirmar_correo'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$cedula = strtoupper($cedula);

if (!cedulaValida($cedula) || ($telefono !== '' && !telefonoValido($telefono))) {
    header('Location: completar_perfil.php?error=1');
    exit;
}

if (!$nombre || !$apellido || !$cedula || !filter_var($correo, FILTER_VALIDATE_EMAIL) || $correo !== $confirm) {
    header('Location: completar_perfil.php?error=1');
    exit;
}

try {
    $stmt = $pdo->prepare('
        INSERT INTO perfiles_usuarios (usuario_id, nombre, apellido, cedula, correo, telefono, confirmar_correo)
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ');
    $stmt->execute([$_SESSION['usuario_id'], $nombre, $apellido, $cedula, $correo, $telefono ?: null]);
    $_SESSION['nombre_completo'] = $nombre . ' ' . $apellido;
    header('Location: dashboard.php');
} catch (PDOException $e) {
    header('Location: completar_perfil.php?error=1');
}
exit;