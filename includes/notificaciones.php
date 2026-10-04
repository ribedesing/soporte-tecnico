<?php
/**
 * Módulo único de notificaciones.
 *
 * Reemplaza (y sustituye por completo) a:
 *   - /notificaciones.php                  (página "Todas las notificaciones")
 *   - /notificaciones_pendientes.php       (AJAX: notificaciones no leídas)
 *   - /notificacion_accion.php             (abrir una notificación y marcarla leída)
 *   - /notificaciones_marcar_leidas.php    (marcar una o todas como leídas)
 *   - /includes/notificaciones_secretaria.php (funciones generadoras)
 *
 * Este archivo funciona como librería (las funciones se pueden `require`
 * desde includes/topbar.php) y, cuando se accede a él directamente por URL
 * con ?accion=..., como router de los 4 endpoints anteriores.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/security.php';

/* =====================================================================
   FUNCIONES GENERADORAS (antes en includes/notificaciones_secretaria.php)
   ===================================================================== */

function generarNotificacionesSecretaria(PDO $pdo): void
{
    $secretarias = $pdo->query("SELECT u.id_usuarios
        FROM usuarios u
        INNER JOIN roles r ON r.id_roles = u.rol_id
        WHERE r.nombre = 'secretaria'")->fetchAll(PDO::FETCH_COLUMN);

    if (!$secretarias) {
        return;
    }

    $existe = $pdo->prepare('SELECT 1 FROM notificaciones WHERE usuario_id=? AND url=? LIMIT 1');
    $crear = $pdo->prepare('INSERT INTO notificaciones (usuario_id, titulo, mensaje, url, leida) VALUES (?, ?, ?, ?, 0)');

    $agregar = static function (int $usuarioId, string $titulo, string $mensaje, string $url) use ($existe, $crear): void {
        $existe->execute([$usuarioId, $url]);
        if (!$existe->fetchColumn()) {
            $crear->execute([$usuarioId, $titulo, $mensaje, $url]);
        }
    };

    $tickets = $pdo->query("SELECT id_tickets, titulo
        FROM tickets
        WHERE estado='pendiente'
          AND analista_id IS NULL
          AND fecha_creacion <= DATE_SUB(NOW(), INTERVAL 10 MINUTE)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tickets as $ticket) {
        $url = 'secretaria/tickets.php?ticket=' . (int)$ticket['id_tickets'] . '&aviso=sin_atender';
        foreach ($secretarias as $secretariaId) {
            $agregar((int)$secretariaId, 'Ticket sin atender', 'El ticket #' . str_pad((string)$ticket['id_tickets'], 6, '0', STR_PAD_LEFT) . ' lleva más de 10 minutos sin ser tomado.', $url);
        }
    }

    $hojas = $pdo->query("SELECT id_hojas, estatus
        FROM hojas_servicio
        WHERE estatus IN ('en_proceso', 'pendiente_insumos')
          AND created_at <= DATE_SUB(NOW(), INTERVAL 2 DAY)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($hojas as $hoja) {
        $url = 'secretaria/hojas.php?hoja=' . (int)$hoja['id_hojas'] . '&aviso=pendiente';
        foreach ($secretarias as $secretariaId) {
            $agregar((int)$secretariaId, 'Servicio pendiente de culminar', 'La hoja de servicio #' . (int)$hoja['id_hojas'] . ' lleva más de 2 días sin culminar.', $url);
        }
    }

    $analistas = $pdo->query("SELECT u.id_usuarios, u.usuario
        FROM usuarios u
        INNER JOIN roles r ON r.id_roles = u.rol_id
        LEFT JOIN perfiles_usuarios p ON p.usuario_id = u.id_usuarios
        WHERE r.nombre='analista'
          AND u.fecha_creacion <= DATE_SUB(NOW(), INTERVAL 3 DAY)
          AND (p.id_perfil IS NULL OR p.nombre IS NULL OR p.apellido IS NULL OR p.correo IS NULL OR p.cedula IS NULL)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($analistas as $analista) {
        $url = 'secretaria/analista_detalle.php?id=' . (int)$analista['id_usuarios'] . '&aviso=perfil_pendiente';
        foreach ($secretarias as $secretariaId) {
            $agregar((int)$secretariaId, 'Perfil de analista incompleto', 'El analista ' . $analista['usuario'] . ' lleva más de 3 días sin completar su perfil.', $url);
        }
    }
}

function generarNotificacionesAdministrador(PDO $pdo): void
{
    $administradores = $pdo->query("SELECT u.id_usuarios
        FROM usuarios u
        INNER JOIN roles r ON r.id_roles = u.rol_id
        WHERE r.nombre = 'administrador' AND u.estado = 'activo'")->fetchAll(PDO::FETCH_COLUMN);

    if (!$administradores) {
        return;
    }

    $eventos = $pdo->query("SELECT a.id_auditoria, a.accion, a.detalle, a.usuario_id, a.created_at, u.usuario
        FROM auditoria_accesos a
        INNER JOIN usuarios u ON u.id_usuarios = a.usuario_id
        ORDER BY a.id_auditoria DESC
        LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
    $existe = $pdo->prepare('SELECT 1 FROM notificaciones WHERE usuario_id=? AND url=? LIMIT 1');
    $crear = $pdo->prepare('INSERT INTO notificaciones (usuario_id, titulo, mensaje, url, leida) VALUES (?, ?, ?, ?, 0)');

    foreach ($eventos as $evento) {
        $url = 'admin/auditoria.php?evento=' . (int)$evento['id_auditoria'];
        $titulo = 'Actividad del sistema';
        $detalle = trim((string)($evento['detalle'] ?? ''));
        $mensaje = $evento['usuario'] . ' realizó ' . str_replace('_', ' ', $evento['accion'])
            . ($detalle !== '' ? ': ' . $detalle : '.');

        foreach ($administradores as $administradorId) {
            $existe->execute([(int)$administradorId, $url]);
            if (!$existe->fetchColumn()) {
                $crear->execute([(int)$administradorId, $titulo, $mensaje, $url]);
            }
        }
    }
}

/* =====================================================================
   ROUTER — solo se ejecuta si el navegador pidió este archivo
   directamente (no cuando otro .php lo hace require_once como librería)
   ===================================================================== */

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {

    $accion = $_GET['accion'] ?? 'ver';

    /* ---- accion=pendientes : JSON con notificaciones no leídas (AJAX) ---- */
    if ($accion === 'pendientes') {
        header('Content-Type: application/json; charset=UTF-8');
        if (!usuarioAutenticado()) {
            http_response_code(401);
            echo json_encode(['ok' => false]);
            exit;
        }
        try {
            $q = $pdo->prepare("SELECT id,titulo,mensaje,url,created_at FROM notificaciones WHERE usuario_id=? AND leida=0 ORDER BY created_at DESC LIMIT 10");
            $q->execute([$_SESSION['usuario_id']]);
            echo json_encode(['ok' => true, 'notificaciones' => $q->fetchAll()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false]);
        }
        exit;
    }

    /* ---- accion=abrir : POST + CSRF; nunca modifica estado por GET ---- */
    if ($accion === 'abrir') {
        if (!usuarioAutenticado()) { header('Location: ../index.php'); exit; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verificarCsrf($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            exit('Solicitud no válida.');
        }
        $id = (int)($_POST['id'] ?? 0);
        $q = $pdo->prepare('SELECT url FROM notificaciones WHERE id=? AND usuario_id=?');
        $q->execute([$id, $_SESSION['usuario_id']]);
        $n = $q->fetch();
        if ($n) {
            $pdo->prepare('UPDATE notificaciones SET leida=1 WHERE id=? AND usuario_id=?')->execute([$id, $_SESSION['usuario_id']]);
            header('Location: ' . ($n['url'] ? '../' . $n['url'] : '../index.php'));
            exit;
        }
        header('Location: ../index.php'); exit;
    }

    /* ---- accion=marcar (POST) : marcar una o todas como leídas ---- */
    if ($accion === 'marcar') {
        if (!usuarioAutenticado()) {
            header('Location: ../index.php');
            exit;
        }
        $volver = $_POST['volver'] ?? '';
        $destino = $volver !== '' ? $volver : '../includes/notificaciones.php';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verificarCsrf($_POST['csrf_token'] ?? null)) {
            header('Location: ' . $destino . (str_contains($destino, '?') ? '&' : '?') . 'error=csrf');
            exit;
        }

        $u = usuarioActual();
        if (isset($_POST['marcar_todas'])) {
            $pdo->prepare('UPDATE notificaciones SET leida=1 WHERE usuario_id=?')->execute([$u['id']]);
        } elseif (!empty($_POST['id'])) {
            $pdo->prepare('UPDATE notificaciones SET leida=1 WHERE id=? AND usuario_id=?')->execute([(int)$_POST['id'], $u['id']]);
        }

        header('Location: ' . $destino);
        exit;
    }

    /* ---- accion=ver (por defecto) : página "Todas las notificaciones" ---- */
    requerirRol(['analista', 'tecnico', 'secretaria', 'departamento', 'administrador']);
    $u = usuarioActual();

    $q = $pdo->prepare('SELECT id,titulo,mensaje,url,leida,created_at FROM notificaciones WHERE usuario_id=? ORDER BY created_at DESC');
    $q->execute([$u['id']]);
    $notifs = $q->fetchAll();

    $perfilTop = $pdo->prepare('SELECT nombre,apellido,avatar FROM perfiles_usuarios WHERE usuario_id=? LIMIT 1');
    $perfilTop->execute([$u['id']]);
    $pt = $perfilTop->fetch() ?: [];
    $nombreTop = trim(($pt['nombre'] ?? '') . ' ' . ($pt['apellido'] ?? '')) ?: ($u['usuario'] ?? 'Usuario');
    $avatarTop = $pt['avatar'] ?? '';

    $moduloPath = $u['rol'] === 'administrador' ? 'admin/' : (in_array($u['rol'], ['secretaria'], true) ? 'secretaria/' : (($u['rol'] === 'departamento') ? 'departamento/' : 'analista/'));
    $rootPath = '../';
    $assetPath = '../assets/';
    $logoutPath = '../logout.php';
    $perfilUrl = '../includes/perfil.php';
    ?>
    <!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Notificaciones · Soporte Técnico</title><link rel="icon" href="../assets/img/logo.png"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous"><link href="../assets/css/style.css?v=20261001-notifications" rel="stylesheet"><?php if ($u['rol'] === 'administrador'): ?><link href="../assets/css/admin.css?v=20261001-blue" rel="stylesheet"><?php endif; ?></head><body><div class="app-shell">
    <?php $moduloPath = '../' . $moduloPath; require __DIR__ . '/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/topbar.php'; ?>
        <div class="content"><div class="card-panel notification-page"><div class="panel-head"><div><h2><i class="bi bi-bell me-2"></i>Todas las notificaciones</h2><span class="text-muted small"><?= count($notifs) ?> registros</span></div><?php if ($notifs): ?><form method="post" action="notificaciones.php?accion=marcar" class="mb-0"><?= csrf_field() ?><input type="hidden" name="volver" value="notificaciones.php"><button type="submit" name="marcar_todas" value="1" class="btn btn-outline-brand btn-sm"><i class="bi bi-check2-all me-1"></i>Marcar todas como leídas</button></form><?php endif; ?></div><div class="panel-body p-0">
            <?php if (!$notifs): ?><div class="text-center text-muted py-5">No tienes notificaciones.</div><?php endif; ?>
            <?php foreach ($notifs as $n): ?><div class="notification-row <?= !(int)$n['leida'] ? 'unread' : '' ?>"><form method="post" action="notificaciones.php?accion=abrir" class="notification-row-link-form"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$n['id'] ?>"><button type="submit" class="notification-row-link"><div class="notification-icon"><i class="bi bi-bell-fill"></i></div><div class="notification-content"><div class="fw-semibold"><?= h($n['titulo']) ?></div><div class="text-muted small"><?= h($n['mensaje']) ?></div><div class="notification-time"><?= h(date('d/m/Y H:i', strtotime($n['created_at']))) ?></div></div><i class="bi bi-chevron-right notification-arrow"></i></button></form><?php if (!(int)$n['leida']): ?><form method="post" action="notificaciones.php?accion=marcar" class="notification-read-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$n['id'] ?>"><input type="hidden" name="volver" value="notificaciones.php"><button type="submit" class="btn btn-outline-brand btn-sm" title="Marcar como leída"><i class="bi bi-check2"></i><span class="visually-hidden">Marcar como leída</span></button></form><?php endif; ?></div><?php endforeach; ?>
        </div></div></div></div></div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script><script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">(function(){const key='soporte-theme';const apply=t=>{document.documentElement.classList.toggle('dark-mode',t==='dark');document.body.classList.toggle('dark-mode',t==='dark');};apply(localStorage.getItem(key)||'light');})();</script></body></html>
    <?php
    exit;
}
