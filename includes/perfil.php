<?php
/**
 * Perfil unificado — "Administrar mi perfil" para TODOS los módulos.
 *
 * Cada cambio es independiente:
 *   - Teléfono: se edita y guarda por separado.
 *   - Correo: se edita y guarda por separado y solicita confirmación.
 *   - Avatar: se cambia por separado.
 *
 * No existe un botón global "Editar datos", por lo que una modificación
 * nunca obliga a enviar nuevamente los demás campos.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/upload_security.php';

requerirRol(['administrador', 'secretaria', 'analista', 'tecnico', 'departamento']);

$u = usuarioActual();
$rol = ($u['rol'] === 'tecnico') ? 'analista' : $u['rol'];
$modulo = rutaDashboardPorRol($u['rol']);

$etiquetasRol = [
    'administrador' => 'Administrador',
    'secretaria'    => 'Secretaría',
    'analista'      => 'Analista',
    'departamento'  => 'Oficina',
];

/* ---------- Perfil actual ---------- */
$q = $pdo->prepare(
    'SELECT p.*, d.nombre AS departamento
       FROM perfiles_usuarios p
       LEFT JOIN departamentos d ON d.id_departamentos = p.departamento_id
      WHERE p.usuario_id = ? LIMIT 1'
);
$q->execute([$u['id']]);
$p = $q->fetch() ?: null;

/* El analista completa su perfil por su flujo de primer ingreso. */
if (!$p && $rol === 'analista') {
    header('Location: ../analista/completar_perfil.php');
    exit;
}

$esNuevo = !$p;

/*
 * Para perfiles ya existentes, nombre/apellido/cédula permanecen como datos
 * informativos. Los cambios solicitados aquí son independientes: teléfono,
 * correo y avatar.
 *
 * Si un perfil nuevo llega a esta pantalla, se permite completar todos sus
 * datos una sola vez.
 */
$editaIdentidad = $esNuevo;

