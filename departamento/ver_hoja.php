<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requerirRol(['departamento']);
requerirCorreoConfirmado($pdo);
$u = usuarioActual();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT hs.*, d.nombre AS departamento,
    TRIM(CONCAT(COALESCE(p.nombre, ''), ' ', COALESCE(p.apellido, ''))) AS tecnico
    FROM hojas_servicio hs
    JOIN departamentos d ON d.id_departamentos = hs.departamento_solicitante_id
    JOIN usuarios tu ON tu.id_usuarios = hs.tecnico_id
    LEFT JOIN perfiles_usuarios p ON p.usuario_id = tu.id_usuarios
    WHERE hs.id_hojas = ? AND hs.departamento_solicitante_id = ?");
$stmt->execute([$id, $u['departamento_id']]);
$hoja = $stmt->fetch();
if (!$hoja) {
    http_response_code(404);
    die('Hoja de servicio no encontrada.');
}

$tiposStmt = $pdo->prepare("SELECT ts.id_servicios, ts.codigo, ts.nombre,
    (hst.hoja_id IS NOT NULL) AS marcado
    FROM tipos_servicio ts
    LEFT JOIN hoja_servicio_tipo hst ON hst.tipo_servicio_id = ts.id_servicios AND hst.hoja_id = ?
    ORDER BY ts.codigo");
$tiposStmt->execute([$id]);
$tipos = $tiposStmt->fetchAll();

$evidenciaStmt = $pdo->prepare('SELECT archivo FROM hoja_servicio_evidencias WHERE hoja_id = ? LIMIT 1');
$evidenciaStmt->execute([$id]);
$evidencia = $evidenciaStmt->fetchColumn() ?: null;
$rutaBase = '../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hoja N° <?= numeroHoja($id) ?> | Soporte Técnico</title>
<link rel="icon" href="../assets/img/logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main">
<?php include __DIR__ . '/../includes/topbar.php'; ?>
<div class="content worksheet-page">
<?php require __DIR__ . '/../includes/vista_hoja.php'; ?>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="../assets/js/csp-handlers.js" defer></script></body>
</html>
