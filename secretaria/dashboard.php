<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requerirRol(['secretaria', 'administrador']);

$u = usuarioActual();


// =====================================================
// FILTROS
// =====================================================

$fTecnico = (int)($_GET['tecnico_id'] ?? 0);
$fDepto   = (int)($_GET['departamento_id'] ?? 0);
$fEstatus = $_GET['estatus'] ?? '';
$fFecha   = $_GET['fecha'] ?? '';

$where  = [];
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

$whereSql = $where
    ? ('WHERE ' . implode(' AND ', $where))
    : '';


// =====================================================
// KPIs DEL MES
// =====================================================

$kpi = $pdo->query("
    SELECT

        (
            SELECT COUNT(*)
            FROM usuarios u
            JOIN roles r
                ON r.id_roles = u.rol_id
            WHERE r.nombre = 'analista'
              AND u.ultima_actividad >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ) AS tecnicos_activos,

        SUM(
            DATE_FORMAT(fecha, '%Y-%m')
            = DATE_FORMAT(CURDATE(), '%Y-%m')
        ) AS hojas_mes,

        ROUND(
            AVG(
                CASE
                    WHEN DATE_FORMAT(fecha, '%Y-%m')
                         = DATE_FORMAT(CURDATE(), '%Y-%m')
                    THEN tiempo_total_minutos
                END
            ),
            0
        ) AS tiempo_prom,

        SUM(
            estatus = 'pendiente_insumos'
        ) AS pendientes

    FROM hojas_servicio
")->fetch();


// =====================================================
// LISTADO DE HOJAS DE SERVICIO
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        hs.*,
        d.nombre AS departamento,
        TRIM(
            CONCAT(
                COALESCE(p.nombre, ''),
                ' ',
                COALESCE(p.apellido, '')
            )
        ) AS tecnico
    FROM hojas_servicio hs
    JOIN departamentos d
        ON d.id_departamentos = hs.departamento_solicitante_id
    JOIN usuarios u
        ON u.id_usuarios = hs.tecnico_id
    LEFT JOIN perfiles_usuarios p
        ON p.usuario_id = u.id_usuarios
    $whereSql
    ORDER BY
        hs.fecha DESC,
        hs.id_hojas DESC
    LIMIT 5
");

$stmt->execute($params);

$hojas = $stmt->fetchAll();


// =====================================================
// CATÁLOGO DE TÉCNICOS
// =====================================================

$tecnicos = $pdo->query("
    SELECT
        u.id_usuarios AS id,

        TRIM(
            CONCAT(
                COALESCE(p.nombre, ''),
                ' ',
                COALESCE(p.apellido, '')
            )
        ) AS nombre_completo

    FROM usuarios u

    JOIN roles r
        ON r.id_roles = u.rol_id

    LEFT JOIN perfiles_usuarios p
        ON p.usuario_id = u.id_usuarios

    WHERE r.nombre = 'analista'

    ORDER BY
        p.nombre,
        p.apellido
")->fetchAll();


// =====================================================
// CATÁLOGO DE DEPARTAMENTOS
// =====================================================

$departamentos = $pdo->query("
    SELECT
        id_departamentos,
        nombre

    FROM departamentos

    ORDER BY nombre
")->fetchAll();


// =====================================================
// GRÁFICO: SERVICIOS POR TÉCNICO
// =====================================================

$porTecnico = $pdo->query("
    SELECT

        TRIM(
            CONCAT(
                COALESCE(p.nombre, ''),
                ' ',
                COALESCE(p.apellido, '')
            )
        ) AS nombre,

        COUNT(*) AS total

    FROM hojas_servicio hs

    JOIN usuarios u
        ON u.id_usuarios = hs.tecnico_id

    LEFT JOIN perfiles_usuarios p
        ON p.usuario_id = u.id_usuarios

    WHERE DATE_FORMAT(hs.fecha, '%Y-%m')
          = DATE_FORMAT(CURDATE(), '%Y-%m')

    GROUP BY
        u.id_usuarios,
        p.nombre,
        p.apellido

    ORDER BY total DESC
")->fetchAll();


// =====================================================
// GRÁFICO: TIPOS DE SERVICIO
// =====================================================

$porTipo = $pdo->query("
    SELECT

        ts.nombre,

        COUNT(*) AS total

    FROM hoja_servicio_tipo hst

    JOIN hojas_servicio hs
        ON hs.id_hojas = hst.hoja_id

    JOIN tipos_servicio ts
        ON ts.id_servicios = hst.tipo_servicio_id

    WHERE DATE_FORMAT(hs.fecha, '%Y-%m')
          = DATE_FORMAT(CURDATE(), '%Y-%m')

    GROUP BY
        ts.id_servicios,
        ts.nombre

    ORDER BY total DESC
")->fetchAll();


// =====================================================
// NOTIFICACIONES DE DEMOSTRACIÓN
// =====================================================

$notificacionesDemo = [

    [
        'icono' => 'bi-play-circle-fill',
        'msg' => '<strong>Mandy Peña</strong> inició un servicio en Consultoría Jurídica.',
        'hace' => 'hace 5 min'
    ],

    [
        'icono' => 'bi-check2-circle',
        'msg' => '<strong>José Rivas</strong> finalizó una hoja de servicio en Catastro.',
        'hace' => 'hace 22 min'
    ],

    [
        'icono' => 'bi-play-circle-fill',
        'msg' => '<strong>José Rivas</strong> inició un servicio en RRHH.',
        'hace' => 'hace 40 min'
    ],

];

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Panel general · Secretaría | Soporte Técnico
</title>

<link
    rel="icon"
    href="../assets/img/logo.png"
>

<link
    rel="manifest"
    href="../manifest.json"
>

<meta
    name="theme-color"
    content="#0A3F3A"
>

<link
    href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet"
>

<link
    href="../assets/css/style.css?v=20261001-blue"
    rel="stylesheet"
>

</head>


<body>

<div class="app-shell">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <!-- =====================================================
         CONTENIDO PRINCIPAL
    ====================================================== -->

    <div class="main">
<?php include __DIR__ . '/../includes/topbar.php'; ?>
        <!-- =================================================
             CONTENIDO
        ================================================== -->

        <div class="content">


            <!-- =================================================
                 KPIs
            ================================================== -->

            <div class="row g-3 mb-4">


                <!-- Técnicos activos -->

                <div class="col-6 col-lg-3">

                    <div class="kpi-card">

                        <div
                            class="kpi-icon"
                            style="background:var(--teal-100);color:var(--teal-700);"
                        >
                            <i class="bi bi-people"></i>
                        </div>

                        <div class="kpi-label mt-2">
                            Técnicos activos
                        </div>

                        <div class="kpi-value">
                            <?= (int)$kpi['tecnicos_activos'] ?>
                        </div>

                    </div>

                </div>



                <!-- Hojas este mes -->

                <div class="col-6 col-lg-3">

                    <div class="kpi-card">

                        <div
                            class="kpi-icon"
                            style="background:#EAF8EE;color:var(--green-600);"
                        >
                            <i class="bi bi-clipboard-check"></i>
                        </div>

                        <div class="kpi-label mt-2">
                            Hojas de este mes
                        </div>

                        <div class="kpi-value">
                            <?= (int)$kpi['hojas_mes'] ?>
                        </div>

                    </div>

                </div>



                <!-- Tiempo promedio -->

                <div class="col-6 col-lg-3">

                    <div class="kpi-card">

                        <div
                            class="kpi-icon"
                            style="background:var(--amber-bg);color:#8A5A12;"
                        >
                            <i class="bi bi-stopwatch"></i>
                        </div>

                        <div class="kpi-label mt-2">
                            Tiempo prom. atención
                        </div>

                        <div
                            class="kpi-value"
                            style="font-size:1.4rem;"
                        >
                            <?= minutosATexto(
                                $kpi['tiempo_prom'] !== null
                                    ? (int)$kpi['tiempo_prom']
                                    : null
                            ) ?>
                        </div>

                    </div>

                </div>



                <!-- Pendientes -->

                <div class="col-6 col-lg-3">

                    <div class="kpi-card">

                        <div
                            class="kpi-icon"
                            style="background:var(--red-bg);color:var(--red);"
                        >
                            <i class="bi bi-exclamation-circle"></i>
                        </div>

                        <div class="kpi-label mt-2">
                            Pendientes
                        </div>

                        <div class="kpi-value">
                            <?= (int)$kpi['pendientes'] ?>
                        </div>

                    </div>

                </div>

            </div>



            <!-- =================================================
                 GRÁFICOS
            ================================================== -->

            <div class="row g-3 mb-3">


                <!-- Servicios por técnico -->

                <div class="col-lg-7">

                    <div class="card-panel h-100">

                        <div class="panel-head">

                            <h2>
                                Servicios por técnico — este mes
                            </h2>

                        </div>

                        <div class="panel-body">

                            <canvas
                                id="chartTecnico"
                                height="150"
                            ></canvas>

                        </div>

                    </div>

                </div>



                <!-- Tipos de servicio -->

                <div class="col-lg-5">

                    <div class="card-panel h-100">

                        <div class="panel-head">

                            <h2>
                                Distribución por tipo de servicio
                            </h2>

                        </div>

                        <div class="panel-body">

                            <?php if ($porTipo): ?>
                            <canvas
                                id="chartTipo"
                                height="150"
                            ></canvas>
                            <?php else: ?>
                            <p class="text-muted mb-0 text-center py-4">
                                Todavía no hay hojas de servicio registradas
                                este mes, así que no hay nada que graficar.
                            </p>
                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            </div>



            <!-- =================================================
                 TABLA DE HOJAS
            ================================================== -->

            <div class="card-panel">
                <div class="panel-head">
                    <h2>
                        Hojas de servicio
                    </h2>
                </div>
                <!-- FILTROS -->
                <div class="panel-body pb-0">

                    <form class="row g-2 mb-3 secretaria-dashboard-filtros">


                        <!-- Técnico -->

                        <div class="col-md-3">

                            <select
                                name="tecnico_id"
                                class="form-select form-select-sm"
                                data-csp-submit-change
                            >

                                <option value="0">
                                    Todos los técnicos
                                </option>


                                <?php foreach ($tecnicos as $t): ?>

                                    <option
                                        value="<?= (int)$t['id'] ?>"
                                        <?= $fTecnico === (int)$t['id']
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= h($t['nombre_completo']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>



                        <!-- Departamento -->

                        <div class="col-md-3">

                            <select
                                name="departamento_id"
                                class="form-select form-select-sm"
                                data-csp-submit-change
                            >

                                <option value="0">
                                    Todos los departamentos
                                </option>


                                <?php foreach ($departamentos as $d): ?>

                                    <option
                                        value="<?= (int)$d['id_departamentos'] ?>"
                                        <?= $fDepto === (int)$d['id_departamentos']
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= h($d['nombre']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>



                        <!-- Estatus -->

                        <div class="col-md-3">

                            <select
                                name="estatus"
                                class="form-select form-select-sm"
                                data-csp-submit-change
                            >

                                <option value="">
                                    Todos los estatus
                                </option>

                                <option
                                    value="completado"
                                    <?= $fEstatus === 'completado'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Completado
                                </option>

                                <option
                                    value="en_proceso"
                                    <?= $fEstatus === 'en_proceso'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    En proceso
                                </option>

                                <option
                                    value="pendiente_insumos"
                                    <?= $fEstatus === 'pendiente_insumos'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Pendiente insumos
                                </option>

                            </select>

                        </div>



                        <!-- Fecha -->

                        <div class="col-md-3">

                            <input
                                type="date"
                                name="fecha"
                                value="<?= h($fFecha) ?>"
                                class="form-control form-control-sm"
                                data-csp-submit-change
                            >

                        </div>

                    </form>

                </div>



                <!-- LISTADO DE HOJAS: mismo estilo visual de hojas.php -->
                <div class="panel-body pt-0 secretaria-dashboard-hojas-list">
                    <?php if (!$hojas): ?>
                        <div class="text-center text-muted py-4">No hay hojas de servicio con estos filtros.</div>
                    <?php endif; ?>
                    <?php foreach ($hojas as $hoja): ?>
                        <?php [$claseBadge, $tituloBadge] = badgeEstatus($hoja['estatus']); $hid=(int)$hoja['id_hojas']; ?>
                        <div class="ticket-card text-decoration-none text-reset" style="cursor:pointer" data-csp-href="ver_hoja.php?id=<?= $hid ?>">
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
                                        <i class="bi bi-clock"></i> <?= substr($hoja['hora_inicio'],0,5) ?>–<?= $hoja['hora_fin'] ? substr($hoja['hora_fin'],0,5) : '—' ?>
                                        (<?= minutosATexto($hoja['tiempo_total_minutos']) ?>)
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

    </div>

</div>



<!-- =====================================================
     JAVASCRIPT
====================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
></script>

<script
    src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" integrity="sha384-NrKB+u6Ts6AtkIhwPixiKTzgSKNblyhlk0Sohlgar9UHUBzai/sgnNNWWd291xqt" crossorigin="anonymous"
></script>


<script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">

// =====================================================
// SIDEBAR
// =====================================================

document
    .getElementById('sidebarToggle')
    ?.addEventListener('click', () => {

        document
            .getElementById('sidebar')
            .classList
            .toggle('show');

    });


// =====================================================
// GRÁFICO POR TÉCNICO
// =====================================================

new Chart(
    document.getElementById('chartTecnico'),
    {

        type: 'bar',

        data: {

            labels:
                <?= json_encode(
                    array_column($porTecnico, 'nombre')
                ) ?>,

            datasets: [

                {
                    label: 'Hojas de servicio',

                    data:
                        <?= json_encode(
                            array_map(
                                'intval',
                                array_column(
                                    $porTecnico,
                                    'total'
                                )
                            )
                        ) ?>,

                    backgroundColor: '#0E8C7F',

                    borderRadius: 6,

                    maxBarThickness: 34
                }

            ]

        },

        options: {

            plugins: {

                legend: {
                    display: false
                }

            },

            scales: {

                y: {

                    beginAtZero: true,

                    grid: {
                        color: '#EDEFEE'
                    }

                },

                x: {

                    grid: {
                        display: false
                    }

                }

            }

        }

    }
);


// =====================================================
// GRÁFICO POR TIPO DE SERVICIO
// =====================================================

if (document.getElementById('chartTipo')) {
new Chart(
    document.getElementById('chartTipo'),
    {

        type: 'doughnut',

        data: {

            labels:
                <?= json_encode(
                    array_column($porTipo, 'nombre')
                ) ?>,

            datasets: [

                {

                    data:
                        <?= json_encode(
                            array_map(
                                'intval',
                                array_column(
                                    $porTipo,
                                    'total'
                                )
                            )
                        ) ?>,

                    backgroundColor: [

                        '#0E8C7F',
                        '#2DBE59',
                        '#E8A33D',
                        '#D6534A',
                        '#6FAEA4',
                        '#B7C4C1',
                        '#8FC9C0',
                        '#4B5A56'

                    ]

                }

            ]

        },

        options: {

            plugins: {

                legend: {

                    position: 'bottom',

                    labels: {

                        boxWidth: 10,

                        font: {
                            size: 11
                        }

                    }

                }

            },

            cutout: '62%'

        }

    }
);
}

</script>


<script src="../assets/js/csp-handlers.js" defer></script></body>

</html>