<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requerirRol(['secretaria', 'administrador']);
$u = usuarioActual();

// ============================================================
// FILTROS
// ============================================================
$fTecnico = (int)($_GET['tecnico_id'] ?? 0);
$fDepto   = (int)($_GET['departamento_id'] ?? 0);
$fEstatus = $_GET['estatus'] ?? '';
$fFecha   = $_GET['fecha'] ?? '';

$where = [];
$params = [];

if ($fTecnico) {
    $where[] = 'hs.tecnico_id = :tecnico_id';
    $params['tecnico_id'] = $fTecnico;
}
if ($fDepto) {
    $where[] = 'hs.departamento_solicitante_id = :depto_id';
    $params['depto_id'] = $fDepto;
}
if (in_array($fEstatus, ['completado', 'en_proceso', 'pendiente_insumos'], true)) {
    $where[] = 'hs.estatus = :estatus';
    $params['estatus'] = $fEstatus;
}
if ($fFecha) {
    $where[] = 'hs.fecha = :fecha';
    $params['fecha'] = $fFecha;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ============================================================
// LISTADO DE HOJAS (con paginación simple, sin paginación, todas)
// ============================================================
$stmt = $pdo->prepare("
    SELECT
        hs.*,
        d.nombre AS departamento,
        TRIM(CONCAT(COALESCE(p.nombre, ''), ' ', COALESCE(p.apellido, ''))) AS tecnico
    FROM hojas_servicio hs
    JOIN departamentos d ON d.id_departamentos = hs.departamento_solicitante_id
    JOIN usuarios u ON u.id_usuarios = hs.tecnico_id
    LEFT JOIN perfiles_usuarios p ON p.usuario_id = u.id_usuarios
    $whereSql
    ORDER BY hs.fecha DESC, hs.id_hojas DESC
");
$stmt->execute($params);
$hojas = $stmt->fetchAll();
$pg = paginar($hojas);
$totalHojas = $pg['total'];
$hojas = $pg['items'];

$tiposPorHoja = [];
if ($hojas) {
    $ids = array_map('intval', array_column($hojas, 'id_hojas'));
    $in = implode(',', array_fill(0, count($ids), '?'));
    $qTipos = $pdo->prepare("SELECT hst.hoja_id, ts.nombre FROM hoja_servicio_tipo hst JOIN tipos_servicio ts ON ts.id_servicios = hst.tipo_servicio_id WHERE hst.hoja_id IN ($in)");
    $qTipos->execute($ids);
    foreach ($qTipos->fetchAll() as $tipo) {
        $tiposPorHoja[$tipo['hoja_id']][] = $tipo['nombre'];
    }
}

// ============================================================
// CATÁLOGOS PARA FILTROS
// ============================================================
$tecnicos = $pdo->query("
    SELECT
        u.id_usuarios AS id,
        TRIM(CONCAT(COALESCE(p.nombre, ''), ' ', COALESCE(p.apellido, ''))) AS nombre_completo
    FROM usuarios u
    JOIN roles r ON r.id_roles = u.rol_id
    LEFT JOIN perfiles_usuarios p ON p.usuario_id = u.id_usuarios
    WHERE r.nombre = 'analista'
    ORDER BY p.nombre, p.apellido
")->fetchAll();

$departamentos = $pdo->query("
    SELECT id_departamentos, nombre
    FROM departamentos
    ORDER BY nombre
")->fetchAll();

$seccion_activa = 'hojas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hojas de servicio · Secretaría | Soporte Técnico</title>
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

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>
        <div class="content">

            <div class="card-panel">
                <div class="panel-head">
                    <h2>Todas las hojas de servicio</h2>
                    <span class="text-muted small"><?= $totalHojas ?> registros</span>
                </div>

                <!-- FILTROS -->
                <div class="panel-body pb-0">
                    <form class="row g-2 mb-3 secretaria-hojas-filtros">
                        <!-- Técnico -->
                        <div class="col-md-3">
                            <select name="tecnico_id" class="form-select form-select-sm" data-csp-submit-change>
                                <option value="0">Todos los técnicos</option>
                                <?php foreach ($tecnicos as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>" <?= $fTecnico === (int)$t['id'] ? 'selected' : '' ?>>
                                        <?= h($t['nombre_completo'] ?: 'Sin nombre') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Departamento -->
                        <div class="col-md-3">
                            <select name="departamento_id" class="form-select form-select-sm" data-csp-submit-change>
                                <option value="0">Todos los departamentos</option>
                                <?php foreach ($departamentos as $d): ?>
                                    <option value="<?= (int)$d['id_departamentos'] ?>" <?= $fDepto === (int)$d['id_departamentos'] ? 'selected' : '' ?>>
                                        <?= h($d['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Estatus -->
                        <div class="col-md-3">
                            <select name="estatus" class="form-select form-select-sm" data-csp-submit-change>
                                <option value="">Todos los estatus</option>
                                <option value="completado" <?= $fEstatus === 'completado' ? 'selected' : '' ?>>Completado</option>
                                <option value="en_proceso" <?= $fEstatus === 'en_proceso' ? 'selected' : '' ?>>En proceso</option>
                                <option value="pendiente_insumos" <?= $fEstatus === 'pendiente_insumos' ? 'selected' : '' ?>>Pendiente insumos</option>
                            </select>
                        </div>
                        <!-- Fecha -->
                        <div class="col-md-3">
                            <input type="date" name="fecha" value="<?= h($fFecha) ?>" class="form-control form-control-sm" data-csp-submit-change>
                        </div>
                    </form>
                </div>

                <?php if (!$hojas): ?>
                    <div class="panel-body text-center text-muted py-5">No hay hojas de servicio con estos filtros.</div>
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
                                    <i class="bi bi-person"></i> <?= h($hoja['tecnico'] ?: 'Sin técnico') ?>
                                    &nbsp;·&nbsp;
                                    <i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($hoja['fecha'])) ?>
                                    &nbsp;·&nbsp;
                                    <i class="bi bi-clock"></i> <?= substr($hoja['hora_inicio'], 0, 5) ?>–<?= $hoja['hora_fin'] ? substr($hoja['hora_fin'], 0, 5) : '—' ?>
                                    (<?= minutosATexto($hoja['tiempo_total_minutos']) ?>)
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="panel-body"><?= renderPaginacion($pg) ?></div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('show');
});
</script>
<script src="../assets/js/csp-handlers.js" defer></script></body>
</html>