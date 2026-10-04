<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../includes/security.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'La sesión expiró o el token de seguridad no es válido.';
    } else {
        $mensaje = 'Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $correo = trim($_POST['correo'] ?? '');
        if (excedeLimiteAuth($pdo, 'reset-ip-hour', $ip, 10, 3600)) {
            // La misma respuesta se usa tanto para cuentas existentes como inexistentes.
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = 'Correo electrónico inválido.';
            $mensaje = '';
        } elseif (excedeLimiteAuth($pdo, 'reset-email-window', strtolower($correo), 1, 300)) {
            // Evita solicitar múltiples enlaces para una misma dirección.
        } else {
            $stmt = $pdo->prepare('SELECT u.id_usuarios, u.usuario FROM perfiles_usuarios p INNER JOIN usuarios u ON u.id_usuarios = p.usuario_id WHERE p.correo = :correo LIMIT 1');
            $stmt->execute(['correo' => $correo]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($usuario) {
                $baseUrl = urlAplicacion();
                if ($baseUrl === null) {
                    emitirLogError('APP_URL no está configurada para recuperación de contraseña.');
                } else {
                    $pdo->prepare('DELETE FROM password_resets WHERE correo = :correo')->execute(['correo' => $correo]);
                    $token = bin2hex(random_bytes(32));
                    $hash = hash('sha256', $token);
                    $expira = date('Y-m-d H:i:s', strtotime('+60 minutes'));
                    $insert = $pdo->prepare('INSERT INTO password_resets (correo, token_hash, expira_en) VALUES (:correo, :hash, :expira)');
                    $insert->execute(['correo' => $correo, 'hash' => $hash, 'expira' => $expira]);
                    $url = $baseUrl . '/auth/reset_password.php?token=' . urlencode($token);
                    $enviado = enviarCorreoRecuperacion($correo, $usuario['usuario'], $url);
                    if (!$enviado) {
                        emitirLogError('No se pudo enviar correo de recuperación.', ['usuario_id' => (int)$usuario['id_usuarios']]);
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">try{if((localStorage.getItem('soporte-theme')||'light')==='dark'){document.documentElement.classList.add('dark-mode');}}catch(e){}</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar contraseña | Soporte Técnico</title>
<link rel="icon" href="../assets/img/logo.png">
<meta name="theme-color" content="#0A3F3A">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
<link href="../assets/css/style.css" rel="stylesheet">
<link href="../assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-page">

<main class="auth-card panel-right">
  <aside class="auth-panel">
    <img class="auth-panel-logo" src="../assets/img/logo.png" alt="Logo del sistema">
    <h1>Soporte Técnico</h1>
    <p>Te enviaremos un enlace seguro para que recuperes el acceso a tu cuenta.</p>
    <a class="auth-panel-cta" href="../index.php">Volver a iniciar sesión</a>
  </aside>
  <section class="auth-main">
    <div class="auth-main-inner">
      <h2 class="auth-title">Recuperar contraseña</h2>
      <p class="auth-lead">Escribe el correo electrónico asociado a tu cuenta.</p>

      <?php if ($mensaje): ?><div class="alert alert-success" role="status"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

      <form method="post"><?= csrf_field() ?>
        <div class="auth-field">
          <label for="correo">Correo electrónico</label>
          <div class="auth-input">
            <i class="bi bi-envelope" aria-hidden="true"></i>
            <input id="correo" type="email" name="correo" autocomplete="email" placeholder="nombre@correo.com" required autofocus>
          </div>
        </div>
        <button type="submit" class="auth-submit">Enviar enlace</button>
      </form>

      <p class="auth-alt"><a class="auth-link" href="../index.php">Volver a iniciar sesión</a></p>
      <p class="auth-legal">Sistema de Soporte Técnico — proyecto de portafolio<br>
        <a href="../legal/privacidad.php">Privacidad</a> · <a href="../legal/terminos.php">Términos</a> · <a href="../legal/cookies.php">Cookies</a></p>
    </div>
  </section>
</main>
</body>
</html>
