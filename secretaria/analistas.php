<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requerirRol(['secretaria', 'administrador']);

$u = usuarioActual();

/*
|--------------------------------------------------------------------------
| Nombre del usuario de Secretaría
|--------------------------------------------------------------------------
| El nombre y apellido están en perfiles_usuarios.
*/
$nombreUsuario = trim(
    ($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? '')
);

if ($nombreUsuario === '') {
    $nombreUsuario = $u['usuario'] ?? 'Usuario';
}


/*
|--------------------------------------------------------------------------
| Analistas registrados
|--------------------------------------------------------------------------
| Datos:
| - usuarios.usuario
| - perfiles_usuarios.nombre
| - perfiles_usuarios.apellido
| - usuarios.fecha_creacion
| - usuarios.ultima_actividad
|
| El analista se considera:
| ACTIVO   = actividad dentro de los últimos 5 minutos.
| INACTIVO = sin actividad reciente.
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        u.id_usuarios,
        u.usuario,
        p.nombre,
        p.apellido,
        u.ultima_actividad,
        u.fecha_creacion,
        r.nombre AS rol
    FROM usuarios u

    INNER JOIN roles r
        ON r.id_roles = u.rol_id

    LEFT JOIN perfiles_usuarios p
        ON p.usuario_id = u.id_usuarios

    WHERE LOWER(TRIM(r.nombre)) IN (
        'analista',
        'tecnico',
        'técnico'
    )

    ORDER BY u.fecha_creacion DESC
");

$analistas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$pgAnalistas = paginar($analistas);
$totalAnalistas = $pgAnalistas['total'];
$analistas = $pgAnalistas['items'];


/*
|--------------------------------------------------------------------------
| Determinar estado del analista
|--------------------------------------------------------------------------
| Se considera ACTIVO si tuvo actividad durante los últimos 5 minutos.
|--------------------------------------------------------------------------
*/

$ahora = time();

foreach ($analistas as &$analista) {

    $analista['en_linea'] = false;

    if (!empty($analista['ultima_actividad'])) {

        $ultimaActividad = strtotime(
            $analista['ultima_actividad']
        );

        if ($ultimaActividad !== false) {

            $diferencia = $ahora - $ultimaActividad;

            if ($diferencia >= 0 && $diferencia <= 300) {
                $analista['en_linea'] = true;
            }
        }
    }
}

unset($analista);

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
        Analistas | Soporte Técnico
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
        href="../assets/css/style.css"
        rel="stylesheet"
    >

</head>


<body>

<div class="app-shell">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <!-- ==========================================================
         CONTENIDO PRINCIPAL
    =========================================================== -->

    <div class="main">
<?php include __DIR__ . '/../includes/topbar.php'; ?>



        <!-- ======================================================
             CONTENIDO
        ======================================================= -->

        <div class="content">


            <!-- ==================================================
                 MENSAJES
            =================================================== -->

            <?php if (($_GET['error'] ?? '') === '1'): ?>

                <div class="alert alert-danger">

                    La contraseña debe tener mayúscula,
                    minúscula, número y símbolo.

                </div>

            <?php endif; ?>


            <?php if (($_GET['error'] ?? '') === '2'): ?>

                <div class="alert alert-danger">

                    Ese usuario ya existe.

                </div>

            <?php endif; ?>


            <?php if (($_GET['error'] ?? '') === '3'): ?>

                <div class="alert alert-danger">

                    El nombre de usuario es obligatorio.

                </div>

            <?php endif; ?>


            <?php if (($_GET['success'] ?? '') === '1'): ?>

                <div class="alert alert-success">

                    Analista registrado correctamente.

                </div>

            <?php endif; ?>



            <!-- ==================================================
                 TARJETA DE ANALISTAS
            =================================================== -->

            <div class="card-panel">


                <div class="panel-head">

                    <h2>
                        Analistas registrados
                    </h2>


                    <div class="d-flex gap-2 align-items-center">

                        <span class="text-muted small">

                            <?= $totalAnalistas ?>

                        </span>


                        <button
                            class="btn btn-brand btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#modalAnalista"
                            type="button"
                        >

                            <i class="bi bi-person-plus me-1"></i>

                            Registrar analista

                        </button>

                    </div>

                </div>



                <!-- ==================================================
                     TABLA
                =================================================== -->

                <div class="table-responsive">

                    <table class="table align-middle mb-0">


                        <thead
                            style="background:var(--paper);"
                        >

                            <tr
                                class="small text-muted text-uppercase"
                                style="letter-spacing:.03em;"
                            >

                                <th class="ps-3">
                                    Usuario
                                </th>

                                <th>
                                    Nombre
                                </th>

                                <th>
                                    Apellido
                                </th>

                                <th>
                                    Registrado
                                </th>

                                <th class="pe-3">
                                    Estado en el sistema
                                </th>

                            </tr>

                        </thead>