/* ---------- Guardar una operación independiente ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $volver = static function (string $qs = ''): void {
        header('Location: perfil.php' . ($qs !== '' ? '?' . $qs : ''));
        exit;
    };

    if (!verificarCsrf($_POST['csrf_token'] ?? null)) {
        $volver('error=csrf');
    }

    $accion = $_POST['accion'] ?? '';

    /*
     * PERFIL NUEVO
     * Se mantiene como flujo de alta para cuentas que todavía no tienen
     * perfil. Los analistas normalmente pasan primero por completar_perfil.php.
     */
    if ($esNuevo) {
        if ($accion !== 'crear_perfil') {
            $volver('error=datos');
        }

        $nombre   = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $cedula   = strtoupper(trim($_POST['cedula'] ?? ''));
        $correo   = trim($_POST['correo'] ?? '');
        $confirm  = trim($_POST['confirmar_correo'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        if (
            $nombre === '' ||
            $apellido === '' ||
            !cedulaValida($cedula) ||
            !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
            $correo !== $confirm ||
            ($telefono !== '' && !telefonoValido($telefono))
        ) {
            $volver('error=datos');
        }

        try {
            $pdo->prepare(
                'INSERT INTO perfiles_usuarios
                    (usuario_id, nombre, apellido, cedula, correo, telefono, confirmar_correo)
                 VALUES (?, ?, ?, ?, ?, ?, 0)'
            )->execute([
                $u['id'],
                $nombre,
                $apellido,
                $cedula,
                $correo,
                $telefono ?: null
            ]);

            $_SESSION['nombre_completo'] = $nombre . ' ' . $apellido;
            $volver('ok=1');
        } catch (Throwable $e) {
            error_log('No se pudo crear el perfil: ' . $e->getMessage());

            if ((string)$e->getCode() === '23000') {
                $mensaje = stripos($e->getMessage(), 'cedula') !== false
                    ? 'cedula_duplicada'
                    : 'correo_duplicado';
                $volver('error=' . $mensaje);
            }

            $volver('error=guardar');
        }
    }

    /*
     * TELÉFONO
     * Solo actualiza telefono. No toca correo, avatar, nombre ni cédula.
     */
    if ($accion === 'telefono') {
        $telefono = trim($_POST['telefono'] ?? '');

        if ($telefono !== '' && !telefonoValido($telefono)) {
            $volver('error=telefono');
        }

        try {
            $pdo->prepare(
                'UPDATE perfiles_usuarios SET telefono = ? WHERE usuario_id = ?'
            )->execute([$telefono !== '' ? $telefono : null, $u['id']]);

            registrarAuditoriaPerfil($pdo, $u['id'], 'Actualizó su número de teléfono');
            $volver('ok=telefono');
        } catch (Throwable $e) {
            error_log('No se pudo actualizar el teléfono: ' . $e->getMessage());
            $volver('error=guardar');
        }
    }

    /*
     * AVATAR
     * Solo actualiza avatar. La imagen se presenta siempre dentro del círculo
     * mediante object-fit: cover.
     */
    if ($accion === 'avatar') {
        $dirAvatars = __DIR__ . '/../uploads/avatars';
        $archivo = $_FILES['avatar'] ?? null;

        if (!$archivo || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
            $volver('error=imagen');
        }

        $extensiones = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif'
        ];
        try {
            $extensionAvatar = validarImagenSubida($archivo, $extensiones, 5 * 1024 * 1024);
        } catch (Throwable $e) {
            $volver('error=imagen');
        }

        if (!is_dir($dirAvatars) && !mkdir($dirAvatars, 0775, true)) {
            $volver('error=imagen');
        }

        $avatarNuevo = bin2hex(random_bytes(12)) . '.' . $extensionAvatar;

        if (!move_uploaded_file($archivo['tmp_name'], $dirAvatars . '/' . $avatarNuevo)) {
            $volver('error=imagen');
        }

        $avatarAnterior = $p['avatar'] ?? '';

        try {
            $pdo->beginTransaction();

            $pdo->prepare(
                'UPDATE perfiles_usuarios SET avatar = ? WHERE usuario_id = ?'
            )->execute([$avatarNuevo, $u['id']]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            @unlink($dirAvatars . '/' . $avatarNuevo);
            error_log('No se pudo actualizar el avatar: ' . $e->getMessage());
            $volver('error=guardar');
        }

        /* Elimina el avatar anterior solo si ya no lo usa ningún perfil. */
        if ($avatarAnterior !== '') {
            $viejo = basename($avatarAnterior);
            $uso = $pdo->prepare('SELECT COUNT(*) FROM perfiles_usuarios WHERE avatar = ?');
            $uso->execute([$viejo]);

            if ((int)$uso->fetchColumn() === 0) {
                @unlink($dirAvatars . '/' . $viejo);
            }
        }

        registrarAuditoriaPerfil($pdo, $u['id'], 'Cambió su imagen de perfil');
        $volver('ok=avatar');
    }

    /*
     * CORREO
     * Solo actualiza correo y su estado de confirmación. No toca teléfono
     * ni avatar.
     */
    if ($accion === 'correo') {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (excedeLimiteAuth($pdo, 'cambio-correo-ip-hour', $ip, 10, 3600) || excedeLimiteAuth($pdo, 'cambio-correo-account-hour', (string)$u['id'], 5, 3600)) $volver('error=correo_envio');
        $correo  = strtolower(trim($_POST['correo'] ?? ''));
        $confirm = strtolower(trim($_POST['confirmar_correo'] ?? ''));
        $passwordActual = (string)($_POST['password_actual'] ?? '');

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $volver('error=correo');
        if ($correo !== $confirm) $volver('error=correo_coincide');
        if ($passwordActual === '') $volver('error=password_actual');

        $cuenta = $pdo->prepare('SELECT contrasena FROM usuarios WHERE id_usuarios=? AND estado="activo" LIMIT 1');
        $cuenta->execute([$u['id']]);
        $hashCuenta = $cuenta->fetchColumn();
        if (!is_string($hashCuenta) || !password_verify($passwordActual, $hashCuenta)) $volver('error=password_actual');

        if (strcasecmp($correo, (string)$p['correo']) === 0) $volver('ok=correo_sin_cambio');

        $ocupado = $pdo->prepare('SELECT 1 FROM perfiles_usuarios WHERE LOWER(correo)=LOWER(?) AND usuario_id<>? LIMIT 1');
        $ocupado->execute([$correo, $u['id']]);
        if ($ocupado->fetchColumn()) $volver('error=correo_duplicado');

        $token = bin2hex(random_bytes(32));
        require_once __DIR__ . '/../config/mail.php';
        $base = urlAplicacion();
        if ($base === null) {
          emitirLogError('APP_URL no está configurada para cambio de correo.', ['usuario_id' => $u['id']]);
          $volver('error=correo_envio');
        }
        $nombreCompleto = trim(($p['nombre'] ?? '') . ' ' . ($p['apellido'] ?? '')) ?: $u['usuario'];
        $url = $base . '/includes/confirmar_cambio_correo.php?token=' . urlencode($token);
        if (!enviarCorreoConfirmacion($correo, $nombreCompleto, $url)) $volver('error=correo_envio');

        try {
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM cambios_correo_pendientes WHERE usuario_id=?')->execute([$u['id']]);
          $pdo->prepare('UPDATE perfiles_usuarios SET correo=?, confirmar_correo=0 WHERE usuario_id=?')
            ->execute([$correo, $u['id']]);
            $pdo->prepare('INSERT INTO cambios_correo_pendientes (usuario_id, correo_nuevo, token_hash, expira_en) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))')
                ->execute([$u['id'], $correo, hash('sha256', $token)]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            emitirLogError('No se pudo crear la solicitud de cambio de correo.', ['usuario_id' => $u['id'], 'code' => $e->getCode()]);
            $volver('error=guardar');
        }

          registrarAuditoriaPerfil($pdo, $u['id'], 'Cambió su correo; pendiente de confirmación');
        $volver('ok=correo');
    }

    $volver('error=datos');
}

/* ---------- Vista (GET) ---------- */
$p = $p ?? [
    'nombre' => '',
    'apellido' => '',
    'cedula' => '',
    'correo' => '',
    'telefono' => '',
    'avatar' => '',
    'confirmar_correo' => 0,
    'departamento' => ''
];

$confirmado = (int)$p['confirmar_correo'] === 1;
$ok = $_GET['ok'] ?? null;
$error = $_GET['error'] ?? null;

$mensajesError = [
    'imagen'           => 'La imagen no pudo guardarse. Usa JPG, PNG, WEBP o GIF de hasta 5 MB.',
    'correo'           => 'Escribe un correo electrónico válido.',
    'correo_coincide'  => 'El correo y su confirmación deben coincidir.',
    'correo_duplicado' => 'Ese correo ya está registrado en otra cuenta.',
    'correo_envio'     => 'No se pudo enviar el enlace de confirmación. El correo no fue cambiado.',
    'password_actual'  => 'La contraseña actual no es correcta.',
    'telefono'         => 'El teléfono debe tener formato 0424-1234567.',
    'cedula'            => 'La cédula debe tener formato V-12345678 o E-12345678.',
    'cedula_duplicada' => 'Esa cédula ya está registrada en otra cuenta.',
    'datos'             => 'Verifica los datos requeridos.',
    'csrf'              => 'La sesión del formulario expiró. Inténtalo de nuevo.',
    'guardar'            => 'No se pudieron guardar los cambios. Inténtalo nuevamente.',
];

/* Variables que consumen sidebar.php y topbar.php */
$moduloPath = '../' . $modulo . '/';
$rootPath   = '../';
$assetPath  = '../assets/';
$logoutPath = '../logout.php';
$telefonoPatron = 'pattern="(0424|0414|0412|0422|0426|0416)-[0-9]{7}"';

/**
 * Auditoría segura: el cambio de perfil no debe fallar aunque la tabla
 * de auditoría tenga una incidencia.
 */
function registrarAuditoriaPerfil(PDO $pdo, int $usuarioId, string $detalle): void
{
    try {
        $pdo->prepare(
            'INSERT INTO auditoria_accesos (usuario_id, accion, detalle, ip)
             VALUES (?, ?, ?, ?)'
        )->execute([
            $usuarioId,
            'actualizar_perfil',
            $detalle,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (Throwable $e) {
        error_log('Auditoría de perfil: ' . $e->getMessage());
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mi perfil | Soporte Técnico</title>
<link rel="icon" href="../assets/img/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
<link href="../assets/css/style.css?v=20261001-blue" rel="stylesheet">
<?php if ($rol === 'administrador'): ?><link href="../assets/css/admin.css?v=20261001-blue" rel="stylesheet"><?php endif; ?>

<style>
/* Perfil: edición independiente por campo */
.profile-field {
    position: relative;
}

.profile-field .field-edit {
    flex: 0 0 auto;
}

.profile-field .form-control[disabled] {
    background-color: var(--bs-secondary-bg, #f8f9fa);
    cursor: default;
}

.profile-action {
    display: none;
    margin-top: .55rem;
}

.profile-field.is-editing .profile-action {
    display: flex;
}

.profile-field.is-editing .form-control {
    background-color: #fff;
    border-color: var(--teal-700, #0a3f3a);
    box-shadow: 0 0 0 .15rem rgba(10, 63, 58, .08);
}

.profile-avatar-wrap {
    width: 112px;
    height: 112px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--teal-100, #e8f3f1);
}

.profile-avatar-wrap img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    border-radius: 50%;
}

.profile-avatar-wrap .avatar-initials {
    font-size: 1.8rem;
    font-weight: 700;
}

.avatar-actions .btn {
    min-width: 145px;
}

#avatarAction.d-flex { display: flex !important; }

#avatarNombre {
    max-width: 320px;
    margin-left: auto;
    margin-right: auto;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
</head>
<body>
<div class="app-shell">
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="main">
<?php include __DIR__ . '/topbar.php'; ?>
<div class="content<?= $rol === 'administrador' ? ' admin-content' : '' ?>">
<div class="card-panel" style="max-width:820px;margin:0 auto;">
  <div class="panel-head">
    <div>
      <h2>Administrar mi perfil</h2>
      <p class="text-muted small mb-0">
        <?= $editaIdentidad
            ? 'Completa tus datos personales, de contacto y tu imagen.'
            : 'Cada dato se puede modificar de forma independiente.' ?>
      </p>
    </div>
    <?php if (!$esNuevo): ?>
      <span class="<?= $confirmado ? 'badge-completado' : 'badge-pendiente' ?> badge">
        <?= $confirmado ? 'Correo confirmado' : 'Correo pendiente' ?>
      </span>
    <?php endif; ?>
  </div>

  <div class="panel-body">

    <?php if (!empty($_GET['confirmado'])): ?>
      <div class="alert alert-success">
        <i class="bi bi-check2-circle me-1"></i>Correo confirmado correctamente.
      </div>
    <?php elseif ($ok === 'correo'): ?>
      <div class="alert alert-success">
        <i class="bi bi-envelope-check me-1"></i>
        Correo actualizado y pendiente de confirmación. Enviamos un enlace al nuevo correo.
      </div>
    <?php elseif ($ok === 'correo_sin_cambio'): ?>
      <div class="alert alert-info">
        <i class="bi bi-info-circle me-1"></i>
        El correo indicado es el mismo que ya tienes registrado.
      </div>
    <?php elseif ($ok === 'telefono'): ?>
      <div class="alert alert-success">
        <i class="bi bi-telephone-check me-1"></i>
        Número de teléfono actualizado correctamente.
      </div>
    <?php elseif ($ok === 'avatar'): ?>
      <div class="alert alert-success">
        <i class="bi bi-person-circle me-1"></i>
        Imagen de perfil actualizada correctamente.
      </div>
    <?php elseif ($ok): ?>
      <div class="alert alert-success">
        <i class="bi bi-check2-circle me-1"></i>Perfil creado correctamente.
      </div>
    <?php elseif ($error): ?>
      <div class="alert alert-danger">
        <i class="bi bi-exclamation-circle me-1"></i>
        <?= h($mensajesError[$error] ?? 'No se pudieron guardar los cambios. Revisa los datos e inténtalo de nuevo.') ?>
      </div>
    <?php endif; ?>

    <?php if ($esNuevo): ?>
      <!-- Perfil nuevo: flujo único de creación. -->
      <form method="post" action="perfil.php" id="crearPerfilForm">
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="crear_perfil">

        <div class="text-center mb-4">
          <div class="profile-avatar-wrap">
            <span class="avatar-initials">
              <?= h(iniciales(trim(($p['nombre'] ?? '') . ' ' . ($p['apellido'] ?? '')) ?: $u['usuario'])) ?>
            </span>
          </div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="nombre">Nombre</label>
            <input id="nombre" class="form-control" name="nombre" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="apellido">Apellido</label>
            <input id="apellido" class="form-control" name="apellido" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="cedula">Cédula</label>
            <input id="cedula" class="form-control" name="cedula" maxlength="10" placeholder="V-00000000" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="telefonoNuevo">Teléfono</label>
            <input id="telefonoNuevo" class="form-control" name="telefono" <?= $telefonoPatron ?> maxlength="12" placeholder="0400-0000000" inputmode="tel">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="correoNuevo">Correo</label>
            <input id="correoNuevo" type="email" class="form-control" name="correo" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="confirmarCorreoNuevo">Confirmar correo</label>
            <input id="confirmarCorreoNuevo" type="email" class="form-control" name="confirmar_correo" required>
          </div>
        </div>

        <div class="border-top mt-4 pt-3 d-flex justify-content-end">
          <button type="submit" class="btn btn-brand">
            <i class="bi bi-check2-circle me-1"></i>Guardar y continuar
          </button>
        </div>
      </form>

    <?php else: ?>

      <!-- AVATAR: operación completamente independiente -->
      <div class="text-center mb-4">
        <form method="post" action="perfil.php" enctype="multipart/form-data" id="avatarForm">
          <?= csrf_field() ?>
          <input type="hidden" name="accion" value="avatar">

          <div class="profile-avatar-wrap">
            <?php if (!empty($p['avatar'])): ?>
              <img
                id="avatarPreview"
                src="../includes/archivo_privado.php?tipo=avatar&amp;id=<?= (int)$u['id'] ?>"
                alt="Imagen de perfil">
            <?php else: ?>
              <span id="avatarInitials" class="avatar-initials">
                <?= h(iniciales(trim($p['nombre'] . ' ' . $p['apellido']) ?: $u['usuario'])) ?>
              </span>
              <img id="avatarPreview" src="" style="display:none;" alt="Vista previa de la imagen de perfil">
            <?php endif; ?>
          </div>

          <div class="avatar-actions mt-3">
            <label for="avatarInput" class="btn btn-outline-brand btn-sm">
              <i class="bi bi-camera me-1"></i>Cambiar imagen
            </label>
            <input
              id="avatarInput"
              type="file"
              name="avatar"
              accept="image/jpeg,image/png,image/webp,image/gif"
              hidden>
          </div>

          <div id="avatarNombre" class="form-text mt-2">
            La imagen se ajustará automáticamente al círculo.
          </div>

          <div id="avatarAction" class="profile-action justify-content-center gap-2">
            <button type="submit" class="btn btn-brand btn-sm">
              <i class="bi bi-check2 me-1"></i>Guardar imagen
            </button>
            <button type="button" id="cancelarAvatar" class="btn btn-outline-secondary btn-sm">
              Cancelar
            </button>
          </div>
        </form>
      </div>

      <div class="row g-3">

        <!-- NOMBRE -->
        <div class="col-md-6">
          <label class="form-label" for="nombreVisual">Nombre</label>
          <input id="nombreVisual" class="form-control" value="<?= h($p['nombre']) ?>" disabled>
        </div>

        <!-- APELLIDO -->
        <div class="col-md-6">
          <label class="form-label" for="apellidoVisual">Apellido</label>
          <input id="apellidoVisual" class="form-control" value="<?= h($p['apellido']) ?>" disabled>
        </div>

        <!-- CÉDULA -->
        <div class="col-md-6">
          <label class="form-label" for="cedulaVisual">Cédula</label>
          <input id="cedulaVisual" class="form-control" value="<?= h($p['cedula']) ?>" disabled>
        </div>

        <!-- TELÉFONO: edición independiente -->
        <div class="col-md-6">
          <form method="post" action="perfil.php" class="profile-field" id="telefonoField">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="telefono">

            <label class="form-label" for="telefono">
              Teléfono
            </label>

            <div class="d-flex align-items-center gap-2">
              <input
                id="telefono"
                class="form-control"
                name="telefono"
                value="<?= h($p['telefono']) ?>"
                <?= (!$p['telefono'] || telefonoValido($p['telefono'])) ? $telefonoPatron : '' ?>
                maxlength="12"
                placeholder="0400-0000000"
                inputmode="tel"
                autocomplete="tel"
                disabled>

              <button
                type="button"
                class="btn btn-outline-brand field-edit"
                data-edit-target="telefonoField"
                aria-label="Editar número de teléfono"
                title="Editar número de teléfono">
                <i class="bi bi-pencil"></i>
              </button>
            </div>

            <div class="profile-action justify-content-end gap-2">
              <button type="submit" class="btn btn-brand btn-sm">
                <i class="bi bi-check2 me-1"></i>Guardar
              </button>
              <button type="button" class="btn btn-outline-secondary btn-sm" data-cancel-field="telefonoField">
                Cancelar
              </button>
            </div>
          </form>
        </div>

        <!-- CORREO: edición independiente -->
        <div class="col-12">
          <form method="post" action="perfil.php" class="profile-field" id="correoField">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="correo">

            <label class="form-label" for="correo">
              Correo electrónico
            </label>

            <div class="d-flex align-items-center gap-2">
              <input
                id="correo"
                type="email"
                class="form-control"
                name="correo"
                value="<?= h($p['correo']) ?>"
                autocomplete="email"
                required
                disabled>

              <button
                type="button"
                class="btn btn-outline-brand field-edit"
                data-edit-target="correoField"
                aria-label="Editar correo electrónico"
                title="Editar correo electrónico">
                <i class="bi bi-pencil"></i>
              </button>
            </div>

            <div class="profile-action justify-content-end gap-2">
              <div class="w-100">
                <label class="form-label small mt-2" for="confirmar_correo">
                  Confirmar nuevo correo
                </label>
                <input
                  id="confirmar_correo"
                  type="email"
                  class="form-control"
                  name="confirmar_correo"
                  autocomplete="email"
                  required
                  disabled>
              </div>

              <div class="mt-3">
                <label class="form-label small" for="passwordActualCorreo">Contraseña actual</label>
                <input id="passwordActualCorreo" type="password" name="password_actual" class="form-control" autocomplete="current-password" required disabled>
                <div class="form-text">Se solicita para autorizar el cambio de correo.</div>
              </div>
            </div>

            <div class="profile-action justify-content-end gap-2">
              <button type="submit" class="btn btn-brand btn-sm">
                <i class="bi bi-envelope-check me-1"></i>Guardar correo
              </button>
              <button type="button" class="btn btn-outline-secondary btn-sm" data-cancel-field="correoField">
                Cancelar
              </button>
            </div>

            <div class="form-text">
              Si cambias el correo, se enviará un nuevo enlace de confirmación al correo indicado.
            </div>
          </form>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-md-6">
          <label class="form-label">Usuario</label>
          <input class="form-control" value="<?= h($u['usuario']) ?>" disabled>
        </div>

        <div class="col-md-6">
          <label class="form-label">Rol</label>
          <input class="form-control" value="<?= h($etiquetasRol[$rol] ?? ucfirst($rol)) ?>" disabled>
        </div>

        <?php if (!empty($p['departamento'])): ?>
          <div class="col-12">
            <label class="form-label">Departamento u oficina</label>
            <input class="form-control" value="<?= h($p['departamento']) ?>" disabled>
          </div>
        <?php endif; ?>
      </div>

    <?php endif; ?>

  </div>
</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
(() => {
  const $ = id => document.getElementById(id);

  /*
   * Habilita únicamente el campo seleccionado.
   * Los demás formularios permanecen intactos y deshabilitados.
   */
  document.querySelectorAll('[data-edit-target]').forEach(btn => {
    btn.addEventListener('click', () => {
      const field = $(btn.dataset.editTarget);
      if (!field) return;

      field.classList.add('is-editing');

      field.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach(input => {
        input.disabled = false;
      });

      btn.classList.add('d-none');

      const firstInput = field.querySelector('input:not([type="hidden"])');
      firstInput?.focus();
      firstInput?.select?.();

      /*
       * En correo también se habilita la confirmación, pero sigue siendo
       * parte exclusivamente de ese formulario.
       */
      if (field.id === 'correoField') {
        const confirm = $('confirmar_correo');
        if (confirm) {
          confirm.disabled = false;
        }
      }
    });
  });

  /*
   * Cancela solamente la operación seleccionada y restaura sus valores
   * originales sin tocar los otros campos.
   */
  document.querySelectorAll('[data-cancel-field]').forEach(btn => {
    btn.addEventListener('click', () => {
      const field = $(btn.dataset.cancelField);
      if (!field) return;

      const input = field.querySelector('input:not([type="hidden"])');
      if (input) {
        input.disabled = true;
      }

      field.querySelectorAll('input:not([type="hidden"])').forEach(el => {
        if (el.dataset.originalValue !== undefined) {
          el.value = el.dataset.originalValue;
        }
        el.disabled = true;
      });

      field.classList.remove('is-editing');

      const editBtn = field.querySelector('[data-edit-target]');
      editBtn?.classList.remove('d-none');
    });
  });

  /*
   * Guardamos los valores originales al cargar para que Cancelar no
   * deje cambios parciales.
   */
  document.querySelectorAll('.profile-field input:not([type="hidden"])').forEach(input => {
    input.dataset.originalValue = input.value;
  });

  /* ---------- Avatar independiente ---------- */
  const avatarInput = $('avatarInput');
  const avatarPreview = $('avatarPreview');
  const avatarInitials = $('avatarInitials');
  const avatarNombre = $('avatarNombre');
  const avatarAction = $('avatarAction');
  const cancelarAvatar = $('cancelarAvatar');

  let avatarOriginalSrc = avatarPreview?.src || '';
  let avatarOriginalDisplay = avatarPreview?.style.display || '';
  let initialsOriginalDisplay = avatarInitials?.style.display || '';

  avatarInput?.addEventListener('change', event => {
    const archivo = event.target.files?.[0];

    if (!archivo) {
      avatarAction?.classList.remove('d-flex');
      avatarAction?.classList.add('d-none');
      avatarNombre.textContent = 'La imagen se ajustará automáticamente al círculo.';
      return;
    }

    const tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    if (!tiposPermitidos.includes(archivo.type) || archivo.size > 5 * 1024 * 1024) {
      avatarInput.value = '';
      avatarNombre.textContent = 'Imagen no válida. Usa JPG, PNG, WEBP o GIF de hasta 5 MB.';
      avatarAction?.classList.remove('d-flex');
      avatarAction?.classList.add('d-none');
      return;
    }

    avatarNombre.textContent = archivo.name;

    const url = URL.createObjectURL(archivo);

    if (avatarPreview) {
      avatarPreview.src = url;
      avatarPreview.style.display = 'block';
    }

    if (avatarInitials) {
      avatarInitials.style.display = 'none';
    }

    avatarAction?.classList.remove('d-none');
    avatarAction?.classList.add('d-flex');
  });

  cancelarAvatar?.addEventListener('click', () => {
    avatarInput.value = '';

    if (avatarPreview) {
      avatarPreview.src = avatarOriginalSrc;
      avatarPreview.style.display = avatarOriginalDisplay;
    }

    if (avatarInitials) {
      avatarInitials.style.display = initialsOriginalDisplay;
    }

    avatarNombre.textContent = 'La imagen se ajustará automáticamente al círculo.';
    avatarAction?.classList.remove('d-flex');
    avatarAction?.classList.add('d-none');
  });

  /* Menú lateral en móvil */
  const sidebar = $('sidebar');
  $('sidebarToggle')?.addEventListener('click', () => sidebar?.classList.toggle('show'));
  sidebar?.querySelector('.sidebar-close')?.addEventListener('click', () => sidebar.classList.remove('show'));

  let ov = $('sidebarOverlay');
  if (!ov && sidebar) {
    ov = document.createElement('div');
    ov.id = 'sidebarOverlay';
    ov.className = 'sidebar-overlay';
    sidebar.after(ov);
  }

  ov?.addEventListener('click', () => sidebar?.classList.remove('show'));
})();
</script>
</body>
</html>
