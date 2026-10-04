<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requerirRol(['departamento']);
requerirCorreoConfirmado($pdo);
$u = usuarioActual();
if (!$u['departamento_id']) {
    die('Tu cuenta no tiene un departamento asociado. Contacta al administrador.');
}

$stmt = $pdo->prepare("SELECT hs.*, d.nombre AS departamento,
    TRIM(CONCAT(COALESCE(p.nombre, ''), ' ', COALESCE(p.apellido, ''))) AS tecnico
    FROM hojas_servicio hs
    JOIN departamentos d ON d.id_departamentos = hs.departamento_solicitante_id
    JOIN usuarios tu ON tu.id_usuarios = hs.tecnico_id
    LEFT JOIN perfiles_usuarios p ON p.usuario_id = tu.id_usuarios
    WHERE hs.departamento_solicitante_id = ?
    ORDER BY hs.fecha DESC, hs.id_hojas DESC");
$stmt->execute([$u['departamento_id']]);
$hojas = $stmt->fetchAll();
$pg = paginar($hojas);
$totalHojas = $pg['total'];
$hojas = $pg['items'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hojas de servicio | Mi oficina</title>
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
<div class="content">
  <div class="card-panel">
    <div class="panel-head"><h2>Hojas de servicio</h2><span class="text-muted small"><?= $totalHojas ?> registros</span></div>
    <div class="table-responsive"><table class="table align-middle mb-0 mobile-stack-table"><thead style="background:var(--paper);"><tr class="small text-muted text-uppercase"><th class="ps-3">Hoja</th><th>Ticket</th><th>Fecha</th><th>Técnico</th><th>Usuario atendido</th><th class="pe-3">Estado</th></tr></thead><tbody>
    <?php foreach ($hojas as $hoja): [$clase, $texto] = badgeEstatus($hoja['estatus']); ?>
      <tr data-csp-href="ver_hoja.php?id=<?= (int)$hoja['id_hojas'] ?>" style="cursor:pointer"><td data-label="Hoja" class="ps-3 fw-semibold"><?= numeroHoja((int)$hoja['id_hojas']) ?></td><td data-label="Ticket"><?= !empty($hoja['ticket_id']) ? '#' . str_pad((string)$hoja['ticket_id'], 6, '0', STR_PAD_LEFT) : '—' ?></td><td data-label="Fecha"><?= date('d/m/Y', strtotime($hoja['fecha'])) ?></td><td data-label="Técnico"><?= h($hoja['tecnico'] ?: '—') ?></td><td data-label="Usuario atendido"><?= h($hoja['usuario_atendido_nombre']) ?></td><td data-label="Estado" class="pe-3"><span class="<?= $clase ?> badge"><?= $texto ?></span></td></tr>
    <?php endforeach; ?>
    <?php if (!$hojas): ?><tr><td colspan="6" class="text-center text-muted py-4">Todavía no hay hojas de servicio para este departamento.</td></tr><?php endif; ?>
    </tbody></table></div>
    <div class="panel-body"><?= renderPaginacion($pg) ?></div>
  </div>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">document.getElementById('sidebarToggle')?.addEventListener('click',()=>document.getElementById('sidebar').classList.toggle('show'));</script>
<script src="../assets/js/csp-handlers.js" defer></script></body>
</html>