<tbody style="font-size:.88rem;">
    <?php foreach ($analistas as $a): ?>
        <tr style="cursor:pointer;" data-csp-href="analista_detalle.php?id=<?= (int)$a['id_usuarios'] ?>">
            <!-- USUARIO -->
            <td class="ps-3 fw-semibold"><?= h($a['usuario'] ?? '—') ?></td>
            <!-- NOMBRE -->
            <td><?= h(!empty($a['nombre']) ? $a['nombre'] : '—') ?></td>
            <!-- APELLIDO -->
            <td><?= h(!empty($a['apellido']) ? $a['apellido'] : '—') ?></td>
            <!-- FECHA DE REGISTRO -->
            <td>
                <?php
                if (!empty($a['fecha_creacion'])) {
                    $fecha = strtotime($a['fecha_creacion']);
                    if ($fecha !== false) {
                        echo h(date('d/m/Y', $fecha));
                    } else {
                        echo '—';
                    }
                } else {
                    echo '—';
                }
                ?>
            </td>
            <!-- ESTADO -->
            <td class="pe-3">
                <?php if ($a['en_linea']): ?>
                    <span class="badge-completado badge">
                        <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>
                        Activo
                    </span>
                <?php else: ?>
                    <span class="badge-pendiente badge">
                        <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>
                        Inactivo
                    </span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <!-- SIN ANALISTAS -->
    <?php if (!$analistas): ?>
        <tr>
            <td colspan="5" class="text-center text-muted py-4">Aún no hay analistas registrados.</td>
        </tr>
    <?php endif; ?>
</tbody>

                    </table>

                </div>

                <div class="panel-body"><?= renderPaginacion($pgAnalistas) ?></div>

            </div>

        </div>

    </div>

</div>



<!-- ==============================================================
     MODAL REGISTRAR ANALISTA
================================================================ -->

<div
    class="modal fade"
    id="modalAnalista"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog">

        <div class="modal-content">


            <form
                method="post"
                action="analista_guardar.php"
            >
                <?= csrf_field() ?>

                <div class="modal-header">

                    <h5 class="modal-title">
                        Registrar nuevo analista
                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar"
                    ></button>

                </div>



                <div class="modal-body">


                    <!-- MENSAJES DEL FORMULARIO -->

                    <?php if (($_GET['error'] ?? '') === '1'): ?>

                        <div class="alert alert-danger small">

                            La contraseña debe tener
                            mayúscula, minúscula,
                            número y símbolo.

                        </div>

                    <?php elseif (($_GET['error'] ?? '') === '2'): ?>

                        <div class="alert alert-danger small">

                            Ese usuario ya existe.

                        </div>

                    <?php elseif (($_GET['error'] ?? '') === '3'): ?>

                        <div class="alert alert-danger small">

                            El usuario es obligatorio.

                        </div>

                    <?php endif; ?>



                    <!-- USUARIO -->

                    <div class="mb-3">

                        <label
                            class="form-label"
                            for="usuario"
                        >
                            Usuario
                        </label>


                        <input
                            type="text"
                            id="usuario"
                            name="usuario"
                            class="form-control"
                            required
                            autocomplete="off"
                        >

                    </div>



                    <!-- CONTRASEÑA -->

                    <div class="mb-1">

                        <label
                            class="form-label"
                            for="password"
                        >
                            Contraseña
                        </label>


                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            required
                            autocomplete="new-password"
                        >


                        <div class="form-text">

                            Mínimo 12 caracteres,
                            mayúscula, minúscula,
                            número y símbolo.

                        </div>

                    </div>


                </div>



                <div class="modal-footer">


                    <button
                        type="button"
                        class="btn btn-outline-brand"
                        data-bs-dismiss="modal"
                    >

                        Cancelar

                    </button>


                    <button
                        type="submit"
                        class="btn btn-brand"
                    >

                        Registrar analista

                    </button>


                </div>


            </form>

        </div>

    </div>

</div>



<!-- ==============================================================
     SCRIPTS
================================================================ -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
></script>


<script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">

document
    .getElementById('sidebarToggle')
    ?.addEventListener('click', function () {

        document
            .getElementById('sidebar')
            .classList
            .toggle('show');

    });

</script>


<script src="../assets/js/csp-handlers.js" defer></script></body>

</html>