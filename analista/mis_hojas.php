<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requerirRol(['analista']);
$u = usuarioActual();

$estatusFiltro = $_GET['estatus'] ?? '';

$sql = "
    SELECT
        hs.id_hojas,
        hs.*,
        d.nombre AS departamento
    FROM hojas_servicio hs
    JOIN departamentos d
        ON d.id_departamentos = hs.departamento_solicitante_id
    WHERE hs.tecnico_id = :tid
";

$params = ['tid' => $u['id']];

if (in_array($estatusFiltro, ['completado', 'en_proceso', 'pendiente_insumos'], true)) {
    $sql .= " AND hs.estatus = :estatus";
    $params['estatus'] = $estatusFiltro;
}

$sql .= " ORDER BY hs.fecha DESC, hs.id_hojas DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$hojas = $stmt->fetchAll();
$pg = paginar($hojas);
$hojas = $pg['items'];

/* ---------- Tipos por hoja ---------- */
$tiposPorHoja = [];

if ($hojas) {
    $ids = array_column($hojas, 'id_hojas');   // ← PK real
    $ids = array_map('intval', $ids);

    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));

        $q = $pdo->prepare("
            SELECT hst.hoja_id, ts.nombre
            FROM hoja_servicio_tipo hst
            JOIN tipos_servicio ts
                ON ts.id_servicios = hst.tipo_servicio_id
            WHERE hst.hoja_id IN ($in)
        ");
        $q->execute($ids);

        foreach ($q->fetchAll() as $r) {
            $tiposPorHoja[$r['hoja_id']][] = $r['nombre'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mis hojas de servicio | Soporte Técnico</title>
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
      <form method="get" class="mb-3">
        <select name="estatus" class="form-select form-select-sm w-auto" aria-label="Filtrar hojas por estado" data-csp-submit-change>
          <option value="" <?= $estatusFiltro==='' ? 'selected' : '' ?>>Todas</option>
          <option value="completado" <?= $estatusFiltro==='completado' ? 'selected' : '' ?>>Completadas</option>
          <option value="en_proceso" <?= $estatusFiltro==='en_proceso' ? 'selected' : '' ?>>En proceso</option>
          <option value="pendiente_insumos" <?= $estatusFiltro==='pendiente_insumos' ? 'selected' : '' ?>>Pend. insumos</option>
        </select>
      </form>

      <?php if (!$hojas): ?>
        <div class="card-panel">
          <div class="panel-body text-center text-muted py-5">
            No hay hojas de servicio con este filtro.
          </div>
        </div>
      <?php endif; ?>

      <?php foreach ($hojas as $hoja): ?>
        <?php
          [$claseBadge, $tituloBadge] = badgeEstatus($hoja['estatus']);
          $hid = (int)$hoja['id_hojas'];
        ?>

        <div class="ticket-card text-decoration-none text-reset"
             style="cursor:pointer"
             data-csp-href="ver_hoja.php?id=<?= $hid ?>">

          <div class="ticket-stub <?= stubEstatus($hoja['estatus']) ?>">
            <span>N° <?= numeroHoja($hid) ?></span>
          </div>

          <div class="ticket-body">
            <div class="t-top">
              <p class="t-title">
                <?= h(implode(' / ', $tiposPorHoja[$hid] ?? [])) ?: 'Servicio' ?>
                — <?= h($hoja['departamento']) ?>
              </p>
              <span class="<?= $claseBadge ?> badge"><?= $tituloBadge ?></span>
            </div>

            <div class="t-meta d-flex align-items-center justify-content-between gap-2 flex-wrap">
              <span>
                <i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($hoja['fecha'])) ?>
                &nbsp;·&nbsp;
                <i class="bi bi-clock"></i>
                <?= substr($hoja['hora_inicio'], 0, 5) ?>–<?= $hoja['hora_fin'] ? substr($hoja['hora_fin'], 0, 5) : '—' ?>
                (<?= minutosATexto($hoja['tiempo_total_minutos']) ?>)
              </span>

              <?php if (in_array($hoja['estatus'], ['en_proceso', 'pendiente_insumos'], true)): ?>
                <a href="continuar_servicio.php?id=<?= $hid ?>"
                   class="btn btn-brand btn-sm py-1 px-2"
                   style="font-size:.72rem;"
                   data-csp-stop-propagation>
                  <i class="bi bi-check2-circle me-1"></i>Culminar servicio
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

      <?= renderPaginacion($pg) ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
  document.getElementById('sidebarToggle')
    ?.addEventListener('click', () =>
      document.getElementById('sidebar').classList.toggle('show')
    );
</script>
<script src="../assets/js/csp-handlers.js" defer></script></body>
</html>