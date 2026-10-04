<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/password.php';
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'La sesión expiró o el token de seguridad no es válido.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirmacion'] ?? '';
        if (!passwordValida($password)) $error = 'La contraseña no cumple los requisitos.';
        elseif ($password !== $confirm) $error = 'Las contraseñas no coinciden.';
        else {
            $hash = hash('sha256', $token);
            $stmt = $pdo->prepare('SELECT id, correo FROM password_resets WHERE token_hash = :hash AND usado_en IS NULL AND expira_en > NOW() ORDER BY id DESC LIMIT 1');
            $stmt->execute(['hash' => $hash]);
            $reset = $stmt->fetch();
            if (!$reset) $error = 'El enlace es inválido, usado o venció.';
            else {
                $pdo->beginTransaction();
                try {
                    $consumirToken = $pdo->prepare('UPDATE password_resets SET usado_en = NOW() WHERE id = :id AND usado_en IS NULL AND expira_en > NOW()');
                    $consumirToken->execute(['id' => $reset['id']]);
                    if ($consumirToken->rowCount() !== 1) {
                        throw new RuntimeException('El enlace ya fue utilizado o venció.');
                    }

                    $updateUser = $pdo->prepare('UPDATE usuarios u JOIN perfiles_usuarios p ON p.usuario_id = u.id_usuarios SET u.contrasena = :pass, u.version_sesion = u.version_sesion + 1 WHERE p.correo = :correo');
                    $updateUser->execute(['pass' => password_hash($password, PASSWORD_DEFAULT), 'correo' => $reset['correo']]);
                    $pdo->commit();
                    header('Location: ../index.php?reset=1');
                    exit;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    emitirLogError('No se pudo restablecer la contraseña.', ['message' => $e->getMessage()]);
                    $error = 'Ocurrió un error al restablecer la contraseña.';
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
<title>Nueva contraseña | Soporte Técnico</title>
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

<main class="auth-card panel-left">
  <aside class="auth-panel">
    <img class="auth-panel-logo" src="../assets/img/logo.png" alt="Logo del sistema">
    <h1>Soporte Técnico</h1>
    <p>Define una nueva contraseña para volver a ingresar al sistema.</p>
    <a class="auth-panel-cta" href="../index.php">Volver a iniciar sesión</a>
  </aside>
  <section class="auth-main">
    <div class="auth-main-inner">
      <div class="auth-badge" aria-hidden="true"><i class="bi bi-shield-lock"></i></div>
      <h2 class="auth-title">Crear nueva contraseña</h2>
      <p class="auth-lead">Elige una contraseña segura para proteger tu cuenta.</p>

      <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
      <?php if (!$_POST && !$token): ?><div class="alert alert-warning" role="alert">No se recibió el token. Revisa el enlace.</div><?php endif; ?>

      <form method="post"><?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
        <div class="auth-field">
          <label for="password">Nueva contraseña</label>
          <div class="auth-input">
            <i class="bi bi-lock" aria-hidden="true"></i>
            <input id="password" type="password" name="password" minlength="12" autocomplete="new-password" required autofocus>
            <button type="button" class="password-toggle" data-target="password" aria-label="Mostrar contraseña" title="Mostrar contraseña"><i class="bi bi-eye"></i></button>
          </div>
        </div>
        <div class="auth-field">
          <label for="password_confirmacion">Confirmar contraseña</label>
          <div class="auth-input">
            <i class="bi bi-lock-fill" aria-hidden="true"></i>
            <input id="password_confirmacion" type="password" name="password_confirmacion" minlength="12" autocomplete="new-password" required>
            <button type="button" class="password-toggle" data-target="password_confirmacion" aria-label="Mostrar contraseña" title="Mostrar contraseña"><i class="bi bi-eye"></i></button>
          </div>
          <p class="auth-hint">Mínimo 12 caracteres, mayúscula, minúscula, número y símbolo.</p>
        </div>
        <button class="auth-submit" type="submit">Restablecer contraseña</button>
      </form>

      <p class="auth-alt"><a class="auth-link" href="../index.php">Volver a iniciar sesión</a></p>
      <p class="auth-legal">Sistema de Soporte Técnico — proyecto de portafolio<br>
        <a href="../legal/privacidad.php">Privacidad</a> · <a href="../legal/terminos.php">Términos</a> · <a href="../legal/cookies.php">Cookies</a></p>
    </div>
  </section>
</main>

<script src="../assets/js/auth.js"></script>
</body>
</html>
