<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requerirRol(['analista']);
$u = usuarioActual();

// KPIs del mes actual para este técnico
$kpi = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(estatus = 'completado') AS completadas,
        SUM(estatus = 'en_proceso') AS en_proceso,
        SUM(estatus = 'pendiente_insumos') AS pendientes
    FROM hojas_servicio
    WHERE tecnico_id = :tid AND DATE_FORMAT(fecha, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
");
$kpi->execute(['tid' => $u['id']]);
$kpi = $kpi->fetch();

// Rendimiento del mes
$rend = $pdo->prepare("
    SELECT
        ROUND(100 * SUM(estatus = 'completado') / NULLIF(COUNT(*), 0), 0) AS pct_cumplimiento,
        ROUND(AVG(tiempo_total_minutos), 0) AS tiempo_prom
    FROM hojas_servicio
    WHERE tecnico_id = :tid AND DATE_FORMAT(fecha, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
");
$rend->execute(['tid' => $u['id']]);
$rend = $rend->fetch();

// Distribución de hojas por estatus en el mes actual
$resumenEstatus = $pdo->prepare("
  SELECT estatus, COUNT(*) AS total
  FROM hojas_servicio
  WHERE tecnico_id = :tid AND DATE_FORMAT(fecha, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
  GROUP BY estatus
");
$resumenEstatus->execute(['tid' => $u['id']]);
$resumenEstatus = $resumenEstatus->fetchAll();
$estatusGrafico = [
  'completado' => 0,
  'en_proceso' => 0,
  'pendiente_insumos' => 0,
];
foreach ($resumenEstatus as $fila) {
  if (array_key_exists($fila['estatus'], $estatusGrafico)) {
    $estatusGrafico[$fila['estatus']] = (int)$fila['total'];
  }
}

// Servicio más solicitado
$servicioTop = $pdo->prepare("
    SELECT ts.nombre, COUNT(*) AS total
    FROM hoja_servicio_tipo hst
    JOIN hojas_servicio hs ON hs.id_hojas = hst.hoja_id
    JOIN tipos_servicio ts ON ts.id_servicios = hst.tipo_servicio_id
    WHERE hs.tecnico_id = :tid AND DATE_FORMAT(hs.fecha, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
    GROUP BY ts.id_servicios ORDER BY total DESC LIMIT 1
");
$servicioTop->execute(['tid' => $u['id']]);
$servicioTop = $servicioTop->fetch();

// Últimas 3 hojas
$hojas = $pdo->prepare("
  SELECT hs.id_hojas, hs.*, d.nombre AS departamento
    FROM hojas_servicio hs
  JOIN departamentos d ON d.id_departamentos = hs.departamento_solicitante_id
    WHERE hs.tecnico_id = :tid
  ORDER BY hs.fecha DESC, hs.id_hojas DESC
    LIMIT 3
");
$hojas->execute(['tid' => $u['id']]);
$hojas = $hojas->fetchAll();

// Tickets abiertos disponibles para el analista + tickets asignados a él
$ticketsQ = $pdo->prepare("
    SELECT t.*, d.nombre AS departamento,
           TRIM(CONCAT(COALESCE(p.nombre,''),' ',COALESCE(p.apellido,''))) AS solicitante,
           TRIM(CONCAT(COALESCE(ap.nombre,''),' ',COALESCE(ap.apellido,''))) AS analista
    FROM tickets t
    JOIN departamentos d ON d.id_departamentos = t.departamento_id
    JOIN usuarios su ON su.id_usuarios = t.solicitante_id
    LEFT JOIN perfiles_usuarios p ON p.usuario_id = su.id_usuarios
    LEFT JOIN usuarios au ON au.id_usuarios = t.analista_id
    LEFT JOIN perfiles_usuarios ap ON ap.usuario_id = au.id_usuarios
    WHERE t.estado IN ('pendiente','asignado','en_proceso')
      AND (t.analista_id IS NULL OR t.analista_id = :tid)
    ORDER BY CASE WHEN t.analista_id IS NULL THEN 0 ELSE 1 END, t.fecha_creacion DESC
    LIMIT 5
");
$ticketsQ->execute(['tid' => $u['id']]);
$ticketsAbiertos = $ticketsQ->fetchAll();
$ticketsPendientes = 0;
foreach ($ticketsAbiertos as $tt) { if ($tt['analista_id'] === null) $ticketsPendientes++; }

// Tipos de servicio de cada hoja
$tiposPorHoja = [];
if ($hojas) {
    $ids = array_column($hojas, 'id_hojas');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $q = $pdo->prepare("SELECT hst.hoja_id, ts.nombre FROM hoja_servicio_tipo hst JOIN tipos_servicio ts ON ts.id_servicios = hst.tipo_servicio_id WHERE hst.hoja_id IN ($in)");
    $q->execute($ids);
    foreach ($q->fetchAll() as $r) {
        $tiposPorHoja[$r['hoja_id']][] = $r['nombre'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mi panel · Técnico | Soporte Técnico</title>
<link rel="icon" href="../assets/img/logo.png">
<link rel="manifest" href="../manifest.json">
<meta name="theme-color" content="#0A3F3A">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
      <?php if (!empty($_SESSION['hoja_en_curso'])): $ec = $_SESSION['hoja_en_curso']; ?>
        <div class="alert d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4" style="background:var(--amber-bg);border:none;">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-record-circle-fill text-danger"></i>
            <div><strong>Tienes un servicio en curso</strong> desde las <?= substr($ec['hora_inicio'],0,5) ?>. Complétalo para que quede registrado.</div>
          </div>
          <a href="hoja-servicio.php" class="btn btn-brand btn-sm">Continuar y finalizar</a>
        </div>
      <?php elseif (isset($_GET['guardado'])): ?>
        <div class="alert alert-success d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
          <span><i class="bi bi-check2-circle me-1"></i>Hoja de servicio guardada correctamente.</span>
          <a href="ver_hoja.php?id=<?= (int)$_GET['guardado'] ?>" class="btn btn-outline-brand btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>Ver / Exportar PDF</a>
        </div>
      <?php endif; ?>

      <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="kpi-card">
          <div class="kpi-icon" style="background:var(--teal-100);color:var(--teal-700);"><i class="bi bi-clipboard-check"></i></div>
          <div class="kpi-label mt-2">Hojas de este mes</div><div class="kpi-value"><?= (int)($kpi['total'] ?? 0) ?></div>
        </div></div>
        <div class="col-6 col-lg-3"><div class="kpi-card">
          <div class="kpi-icon" style="background:#EAF8EE;color:var(--green-600);"><i class="bi bi-check2-circle"></i></div>
          <div class="kpi-label mt-2">Completadas</div><div class="kpi-value"><?= (int)($kpi['completadas'] ?? 0) ?></div>
        </div></div>
        <div class="col-6 col-lg-3"><div class="kpi-card">
          <div class="kpi-icon" style="background:var(--amber-bg);color:#8A5A12;"><i class="bi bi-hourglass-split"></i></div>
          <div class="kpi-label mt-2">En proceso</div><div class="kpi-value"><?= (int)($kpi['en_proceso'] ?? 0) ?></div>
        </div></div>
        <div class="col-6 col-lg-3"><div class="kpi-card">
          <div class="kpi-icon" style="background:var(--red-bg);color:var(--red);"><i class="bi bi-box-seam"></i></div>
          <div class="kpi-label mt-2">Pend. por insumos</div><div class="kpi-value"><?= (int)($kpi['pendientes'] ?? 0) ?></div>
        </div></div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-lg-8">
          <div class="card-panel h-100">
            <div class="panel-head"><h2>Estado de mis hojas de este mes</h2></div>
            <div class="panel-body">
              <div style="height:240px;position:relative;">
                <canvas id="estatusChart" aria-label="Hojas de servicio por estatus"></canvas>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="card-panel h-100">
            <div class="panel-head">
              <h2><i class="bi bi-bell me-1"></i> Tickets nuevos</h2>
              <span class="badge <?= $ticketsPendientes ? 'text-bg-danger' : 'text-bg-secondary' ?>"><?= $ticketsPendientes ?></span>
            </div>
            <div class="panel-body">
              <?php if (!$ticketsAbiertos): ?>
                <p class="text-muted small mb-0">No tienes tickets pendientes de atención.</p>
              <?php else: ?>
                <?php foreach ($ticketsAbiertos as $tt): ?>
                  <a href="ver_ticket.php?id=<?= (int)$tt['id_tickets'] ?>" class="d-block text-decoration-none text-reset border rounded-3 p-3 mb-2">
                    <div class="d-flex justify-content-between gap-2">
                      <strong class="small">#<?= str_pad((string)$tt['id_tickets'], 6, '0', STR_PAD_LEFT) ?></strong>
                      <?php if ($tt['analista_id'] === null): ?><span class="badge badge-pendiente">Nuevo</span><?php else: ?><span class="badge badge-proceso">Asignado</span><?php endif; ?>
                    </div>
                    <div class="fw-semibold mt-1 small"><?= h($tt['departamento']) ?></div>
                    <div class="small text-muted"><?= h($tt['titulo']) ?></div>
                  </a>
                <?php endforeach; ?>
                <a href="tickets.php" class="small fw-semibold">Ver todos los tickets</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-8">
          <div class="card-panel">
            <div class="panel-head">
              <h2>Mis últimas hojas de servicio</h2>
              <a href="mis_hojas.php" class="small" style="color:var(--teal-700);font-weight:600;">Ver todas</a>
            </div>
            <div class="panel-body">
              <?php if (!$hojas): ?>
                <p class="text-muted small mb-0">Aún no has registrado hojas de servicio. <a href="iniciar-servicio.php">Inicia la primera</a>.</p>
              <?php endif; ?>
              <?php foreach ($hojas as $hoja): [$claseBadge, $tituloBadge] = badgeEstatus($hoja['estatus']); ?>
              <a href="ver_hoja.php?id=<?= $hoja['id_hojas'] ?>" class="ticket-card text-decoration-none text-reset">
                <div class="ticket-stub <?= stubEstatus($hoja['estatus']) ?>"><span>N° <?= numeroHoja($hoja['id_hojas']) ?></span></div>
                <div class="ticket-body">
                  <div class="t-top">
                    <p class="t-title"><?= h(implode(' / ', $tiposPorHoja[$hoja['id_hojas']] ?? [])) ?: 'Servicio' ?> — <?= h($hoja['departamento']) ?></p>
                    <span class="<?= $claseBadge ?> badge"><?= $tituloBadge ?></span>
                  </div>
                  <div class="t-meta">
                    <i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($hoja['fecha'])) ?>
                    &nbsp;·&nbsp; <i class="bi bi-clock"></i> <?= substr($hoja['hora_inicio'],0,5) ?>–<?= $hoja['hora_fin'] ? substr($hoja['hora_fin'],0,5) : '—' ?>
                    (<?= minutosATexto($hoja['tiempo_total_minutos']) ?>)
                  </div>
                </div>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="col-lg-4 analista-secondary">
          <div class="card-panel mb-3">
            <div class="panel-head"><h2>Mi rendimiento del mes</h2></div>
            <div class="panel-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small text-muted">% de cumplimiento</span>
                <span class="fw-bold" style="color:var(--green-600);"><?= (int)($rend['pct_cumplimiento'] ?? 0) ?>%</span>
              </div>
              <div class="progress mb-3" style="height:8px;border-radius:6px;">
                <div class="progress-bar" style="width:<?= (int)($rend['pct_cumplimiento'] ?? 0) ?>%;background:var(--green-600);"></div>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small text-muted">Tiempo promedio de atención</span>
                <span class="fw-bold"><?= minutosATexto($rend['tiempo_prom'] !== null ? (int)$rend['tiempo_prom'] : null) ?></span>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <span class="small text-muted">Servicio más solicitado</span>
                <span class="fw-bold"><?= h($servicioTop['nombre'] ?? '—') ?></span>
              </div>
            </div>
          </div>
          <div class="card-panel">
            <div class="panel-head"><h2>Recordatorio</h2></div>
            <div class="panel-body">
              <p class="small text-muted mb-3">Toda hoja de servicio debe quedar con la firma o el documento adjunto del usuario atendido antes de guardarse.</p>
              <a href="iniciar-servicio.php" class="btn btn-brand btn-sm w-100"><i class="bi bi-play-circle me-1"></i>Iniciar nuevo servicio</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" integrity="sha384-NrKB+u6Ts6AtkIhwPixiKTzgSKNblyhlk0Sohlgar9UHUBzai/sgnNNWWd291xqt" crossorigin="anonymous"></script>
<script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
document.getElementById('sidebarToggle')?.addEventListener('click',()=>document.getElementById('sidebar').classList.toggle('show'));
new Chart(document.getElementById('estatusChart'), {
  type: 'doughnut',
  data: {
    labels: ['Completadas', 'En proceso', 'Pendientes por insumos'],
    datasets: [{
      data: <?= json_encode(array_values($estatusGrafico), JSON_NUMERIC_CHECK) ?>,
      backgroundColor: ['#2E9B57', '#D99A2B', '#D95C5C'],
      borderWidth: 0
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' } }
  }
});
</script>
<?php include __DIR__ . '/../includes/alert_modal.php'; ?>
</body>
</html>