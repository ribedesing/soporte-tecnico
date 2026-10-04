<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requerirRol(['secretaria', 'administrador']);
$seccion_activa = 'tickets';

$estado = $_GET['estado'] ?? '';
$where = '';
$params = [];
$ticketFiltro = (int)($_GET['ticket'] ?? 0);
if ($ticketFiltro > 0) {
  $where = 'WHERE t.id_tickets = ?';
  $params[] = $ticketFiltro;
}
if (in_array($estado, ['pendiente', 'asignado', 'en_proceso', 'completado', 'cancelado'], true)) {
  $where .= $where ? ' AND t.estado = ?' : 'WHERE t.estado = ?';
    $params[] = $estado;
}

$stmt = $pdo->prepare("SELECT t.*, d.nombre AS departamento,
    TRIM(CONCAT(COALESCE(p.nombre, ''), ' ', COALESCE(p.apellido, ''))) AS solicitante,
    TRIM(CONCAT(COALESCE(ap.nombre, ''), ' ', COALESCE(ap.apellido, ''))) AS analista
    FROM tickets t
    JOIN departamentos d ON d.id_departamentos = t.departamento_id
    JOIN usuarios su ON su.id_usuarios = t.solicitante_id
    LEFT JOIN perfiles_usuarios p ON p.usuario_id = su.id_usuarios
    LEFT JOIN usuarios au ON au.id_usuarios = t.analista_id
    LEFT JOIN perfiles_usuarios ap ON ap.usuario_id = au.id_usuarios
    $where
    ORDER BY CASE WHEN t.estado = 'pendiente' THEN 0 WHEN t.estado = 'asignado' THEN 1 WHEN t.estado = 'en_proceso' THEN 2 ELSE 3 END, t.fecha_creacion DESC");
$stmt->execute($params);
$tickets = $stmt->fetchAll();
$pg = paginar($tickets);
$totalTickets = $pg['total'];
$tickets = $pg['items'];

function etiquetaTicket(string $estado): array
{
    return match ($estado) {
        'completado' => ['badge-completado', 'Completado'],
        'en_proceso' => ['badge-proceso', 'En proceso'],
        'asignado' => ['badge-proceso', 'Asignado'],
        'cancelado' => ['badge-pendiente', 'Cancelado'],
        default => ['badge-pendiente', 'Pendiente'],
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tickets | Secretaría</title>
<link rel="icon" href="../assets/img/logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
<?php require __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main">
<?php include __DIR__ . '/../includes/topbar.php'; ?>
<div class="content">
  <div class="card-panel">
    <div class="panel-head">
      <h2>Todos los tickets</h2>
      <span class="text-muted small"><?= $totalTickets ?> registros</span>
    </div>
    <div class="panel-body pb-0">
      <form class="row g-2 mb-3">
        <div class="col-md-4">
          <select name="estado" class="form-select form-select-sm" data-csp-submit-change>
            <option value="">Todos los estados</option>
            <?php foreach (['pendiente' => 'Pendiente', 'asignado' => 'Asignado', 'en_proceso' => 'En proceso', 'completado' => 'Completado', 'cancelado' => 'Cancelado'] as $valor => $texto): ?>
              <option value="<?= $valor ?>" <?= $estado === $valor ? 'selected' : '' ?>><?= $texto ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
    </div>
    <div class="panel-body pt-0">
      <?php foreach ($tickets as $ticket): [$clase, $texto] = etiquetaTicket($ticket['estado']); ?>
        <div class="ticket-card mb-2"
             style="cursor:pointer"
             data-csp-href="ver_ticket.php?id=<?= (int)$ticket['id_tickets'] ?>">
          <div class="ticket-stub <?= stubEstatus($ticket['estado']) ?>">
            <span>#<?= str_pad((string)$ticket['id_tickets'], 6, '0', STR_PAD_LEFT) ?></span>
          </div>
          <div class="ticket-body">
            <div class="t-top">
              <p class="t-title mb-1"><?= h($ticket['titulo']) ?></p>
              <span class="<?= $clase ?> badge"><?= $texto ?></span>
            </div>
            <div class="t-meta mb-1">
              <i class="bi bi-building me-1"></i><?= h($ticket['departamento']) ?>
              <span class="mx-1">·</span>
              <i class="bi bi-person me-1"></i><?= h($ticket['solicitante'] ?: 'Sin solicitante') ?>
            </div>
            <div class="t-meta d-flex justify-content-between gap-2 flex-wrap">
              <span><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])) ?></span>
              <span><i class="bi bi-person-badge me-1"></i>Analista: <?= h($ticket['analista'] ?: 'Sin asignar') ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$tickets): ?>
        <div class="text-center text-muted py-4">No hay tickets con este filtro.</div>
      <?php endif; ?>
      <?= renderPaginacion($pg) ?>
    </div>
  </div>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">document.getElementById('sidebarToggle')?.addEventListener('click',()=>document.getElementById('sidebar').classList.toggle('show'));</script>
<script src="../assets/js/csp-handlers.js" defer></script></body>
</html>
