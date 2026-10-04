<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';

// Si ya hay sesión activa, mandar directo a su módulo
if (usuarioAutenticado()) {
  $ruta = rutaDashboardPorRol($_SESSION['rol'] ?? null);
  if ($ruta) {
    header('Location: ' . $ruta . '/dashboard.php');
    exit;
  }
}

$error = $_GET['error'] ?? '';
$reset = $_GET['reset'] ?? '';
$registro = $_GET['registro'] ?? '';
$correoConfirmado = $_GET['correo_confirmado'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head><script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">try{if((localStorage.getItem('soporte-theme')||'light')==='dark'){document.documentElement.classList.add('dark-mode');}}catch(e){}</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar sesión · Soporte Técnico</title>
<link rel="icon" href="assets/img/logo.png">
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#0A3F3A">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
<link href="assets/css/style.css" rel="stylesheet">
<link href="assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-page">

<main class="auth-card panel-right">
  <aside class="auth-panel">
    <img class="auth-panel-logo" src="assets/img/logo.png" alt="Logo del sistema">
    <h1>Soporte Técnico</h1>
    <p>Registro digital de hojas de servicio y seguimiento del personal técnico.</p>
    <a class="auth-panel-cta" href="departamento/registrar.php">Crear cuenta</a>
  </aside>
  <section class="auth-main">
    <div class="auth-main-inner">
      <h2 class="auth-title">Iniciar sesión</h2>
      <p class="auth-lead">Ingresa con tu usuario y contraseña.</p>

      <?php if ($registro === '1'): ?><div class="alert alert-success" role="status">Cuenta creada con éxito. Confirma tu correo y luego inicia sesión.</div><?php elseif ($registro === '2'): ?><div class="alert alert-warning" role="status">La cuenta fue creada. Debes confirmar tu correo antes de iniciar sesión; si no recibiste el mensaje, contacta al área de soporte técnico.</div><?php endif; ?>
      <?php if ($correoConfirmado === '1'): ?><div class="alert alert-success" role="status">Correo confirmado correctamente. Ya puedes iniciar sesión.</div><?php endif; ?>
      <?php if ($reset === '1'): ?><div class="alert alert-success" role="status">Contraseña restablecida correctamente. Ya puedes iniciar sesión.</div><?php endif; ?>
      <?php if ($error === '1'): ?>
        <div class="alert alert-danger" role="alert">Credenciales inválidas.</div>
      <?php elseif ($error === '2'): ?>
        <div class="alert alert-warning" role="alert">Tu usuario está inactivo. Contacta al administrador.</div>
      <?php elseif ($error === '3'): ?>
        <div class="alert alert-warning" role="alert"><strong>Cuenta bloqueada.</strong> Se superó el límite de 3 intentos fallidos. Intenta nuevamente en 15 minutos.</div>
      <?php elseif ($error === '4'): ?>
        <div class="alert alert-danger" role="alert">La sesión expiró o el token de seguridad no es válido. Intenta nuevamente.</div>
      <?php endif; ?>

      <form action="auth/login_process.php" method="post">
        <?= csrf_field() ?>
        <div class="auth-field">
          <label for="loginUsuario">Usuario</label>
          <div class="auth-input">
            <i class="bi bi-person" aria-hidden="true"></i>
            <input id="loginUsuario" type="text" name="usuario" placeholder="ej. mpena" autocomplete="username" required autofocus>
          </div>
        </div>
        <div class="auth-field">
          <label for="loginPassword">Contraseña</label>
          <div class="auth-input">
            <i class="bi bi-lock" aria-hidden="true"></i>
            <input id="loginPassword" type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
            <button type="button" class="password-toggle" data-target="loginPassword" aria-label="Mostrar contraseña" title="Mostrar contraseña"><i class="bi bi-eye"></i></button>
          </div>
        </div>
        <div class="auth-row"><a href="auth/forgot_password.php" class="auth-link">¿Olvidaste tu clave?</a></div>
        <button type="submit" class="auth-submit">Ingresar</button>
      </form>

      <p class="auth-alt">¿No tienes cuenta? <a class="auth-link" href="departamento/registrar.php">Crear cuenta</a></p>
      <p class="auth-legal">Sistema de Soporte Técnico — proyecto de portafolio<br>
        <a href="legal/privacidad.php">Privacidad</a> · <a href="legal/terminos.php">Términos</a> · <a href="legal/cookies.php">Cookies</a></p>
    </div>
  </section>
</main>

<script src="assets/js/auth.js"></script>
</body>
</html>
