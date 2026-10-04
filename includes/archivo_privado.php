<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    exit;
}

requerirRol(['administrador', 'secretaria', 'analista', 'tecnico', 'departamento']);
$usuario = usuarioActual();
$tipo = $_GET['tipo'] ?? '';
$id = (int)($_GET['id'] ?? 0);

$noEncontrado = static function (): never {
    http_response_code(404);
    exit;
};

if ($id <= 0) {
    $noEncontrado();
}

$carpetas = [
    'avatar' => 'avatars',
    'evidencia' => 'evidencias',
    'firma' => 'firmas',
    'adjunto' => 'adjuntos',
];
$tiposMime = [
    'avatar' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    'evidencia' => ['image/jpeg', 'image/png', 'image/webp'],
    'firma' => ['image/png'],
    'adjunto' => ['image/jpeg', 'image/png', 'application/pdf'],
];

if (!isset($carpetas[$tipo])) {
    $noEncontrado();
}

if ($tipo === 'avatar') {
    if ($id !== (int)$usuario['id']) {
        $noEncontrado();
    }
    $stmt = $pdo->prepare('SELECT avatar AS archivo FROM perfiles_usuarios WHERE usuario_id = ? LIMIT 1');
    $stmt->execute([$id]);
    $avatar = $stmt->fetchColumn();
    $archivo = is_string($avatar) && $avatar !== ''
        ? 'uploads/avatars/' . basename($avatar)
        : null;
} else {
    if ($tipo === 'evidencia') {
        $stmt = $pdo->prepare('SELECT hs.tecnico_id, hs.departamento_solicitante_id, e.archivo FROM hojas_servicio hs JOIN hoja_servicio_evidencias e ON e.hoja_id = hs.id_hojas WHERE hs.id_hojas = ? LIMIT 1');
    } else {
        $columna = $tipo === 'firma' ? 'firma_imagen' : 'documento_adjunto';
        $stmt = $pdo->prepare("SELECT tecnico_id, departamento_solicitante_id, {$columna} AS archivo FROM hojas_servicio WHERE id_hojas = ? LIMIT 1");
    }
    $stmt->execute([$id]);
    $hoja = $stmt->fetch();
    if (!$hoja) {
        $noEncontrado();
    }

    $rol = $usuario['rol'];
    $autorizado = match ($rol) {
        'administrador' => tienePermiso('hojas.ver'),
        'secretaria' => true,
        'analista', 'tecnico' => (int)$hoja['tecnico_id'] === (int)$usuario['id'],
        'departamento' => (int)$hoja['departamento_solicitante_id'] === (int)$usuario['departamento_id'],
        default => false,
    };
    if (!$autorizado) {
        $noEncontrado();
    }
    if ($rol === 'departamento') {
        requerirCorreoConfirmado($pdo);
    }
    $archivo = $hoja['archivo'];
}

$prefijo = 'uploads/' . $carpetas[$tipo] . '/';
if (!is_string($archivo) || !str_starts_with($archivo, $prefijo)) {
    $noEncontrado();
}

$nombre = basename($archivo);
$ruta = __DIR__ . '/../uploads/' . $carpetas[$tipo] . '/' . $nombre;
if ($nombre === '' || !is_file($ruta) || !is_readable($ruta)) {
    $noEncontrado();
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($ruta);
if (!in_array($mime, $tiposMime[$tipo], true)) {
    $noEncontrado();
}

header('Content-Type: ' . $mime);
header('Content-Disposition: ' . ($mime === 'application/pdf' ? 'attachment' : 'inline') . '; filename="' . rawurlencode($nombre) . '"');
header('Content-Length: ' . filesize($ruta));
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('Cross-Origin-Resource-Policy: same-origin');
readfile($ruta);