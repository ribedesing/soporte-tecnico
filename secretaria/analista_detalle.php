<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requerirRol(['secretaria', 'administrador']);

$idAnalista = (int)($_GET['id'] ?? 0);
if ($idAnalista <= 0) {
    header('Location: analistas.php');
    exit;
}

// Obtener datos del analista
$stmtAnalista = $pdo->prepare("
    SELECT
        u.id_usuarios,
        u.usuario,
        u.fecha_creacion,
        u.ultima_actividad,
        (u.ultima_actividad IS NOT NULL AND u.ultima_actividad >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)) AS en_linea,
        p.nombre,
        p.apellido,
        p.cedula,
        p.correo,
        p.telefono,
        r.nombre AS rol
    FROM usuarios u
    INNER JOIN roles r ON r.id_roles = u.rol_id
    LEFT JOIN perfiles_usuarios p ON p.usuario_id = u.id_usuarios
    WHERE u.id_usuarios = :id
");
$stmtAnalista->execute(['id' => $idAnalista]);
$analista = $stmtAnalista->fetch();

if (!$analista) {
    header('Location: analistas.php');
    exit;
}

// Determinar si está en línea con el mismo reloj usado por MySQL al guardar la actividad.
$enLinea = (bool)$analista['en_linea'];

// Filtros para las hojas del analista
$fEstatus = $_GET['estatus'] ?? '';
$fFecha   = $_GET['fecha'] ?? '';

$where = ['hs.tecnico_id = :tecnico_id'];
$params = ['tecnico_id' => $idAnalista];

if (in_array($fEstatus, ['completado', 'en_proceso', 'pendiente_insumos'], true)) {
    $where[] = 'hs.estatus = :estatus';
    $params['estatus'] = $fEstatus;
}
if ($fFecha) {
    $where[] = 'hs.fecha = :fecha';
    $params['fecha'] = $fFecha;
}

$sql = "
    SELECT
        hs.id_hojas,
        hs.fecha,
        hs.hora_inicio,
        hs.hora_fin,
        hs.tiempo_total_minutos,
        hs.descripcion,
        hs.estatus,
        hs.usuario_atendido_nombre,
        hs.otro_especifique,
        d.nombre AS departamento
    FROM hojas_servicio hs
    JOIN departamentos d ON d.id_departamentos= hs.departamento_solicitante_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY hs.fecha DESC, hs.id_hojas DESC
";

$stmtHojas = $pdo->prepare($sql);
$stmtHojas->execute($params);
$hojas = $stmtHojas->fetchAll();
$pg = paginar($hojas);
$hojas = $pg['items'];

