<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requerirRol(['administrador']);
requerirPermiso('roles.editar');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: roles.php');
    exit;
}
if (!verificarCsrf($_POST['csrf_token'] ?? null)) {
    header('Location: roles.php?error=csrf');
    exit;
}

$id = (int)($_POST['rol_id'] ?? 0);
$solicitados = array_values(array_unique(array_map('intval', (array)($_POST['permisos'] ?? []))));

try {
    $q = $pdo->prepare('SELECT nombre FROM roles WHERE id_roles = ?');
    $q->execute([$id]);
    $rolNombre = $q->fetchColumn();
    if ($rolNombre === false) {
        header('Location: roles.php?error=1');
        exit;
    }

    // Solo se aceptan ids de permisos que existen.
    $validos = array_map('intval', $pdo->query('SELECT id FROM permisos')->fetchAll(PDO::FETCH_COLUMN));
    $perms = array_values(array_intersect($solicitados, $validos));

    // El administrador nunca pierde el control de roles y usuarios (evita bloqueo total).
    if ($rolNombre === 'administrador') {
        $q = $pdo->query("SELECT id FROM permisos WHERE clave IN ('roles.ver','roles.editar','usuarios.ver')");
        $perms = array_values(array_unique(array_merge($perms, array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN)))));
    }

    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM rol_permisos WHERE rol_id = ?')->execute([$id]);
    $ins = $pdo->prepare('INSERT INTO rol_permisos (rol_id, permiso_id) VALUES (?, ?)');
    foreach ($perms as $pid) {
        $ins->execute([$id, $pid]);
    }
    $pdo->prepare('INSERT INTO auditoria_accesos(usuario_id,accion,detalle,ip) VALUES(?,?,?,?)')
        ->execute([usuarioActual()['id'], 'editar_permisos_rol', "Rol {$rolNombre}: " . count($perms) . ' permisos', $_SERVER['REMOTE_ADDR'] ?? null]);
    $pdo->commit();

    header("Location: rol_permisos.php?id={$id}&ok=1");
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    emitirLogError('No se pudieron guardar los permisos.', ['message' => $e->getMessage()]);
    header("Location: rol_permisos.php?id={$id}&error=1");
}
exit;
