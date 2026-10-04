<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requerirRol(['secretaria', 'administrador']);

$u = usuarioActual();


// =====================================================
// ID DE LA HOJA
// =====================================================

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    die('Identificador de hoja de servicio inválido.');
}


// =====================================================
// OBTENER HOJA DE SERVICIO
// =====================================================

$stmt = $pdo->prepare("
    SELECT
        hs.*,
        d.nombre AS departamento,
        u.id_usuarios AS tecnico_id,
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
    WHERE hs.id_hojas = :id
    LIMIT 1
");

$stmt->execute([
    'id' => $id
]);

$hoja = $stmt->fetch();


// =====================================================
// VALIDAR QUE EXISTA
// =====================================================

if (!$hoja) {
    http_response_code(404);
    die('Hoja de servicio no encontrada.');
}


// =====================================================
// TIPOS DE SERVICIO
// =====================================================

$tiposStmt = $pdo->prepare("
    SELECT
        ts.id_servicios,
        ts.codigo,
        ts.nombre,
        CASE
            WHEN hst.hoja_id IS NOT NULL THEN 1
            ELSE 0
        END AS marcado
    FROM tipos_servicio ts
    LEFT JOIN hoja_servicio_tipo hst
        ON hst.tipo_servicio_id = ts.id_servicios
       AND hst.hoja_id = :id
    ORDER BY
        ts.codigo
");

$tiposStmt->execute([
    'id' => $id
]);

$tipos = $tiposStmt->fetchAll();


// =====================================================
// EVIDENCIA
// =====================================================

$evidenciaStmt = $pdo->prepare('
    SELECT archivo
    FROM hoja_servicio_evidencias
    WHERE hoja_id = :id
    LIMIT 1
');
$evidenciaStmt->execute(['id' => $id]);
$evidencia = $evidenciaStmt->fetchColumn() ?: null;


// =====================================================
// RUTA Y BOTÓN VOLVER
// =====================================================

$rutaBase = '../';

$volver = ($u['rol'] ?? '') === 'administrador'
    ? '../admin/dashboard.php'
    : 'dashboard.php';


// =====================================================
// NÚMERO DE HOJA
// =====================================================

$numeroHoja = numeroHoja((int)$hoja['id_hojas']);

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
        Hoja N° <?= h($numeroHoja) ?> | Soporte Técnico
    </title>

    <link
        rel="icon"
        href="../assets/img/logo.png"
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
        href="../assets/css/style.css"
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
             CONTENIDO DE LA HOJA
        ================================================== -->

        <div class="content worksheet-page">

            <?php
            require __DIR__ . '/../includes/vista_hoja.php';
            ?>

        </div>

    </div>

</div>



<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
></script>


<script src="../assets/js/csp-handlers.js" defer></script></body>

</html>