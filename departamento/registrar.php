<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

if (usuarioAutenticado()) {
    header('Location: dashboard.php');
    exit;
}
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head><script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">try{if((localStorage.getItem('soporte-theme')||'light')==='dark'){document.documentElement.classList.add('dark-mode');}}catch(e){}</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crear cuenta | Soporte Técnico</title>
<link rel="icon" href="../assets/img/logo.png">
<link rel="manifest" href="../manifest.json">
<meta name="theme-color" content="#0A3F3A">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
<link href="../assets/css/style.css?v=20261001-blue" rel="stylesheet">
<link href="../assets/css/auth.css?v=20261001-blue" rel="stylesheet">
</head>
<body class="auth-page">

<main class="auth-card panel-left is-wide">
  <aside class="auth-panel">
    <img class="auth-panel-logo" src="../assets/img/logo.png" alt="Logo del sistema">
    <h1>Soporte Técnico</h1>
    <p>Crea tu cuenta para solicitar asistencia técnica y seguir tus tickets.</p>
    <a class="auth-panel-cta" href="../index.php">Iniciar sesión</a>
  </aside>
  <section class="auth-main">
    <div class="auth-main-inner">
      <h2 class="auth-title">Crear cuenta</h2>
      <p class="auth-lead">Completa tus datos para solicitar soporte.</p>

      <?php if ($error === '1'): ?><div class="alert alert-danger" role="alert">Todos los campos obligatorios deben completarse.</div><?php elseif ($error === '2'): ?><div class="alert alert-danger" role="alert">El usuario, correo o cédula ya está registrado.</div><?php elseif ($error === '3'): ?><div class="alert alert-danger" role="alert">La contraseña debe tener mínimo 12 caracteres, mayúscula, minúscula, número y símbolo.</div><?php elseif ($error === '4'): ?><div class="alert alert-danger" role="alert">Las contraseñas no coinciden.</div><?php elseif ($error === '5'): ?><div class="alert alert-danger" role="alert">No se pudo enviar el correo de confirmación. Verifica la configuración SMTP.</div><?php elseif ($error === '6'): ?><div class="alert alert-danger" role="alert">La cédula debe tener formato V- o E- seguido de 8 números y el teléfono formato 0424-1234567.</div><?php elseif ($error === '7'): ?><div class="alert alert-danger" role="alert">Debes aceptar los Términos de uso y la Política de privacidad para crear la cuenta.</div><?php elseif ($error === '8'): ?><div class="alert alert-warning" role="alert">Se alcanzó el límite de solicitudes. Espera un momento e inténtalo nuevamente.</div><?php endif; ?>
      <form action="registrar_procesar.php" method="post"><?= csrf_field() ?>
        <div class="auth-grid">
        <div class="auth-field">
          <label for="nombre">Nombre</label>
          <div class="auth-input"><i class="bi bi-person" aria-hidden="true"></i><input id="nombre" type="text" name="nombre" required autocomplete="given-name"></div>
        </div>
        <div class="auth-field">
          <label for="apellido">Apellido</label>
          <div class="auth-input"><i class="bi bi-person" aria-hidden="true"></i><input id="apellido" type="text" name="apellido" required autocomplete="family-name"></div>
        </div>
        <div class="auth-field">
          <label for="cedula">Cédula</label>
          <div class="auth-input"><i class="bi bi-person-vcard" aria-hidden="true"></i><input id="cedula" type="text" name="cedula" pattern="[VE]-[0-9]{8}" maxlength="10" placeholder="V-00000000" required></div>
        </div>
        <div class="auth-field">
          <label for="telefono">Teléfono</label>
          <div class="auth-input"><i class="bi bi-telephone" aria-hidden="true"></i><input id="telefono" type="text" name="telefono" pattern="(0424|0414|0412|0422|0426|0416)-[0-9]{7}" maxlength="12" placeholder="0424-1234567" autocomplete="tel" required></div>
        </div>
        <div class="auth-field">
          <label for="correo">Correo</label>
          <div class="auth-input"><i class="bi bi-envelope" aria-hidden="true"></i><input id="correo" type="email" name="correo" required autocomplete="email"></div>
        </div>
        <div class="auth-field">
          <label for="departamento">Departamento u oficina</label>
          <div class="auth-input"><i class="bi bi-building" aria-hidden="true"></i><input id="departamento" type="text" name="departamento" placeholder="Ej. Recursos Humanos" required maxlength="100"></div>
        </div>
        <div class="auth-field is-full">
          <label for="usuario">Usuario</label>
          <div class="auth-input"><i class="bi bi-at" aria-hidden="true"></i><input id="usuario" type="text" name="usuario" required autocomplete="username"></div>
        </div>
        <div class="auth-field">
          <label for="password">Contraseña</label>
          <div class="auth-input">
            <i class="bi bi-lock" aria-hidden="true"></i>
            <input id="password" type="password" name="password" autocomplete="new-password" required>
            <button type="button" class="password-toggle" data-target="password" aria-label="Mostrar contraseña" title="Mostrar contraseña"><i class="bi bi-eye"></i></button>
          </div>
          <p class="auth-hint">Mínimo 12 caracteres, mayúscula, minúscula, número y símbolo.</p>
        </div>
        <div class="auth-field">
          <label for="password_confirmation">Confirmar contraseña</label>
          <div class="auth-input">
            <i class="bi bi-lock" aria-hidden="true"></i>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
            <button type="button" class="password-toggle" data-target="password_confirmation" aria-label="Mostrar contraseña" title="Mostrar contraseña"><i class="bi bi-eye"></i></button>
          </div>
        </div>
        </div>
        <label class="auth-check" for="acepta_terminos">
          <input type="checkbox" name="acepta_terminos" id="acepta_terminos" value="1" required>
          <span>Acepto los <a href="../legal/terminos.php" target="_blank" rel="noopener">Términos de uso</a> y la <a href="../legal/privacidad.php" target="_blank" rel="noopener">Política de privacidad</a>.</span>
        </label>
        <button class="auth-submit" type="submit">Crear cuenta</button>
      </form>

      <p class="auth-alt">¿Ya tienes cuenta? <a class="auth-link" href="../index.php">Iniciar sesión</a></p>
      <p class="auth-legal">Sistema de Soporte Técnico — proyecto de portafolio<br>
        <a href="../legal/privacidad.php">Privacidad</a> · <a href="../legal/terminos.php">Términos</a> · <a href="../legal/cookies.php">Cookies</a></p>
    </div>
  </section>
</main>

<script src="../assets/js/auth.js"></script>
</body>
</html>
