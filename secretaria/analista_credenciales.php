<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/mail.php';
requerirRol(['secretaria', 'administrador']);
/*
|--------------------------------------------------------------------------
| Verificar que existan las credenciales temporales
|--------------------------------------------------------------------------
*/
if (empty($_SESSION['credencial_nueva'])) {
    header('Location: analistas.php');
    exit;
}
$c = $_SESSION['credencial_nueva'];
/*
|--------------------------------------------------------------------------
| Las credenciales solo se muestran una vez
|--------------------------------------------------------------------------
*/
unset($_SESSION['credencial_nueva']);
/*
|--------------------------------------------------------------------------
| URL de acceso
|--------------------------------------------------------------------------
|
| Se construye automáticamente utilizando el servidor actual.
|
*/
$baseAplicacion = urlAplicacion();
$urlAcceso = $baseAplicacion ? $baseAplicacion . '/index.php' : '';

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
        Credenciales del analista | Soporte Técnico
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
    <!-- =========================================================
         MAIN
    ========================================================== -->

    <div class="main">
<?php include __DIR__ . '/../includes/topbar.php'; ?>
        <!-- =====================================================
             CONTENT
        ====================================================== -->
        <div class="content d-flex justify-content-center">
            <div
                style="max-width:520px;width:100%;"
            >
                <!-- AVISO DE SEGURIDAD -->

                <div
                    class="alert d-flex align-items-center gap-2 no-print"
                    style="background:var(--teal-100);border:none;"
                >
                    <i class="bi bi-shield-lock-fill"></i>
                    <div class="small">
                        Esta contraseña no queda guardada
                        en texto plano ni se puede volver
                        a ver. Si se pierde, habrá que
                        restablecerla.
                    </div>
                </div>
                <!-- =================================================
                     CREDENCIALES
                ================================================== -->
                <div class="credencial-card mb-4">
                    <div class="row g-3 text-center text-sm-start">
                        <!-- USUARIO -->
                        <div class="col-sm-6">
                            <div class="small text-muted">
                                Usuario
                            </div>
                            <div
                                class="fw-bold"
                                style="font-family:var(--font-display);"
                            >
                                <?= h($c['usuario'] ?? '') ?>
                            </div>
                        </div>
                        <!-- CONTRASEÑA -->
                        <div class="col-sm-6">
                            <div class="small text-muted">
                                Contraseña temporal
                            </div>
                            <div
                                class="fw-bold"
                                style="
                                    font-family:var(--font-display);
                                    letter-spacing:.05em;
                                "
                            >
                                <?= h($c['password'] ?? '') ?>
                            </div>
                        </div>
                        <!-- ROL -->
                        <div class="col-sm-6">
                            <div class="small text-muted">
                                Rol
                            </div>
                            <div class="fw-bold">
                            Analista
                            </div>
                        </div>
                    </div>
                </div>
                <!-- =================================================
                     QR
                ================================================== -->
                <div class="card-panel mb-4">
                    <div class="panel-body text-center">
                        <h2 class="fs-6 fw-bold mb-1">
                            Acceso desde el celular
                        </h2>
                        <p class="text-muted small mb-3">
                            El analista puede escanear este
                            código QR para abrir el sistema.
                            También puede utilizar
                            "Agregar a pantalla de inicio"
                            para tenerlo como un ícono.
                        </p>
                        <?php if ($urlAcceso !== ''): ?>
                            <div id="qrCode"></div>
                        <?php else: ?>
                            <p class="text-danger small">Configura APP_URL para generar el código de acceso.</p>
                        <?php endif; ?>
                        <p
                            class="small text-muted mt-3 mb-0"
                            style="word-break:break-all;"
                        >
                            <?= h($urlAcceso) ?>
                        </p>
                    </div>
                </div>
                <!-- =================================================
                     BOTONES
                ================================================== -->
                <div class="d-flex gap-2 no-print">
                    <a
                        href="analistas.php"
                        class="btn btn-brand flex-fill"
                    >
                        Listo
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- =============================================================
     BOOTSTRAP
============================================================= -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
></script>
<!-- =============================================================
     QR CODE
============================================================= -->
<script
    src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js" integrity="sha512-CNgIRecGo7nphbeZ04Sc13ka07paqdeTu0WR1IM4kNcpmBAUSHSQX0FslNhTDadL4O5SAGapGt4FodqL8My0mA==" crossorigin="anonymous"
></script>
<script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
<?php if ($urlAcceso !== ''): ?>
new QRCode(
    document.getElementById('qrCode'),
    {
        text: <?= json_encode($urlAcceso) ?>,
        width: 168,
        height: 168,
        colorDark: '#0A3F3A',
        colorLight: '#ffffff'
    }
);
<?php endif; ?>
</script>
</body>
</html>