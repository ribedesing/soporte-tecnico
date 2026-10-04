<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requerirRol(['analista']);
$u = usuarioActual();

$q = $pdo->prepare("SELECT t.*, d.nombre AS departamento,
    TRIM(CONCAT(COALESCE(p.nombre,''),' ',COALESCE(p.apellido,''))) AS solicitante,
    TRIM(CONCAT(COALESCE(ap.nombre,''),' ',COALESCE(ap.apellido,''))) AS analista
    FROM tickets t
    JOIN departamentos d ON d.id_departamentos=t.departamento_id
    JOIN usuarios su ON su.id_usuarios=t.solicitante_id
    LEFT JOIN perfiles_usuarios p ON p.usuario_id=su.id_usuarios
    LEFT JOIN usuarios au ON au.id_usuarios=t.analista_id
    LEFT JOIN perfiles_usuarios ap ON ap.usuario_id=au.id_usuarios
    WHERE t.estado IN ('pendiente','asignado','en_proceso')
    ORDER BY CASE WHEN t.analista_id IS NULL THEN 0 ELSE 1 END, t.fecha_creacion DESC");
$q->execute();
$tickets = $q->fetchAll();
$pg = paginar($tickets);
$totalTickets = $pg['total'];
$tickets = $pg['items'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tickets | Analista</title>
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
<div class="panel-head">
<h2>Tickets de soporte</h2>
<span class="text-muted small"><?= $totalTickets ?> abiertos</span>
</div>
<div class="panel-body">
<?php foreach ($tickets as $t): ?>
<div class="ticket-card mb-2">
<div class="ticket-stub st-proceso"><span>#<?= str_pad((string)$t['id_tickets'], 6, '0', STR_PAD_LEFT) ?></span></div>
<div class="ticket-body">
<div class="t-top">
<p class="t-title mb-1"><?= h($t['titulo']) ?></p>
<span class="badge <?= $t['estado'] === 'pendiente' ? 'badge-pendiente' : 'badge-proceso' ?>"><?= h(ucfirst(str_replace('_', ' ', $t['estado']))) ?></span>
</div>
<div class="t-meta mb-2"><?= h($t['departamento']) ?> · <?= date('d/m/Y H:i', strtotime($t['fecha_creacion'])) ?></div>
<p class="small mb-2"><?= h(mb_strimwidth($t['descripcion'], 0, 180, '…', 'UTF-8')) ?></p>
<?php if (!$t['analista']): ?>
<a href="ver_ticket.php?id=<?= (int)$t['id_tickets'] ?>" class="btn btn-brand btn-sm">Ver y atender</a>
<?php elseif ((int)$t['analista_id'] === (int)$u['id']): ?>
<a href="ver_ticket.php?id=<?= (int)$t['id_tickets'] ?>" class="btn btn-brand btn-sm">Ticket asignado a mí</a>
<?php if ($t['estado'] === 'asignado'): ?>
<a href="iniciar-servicio.php?ticket=<?= (int)$t['id_tickets'] ?>" class="btn btn-outline-brand btn-sm"><i class="bi bi-play-circle me-1"></i>Iniciar servicio</a>
<?php endif; ?>
<?php else: ?>
<span class="badge text-bg-secondary">Ya tomado por <?= h($t['analista'] ?: 'otro analista') ?></span>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
<?php if (!$tickets): ?>
<p class="text-muted mb-0">No hay tickets abiertos.</p>
<?php endif; ?>
<?= renderPaginacion($pg) ?>
</div>
</div>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
