<?php
// La sesión se inicia siempre desde security.php (cookie httponly, samesite, modo estricto).
require_once __DIR__ . '/security.php';

// Cierre automático de sesión (segundos).
const SESION_INACTIVIDAD_MAX = 1800;  // 30 min sin actividad
const SESION_DURACION_MAX    = 36000; // 10 h desde el inicio de sesión

function usuarioAutenticado() {
    return isset($_SESSION['usuario_id']);
}

function tieneRol($rol) {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === $rol;
}

function rutaDashboardPorRol(?string $rol): ?string {
    return match ($rol) {
        'administrador' => 'admin',
        'secretaria' => 'secretaria',
        'analista', 'tecnico' => 'analista',
        'departamento' => 'departamento',
        default => null,
    };
}

/** Devuelve la conexión PDO, cargándola si la página aún no lo hizo. */
function obtenerPdoAuth(): PDO {
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        return $GLOBALS['pdo'];
    }
    require_once __DIR__ . '/../config/database.php';
    if (isset($pdo) && $pdo instanceof PDO) {
        $GLOBALS['pdo'] = $pdo;
        return $pdo;
    }
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        return $GLOBALS['pdo'];
    }
    http_response_code(500);
    die('Error de configuración.');
}

function cerrarSesionYRedirigir(string $destino): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: ' . $destino);
    exit;
}

/**
 * Revalida la sesión contra la base de datos en cada petición protegida:
 * - expira por inactividad y por duración máxima;
 * - si la cuenta fue desactivada o eliminada, la sesión muere de inmediato;
 * - si el rol cambió, se aplica el rol actual (una degradación surte efecto al instante).
 */
function validarSesionVigente(): void {
    $ahora  = time();
    $inicio = (int)($_SESSION['inicio_sesion'] ?? 0);
    $ultimo = (int)($_SESSION['ultimo_acceso'] ?? 0);
    if ($inicio === 0) { $inicio = $ahora; $_SESSION['inicio_sesion'] = $ahora; }
    if ($ultimo === 0) { $ultimo = $ahora; }

    if (($ahora - $ultimo) > SESION_INACTIVIDAD_MAX || ($ahora - $inicio) > SESION_DURACION_MAX) {
        cerrarSesionYRedirigir('../index.php');
    }
    $_SESSION['ultimo_acceso'] = $ahora;

    try {
        $st = obtenerPdoAuth()->prepare(
            'SELECT u.estado, u.version_sesion, r.nombre AS rol
               FROM usuarios u JOIN roles r ON r.id_roles = u.rol_id
              WHERE u.id_usuarios = ? LIMIT 1'
        );
        $st->execute([(int)$_SESSION['usuario_id']]);
        $fila = $st->fetch();
    } catch (Throwable $e) {
        emitirLogError('No se pudo revalidar la sesión.', ['message' => $e->getMessage()]);
        http_response_code(503);
        die('Servicio no disponible.');
    }

    if (!$fila || $fila['estado'] !== 'activo') {
        cerrarSesionYRedirigir('../index.php');
    }
    if (!isset($_SESSION['version_sesion']) || (int)$_SESSION['version_sesion'] !== (int)$fila['version_sesion']) {
        cerrarSesionYRedirigir('../index.php');
    }
    if ($fila['rol'] !== ($_SESSION['rol'] ?? '')) {
        $_SESSION['rol'] = $fila['rol'];
    }
}

function requerirRol($roles) {
    if (!usuarioAutenticado()) {
        header('Location: ../index.php');
        exit;
    }

    validarSesionVigente();
    enviarCabecerasSeguridad();

    if (!in_array($_SESSION['rol'] ?? '', $roles, true)) {
        $ruta = rutaDashboardPorRol($_SESSION['rol'] ?? null);
        header('Location: ' . ($ruta ? '../' . $ruta . '/dashboard.php' : '../index.php'));
        exit;
    }
}

/**
 * ¿El rol de la sesión tiene este permiso (tablas permisos / rol_permisos)?
 * El administrador conserva siempre roles.ver y roles.editar para que
 * nadie pueda dejar el sistema sin quien administre los permisos.
 */
function tienePermiso(string $clave): bool {
    static $cache = [];
    if (!usuarioAutenticado()) {
        return false;
    }
    $rol = $_SESSION['rol'] ?? '';
    if ($rol === 'administrador' && in_array($clave, ['roles.ver', 'roles.editar'], true)) {
        return true;
    }
    if (!isset($cache[$rol])) {
        $st = obtenerPdoAuth()->prepare(
            'SELECT p.clave
               FROM rol_permisos rp
               JOIN permisos p ON p.id = rp.permiso_id
               JOIN roles r ON r.id_roles = rp.rol_id
              WHERE r.nombre = ?'
        );
        $st->execute([$rol]);
        $cache[$rol] = array_flip($st->fetchAll(PDO::FETCH_COLUMN));
    }
    return isset($cache[$rol][$clave]);
}

function requerirPermiso(string $clave): void {
    if (tienePermiso($clave)) {
        return;
    }
    emitirLogError('Acceso denegado por permiso.', [
        'permiso' => $clave,
        'usuario_id' => $_SESSION['usuario_id'] ?? null,
        'rol' => $_SESSION['rol'] ?? null,
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
    ]);
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Acceso denegado</title>'
       . '<meta name="viewport" content="width=device-width,initial-scale=1"></head>'
       . '<body style="font-family:sans-serif;max-width:480px;margin:15vh auto;padding:0 1rem">'
       . '<h2>Acceso denegado</h2><p>Tu rol no tiene el permiso necesario para esta sección.</p>'
       . '<p><a href="dashboard.php">Volver al panel</a></p></body></html>';
    exit;
}

function usuarioActual() {
    if (!empty($_SESSION['usuario_id'])) {
        $ultimoPulso = (int)($_SESSION['ultima_actividad_pulso'] ?? 0);
        if (time() - $ultimoPulso >= 60) {
            global $pdo;
            if (isset($pdo) && $pdo instanceof PDO) {
                $actualizarActividad = $pdo->prepare('UPDATE usuarios SET ultima_actividad = NOW() WHERE id_usuarios = ?');
                $actualizarActividad->execute([(int)$_SESSION['usuario_id']]);
                $_SESSION['ultima_actividad_pulso'] = time();
            }
        }
    }

    return [
        'id' => $_SESSION['usuario_id'] ?? null,
        'usuario' => $_SESSION['usuario'] ?? '',
        'rol' => $_SESSION['rol'] ?? '',
        'nombre_completo' => $_SESSION['nombre_completo'] ?? '',
        'departamento_id' => $_SESSION['departamento_id'] ?? null,
        'departamento_nombre' => $_SESSION['departamento_nombre'] ?? '',
    ];
}

function requerirCorreoConfirmado(PDO $pdo) {
    if (!usuarioAutenticado()) {
        header('Location: ../index.php');
        exit;
    }
    $q = $pdo->prepare('SELECT confirmar_correo FROM perfiles_usuarios WHERE usuario_id=? LIMIT 1');
    $q->execute([$_SESSION['usuario_id']]);
    $confirmado = (int)$q->fetchColumn() === 1;
    if (!$confirmado && ($_SESSION['rol'] ?? '') === 'departamento') {
        header('Location: confirmar_correo.php');
        exit;
    }
}
