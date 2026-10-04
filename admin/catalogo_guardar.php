<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requerirRol(['administrador']); requerirPermiso('departamentos.gestionar');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

if (!verificarCsrf($_POST['csrf_token'] ?? null)) {
    header('Location: dashboard.php?error=csrf#tab-catalogos');
    exit;
}

$tipo   = $_POST['catalogo'] ?? ''; // 'departamento' | 'tipo_servicio'
$nombre = trim($_POST['nombre'] ?? '');

if ($nombre === '') {
    header('Location: dashboard.php?error=catalogo#tab-catalogos');
    exit;
}

try {
    if ($tipo === 'departamento') {
        $pdo->prepare('INSERT INTO departamentos (nombre) VALUES (:nombre)')->execute(['nombre' => $nombre]);
    } elseif ($tipo === 'tipo_servicio') {
        $siguienteCodigo = (int)$pdo->query('SELECT COALESCE(MAX(codigo),0) + 1 FROM tipos_servicio')->fetchColumn();
        $pdo->prepare('INSERT INTO tipos_servicio (codigo, nombre) VALUES (:codigo, :nombre)')
            ->execute(['codigo' => $siguienteCodigo, 'nombre' => $nombre]);
    }
} catch (PDOException $e) {
    header('Location: dashboard.php?error=duplicado#tab-catalogos');
    exit;
}

$pdo->prepare('INSERT INTO auditoria_accesos(usuario_id,accion,detalle,ip) VALUES(?,?,?,?)')->execute([usuarioActual()['id'],'crear_catalogo',substr($tipo.': '.$nombre,0,255),$_SERVER['REMOTE_ADDR']??null]);
header('Location: dashboard.php?creado=1#tab-catalogos');
exit;