// Obtener tipos de servicio para cada hoja (para mostrarlos en el listado)
$tiposPorHoja = [];
if ($hojas) {
    $ids = array_column($hojas, 'id_hojas');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $q = $pdo->prepare("
        SELECT hst.hoja_id, ts.nombre
        FROM hoja_servicio_tipo hst
        JOIN tipos_servicio ts ON ts.id_servicios = hst.tipo_servicio_id
        WHERE hst.hoja_id IN ($in)
    ");
    $q->execute($ids);
    foreach ($q->fetchAll() as $r) {
        $tiposPorHoja[$r['hoja_id']][] = $r['nombre'];
    }
}

$seccion_activa = 'analistas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analista: <?= h($analista['usuario']) ?> | Soporte Técnico</title>
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
            <!-- Botón volver -->
            <div class="mb-3">
                <a href="analistas.php" class="btn btn-outline-brand btn-sm">
                    <i class="bi bi-arrow-left"></i> Volver a analistas
                </a>
            </div>

            <!-- Datos del analista -->
            <div class="card-panel mb-4">
                <div class="panel-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h2 class="fs-5 fw-bold">
                                <?= h(trim($analista['nombre'] . ' ' . $analista['apellido'])) ?: 'Sin nombre completo' ?>
                                <span class="badge <?= $enLinea ? 'badge-completado' : 'badge-pendiente' ?> ms-2">
                                    <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>
                                    <?= $enLinea ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </h2>
                            <p class="text-muted small mb-1">
                                <i class="bi bi-person"></i> Usuario: <strong><?= h($analista['usuario']) ?></strong>
                            </p>
                            <p class="text-muted small mb-1">
                                <i class="bi bi-envelope"></i> Correo: <?= h($analista['correo'] ?? 'No registrado') ?>
                            </p>
                            <p class="text-muted small mb-1">
                                <i class="bi bi-phone"></i> Teléfono: <?= h($analista['telefono'] ?? 'No registrado') ?>
                            </p>
                            <p class="text-muted small mb-0">
                                <i class="bi bi-calendar3"></i> Registrado: <?= date('d/m/Y', strtotime($analista['fecha_creacion'])) ?>
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <span class="badge bg-light text-dark border"><?= h($analista['rol']) ?></span>
                            <p class="text-muted small mt-2">
                                <i class="bi bi-people"></i> Total hojas: <?= count($hojas) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros y listado de hojas -->
            <div class="card-panel">
                <div class="panel-head">
                    <h2>Hojas de servicio</h2>
                </div>
                <div class="panel-body pb-0">
                    <form class="row g-2 mb-3 secretaria-detalle-filtros">
                        <div class="col-md-4">
                            <select name="estatus" class="form-select form-select-sm" data-csp-submit-change>
                                <option value="">Todos los estatus</option>
                                <option value="completado" <?= $fEstatus === 'completado' ? 'selected' : '' ?>>Completado</option>
                                <option value="en_proceso" <?= $fEstatus === 'en_proceso' ? 'selected' : '' ?>>En proceso</option>
                                <option value="pendiente_insumos" <?= $fEstatus === 'pendiente_insumos' ? 'selected' : '' ?>>Pendiente insumos</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="date" name="fecha" value="<?= h($fFecha) ?>" class="form-control form-control-sm" data-csp-submit-change>
                        </div>
                        <div class="col-md-4">
                            <a href="analista_detalle.php?id=<?= $idAnalista ?>" class="btn btn-sm btn-outline-secondary w-100">Limpiar filtros</a>
                        </div>
                    </form>
                </div>
                <?php if (!$hojas): ?>
                    <div class="panel-body text-center text-muted py-5">Este analista no tiene hojas de servicio.</div>
                <?php endif; ?>
                <?php foreach ($hojas as $h): ?>
                    <?php
                        [$claseBadge, $tituloBadge] = badgeEstatus($h['estatus']);
                        $hid = (int)$h['id_hojas'];
                        $tipos = $tiposPorHoja[$hid] ?? [];
                    ?>
                    <div class="ticket-card mb-2" style="cursor:pointer" data-csp-href="ver_hoja.php?id=<?= $hid ?>">
                        <div class="ticket-stub <?= stubEstatus($h['estatus']) ?>">
                            <span>N° <?= numeroHoja($hid) ?></span>
                        </div>
                        <div class="ticket-body">
                            <div class="t-top">
                                <p class="t-title mb-1"><?= h(implode(' / ', $tipos)) ?: 'Servicio' ?> — <?= h($h['departamento']) ?></p>
                                <span class="<?= $claseBadge ?> badge"><?= $tituloBadge ?></span>
                            </div>
                            <div class="t-meta mb-1">
                                <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($h['fecha'])) ?>
                                <span class="mx-1">·</span>
                                <i class="bi bi-clock me-1"></i><?= substr($h['hora_inicio'], 0, 5) ?>–<?= $h['hora_fin'] ? substr($h['hora_fin'], 0, 5) : '—' ?>
                                (<?= minutosATexto($h['tiempo_total_minutos']) ?>)
                            </div>
                            <div class="t-meta"><i class="bi bi-tools me-1"></i><?= h(implode(', ', $tipos)) ?: 'Sin tipo registrado' ?></div>
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