<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requerirRol(['analista']);
$u = usuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar perfil | Soporte Técnico</title>
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
            <div class="card-panel" style="max-width:760px;margin:0 auto;">
                <div class="panel-head">
                    <div>
                        <h2>Completar perfil</h2>
                        <p class="text-muted small mb-0">Registra tus datos para comenzar a utilizar el sistema.</p>
                    </div>
                    <i class="bi bi-person-vcard fs-3" style="color:var(--teal-700);"></i>
                </div>

                <div class="panel-body">
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-circle"></i>
                            <span>Verifica los datos y confirma que ambos correos coincidan.</span>
                        </div>
                    <?php endif; ?>

                    <div class="alert" style="background:var(--teal-100);color:var(--teal-900);border:none;">
                        <i class="bi bi-info-circle me-1"></i>
                        Completa esta información una sola vez. Después podrás administrar tu teléfono, avatar y correo desde tu perfil.
                    </div>

                    <form method="post" action="guardar_perfil.php"><?= csrf_field() ?>
                        <div class="form-section">
                            <p class="fs-title"><span class="fs-num">1</span>Datos personales</p>
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label" for="nombre">Nombre</label>
                                    <input id="nombre" class="form-control" name="nombre" autocomplete="given-name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="apellido">Apellido</label>
                                    <input id="apellido" class="form-control" name="apellido" autocomplete="family-name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="cedula">Cédula</label>
                                    <input id="cedula" class="form-control" name="cedula" pattern="[VE]-[0-9]{8}" maxlength="10" placeholder="V-00000000" autocomplete="off" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="telefono">Teléfono</label>
                                    <input id="telefono" class="form-control" name="telefono" pattern="(0424|0414|0412|0422|0426|0416)-[0-9]{7}" maxlength="12" placeholder="0400-0000000" autocomplete="tel" inputmode="tel">
                                </div>
                            </div>
                        </div>

                        <div class="form-section mb-0">
                            <p class="fs-title"><span class="fs-num">2</span>Correo electrónico</p>
                            <p class="form-text mt-1">Usaremos este correo para enviarte el enlace de confirmación.</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="correo">Correo</label>
                                    <input id="correo" type="email" class="form-control" name="correo" autocomplete="email" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="confirmar_correo">Confirmar correo</label>
                                    <input id="confirmar_correo" type="email" class="form-control" name="confirmar_correo" autocomplete="email" required>
                                </div>
                            </div>
                        </div>

                        <div class="border-top mt-4 pt-3 d-flex justify-content-end">
                            <button class="btn btn-brand" type="submit">
                                <i class="bi bi-check2-circle me-1"></i>Guardar y continuar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('show');
});
</script>
</body>
</html>