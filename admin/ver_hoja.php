<?php
require_once __DIR__ . '/_layout.php'; requerirPermiso('hojas.ver');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    die('Identificador de hoja de servicio inválido.');
}

$stmt = $pdo->prepare('
    SELECT hs.*, d.nombre AS departamento,
           TRIM(CONCAT(COALESCE(p.nombre, \'\'), \' \', COALESCE(p.apellido, \'\'))) AS tecnico
    FROM hojas_servicio hs
    JOIN departamentos d ON d.id_departamentos = hs.departamento_solicitante_id
    JOIN usuarios u ON u.id_usuarios = hs.tecnico_id
    LEFT JOIN perfiles_usuarios p ON p.usuario_id = u.id_usuarios
    WHERE hs.id_hojas = :id
    LIMIT 1
');
$stmt->execute(['id' => $id]);
$hoja = $stmt->fetch();

if (!$hoja) {
    http_response_code(404);
    die('Hoja de servicio no encontrada.');
}

$tiposStmt = $pdo->prepare('
    SELECT ts.id_servicios, ts.codigo, ts.nombre,
           CASE WHEN hst.hoja_id IS NOT NULL THEN 1 ELSE 0 END AS marcado
    FROM tipos_servicio ts
    LEFT JOIN hoja_servicio_tipo hst
      ON hst.tipo_servicio_id = ts.id_servicios AND hst.hoja_id = :id
    ORDER BY ts.codigo
');
$tiposStmt->execute(['id' => $id]);
$tipos = $tiposStmt->fetchAll();

$evidenciaStmt = $pdo->prepare('
    SELECT archivo FROM hoja_servicio_evidencias WHERE hoja_id = :id LIMIT 1
');
$evidenciaStmt->execute(['id' => $id]);
$evidencia = $evidenciaStmt->fetchColumn() ?: null;

$rutaBase = '../';
adminLayoutStart('Hoja de servicio');
?>
<div class="mb-3 no-print">
  <a href="hojas.php" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Volver a hojas de servicio</a>
</div>
<div class="worksheet-page">
    <?php require __DIR__ . '/../includes/vista_hoja.php'; ?>
</div>
<?php adminLayoutEnd(); ?>

<script src="../assets/js/csp-handlers.js" defer></script>
