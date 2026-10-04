<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$estado = $_GET['estado'] ?? '';
$confirmado = false;
$error = false;
$perfil = null;

if ($token !== '') {
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        $error = true;
    } else {
        $q = $pdo->prepare('SELECT c.id,c.usuario_id,p.nombre,p.apellido,p.correo FROM confirmaciones_correo c INNER JOIN perfiles_usuarios p ON p.usuario_id=c.usuario_id WHERE c.token_hash=? AND c.usado_en IS NULL AND c.expira_en>NOW() LIMIT 1');
        $q->execute([hash('sha256', $token)]);
        $perfil = $q->fetch();
        if (!$perfil) $error = true;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
        if (!verificarCsrf($_POST['csrf_token'] ?? null)) {
            $error = true;
        } else {
            try {
                $pdo->beginTransaction();
                $q = $pdo->prepare('SELECT id,usuario_id FROM confirmaciones_correo WHERE token_hash=? AND usado_en IS NULL AND expira_en>NOW() LIMIT 1 FOR UPDATE');
                $q->execute([hash('sha256', $token)]);
                $r = $q->fetch();
                if (!$r) throw new RuntimeException('token_invalido');
                $pdo->prepare('UPDATE perfiles_usuarios SET confirmar_correo=1 WHERE usuario_id=?')->execute([$r['usuario_id']]);
                $pdo->prepare('UPDATE confirmaciones_correo SET usado_en=NOW() WHERE id=?')->execute([$r['id']]);
                $pdo->commit();
                header('Location: ../index.php?correo_confirmado=1');
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                emitirLogError('No se pudo confirmar el correo.', ['code' => $e->getCode()]);
                $error = true;
            }
        }
    }
} else {
    requerirRol(['departamento']);
    $uid = (int)$_SESSION['usuario_id'];
    $q = $pdo->prepare('SELECT p.correo,p.confirmar_correo,p.nombre,p.apellido FROM perfiles_usuarios p WHERE p.usuario_id=? LIMIT 1');
    $q->execute([$uid]);
    $perfil = $q->fetch();
    if (!$perfil) { http_response_code(500); exit('No se encontró el perfil de la cuenta.'); }
    if ((int)$perfil['confirmar_correo'] === 1) { header('Location: dashboard.php'); exit; }
}
?>
<!DOCTYPE html><html lang="es"><head><script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">try{if((localStorage.getItem('soporte-theme')||'light')==='dark'){document.documentElement.classList.add('dark-mode');}}catch(e){}</script><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Confirmar correo | Soporte Técnico</title><link rel="icon" href="../assets/img/logo.png"><meta name="theme-color" content="#0A3F3A"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700&display=swap" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous"><link href="../assets/css/style.css?v=20261001-blue" rel="stylesheet"><link href="../assets/css/auth.css?v=20261001-blue" rel="stylesheet"></head><body class="auth-page"><main class="auth-card single"><section class="auth-main"><div class="auth-main-inner"><div class="auth-badge" aria-hidden="true"><i class="bi bi-envelope-check"></i></div><h2 class="auth-title">Confirmación de correo</h2>
<?php if ($token !== ''): ?>
  <?php if ($error): ?><div class="alert alert-danger" role="alert">El enlace de confirmación es inválido, ya venció o ya fue utilizado.</div><a href="../index.php" class="auth-submit" style="text-decoration:none;display:flex">Ir al inicio de sesión</a>
  <?php else: ?><p class="auth-lead">Confirma el correo <strong><?=h($perfil['correo'])?></strong> para activar la cuenta.</p><form method="post"><?=csrf_field()?><input type="hidden" name="token" value="<?=h($token)?>"><button class="auth-submit" type="submit">Confirmar correo</button></form><?php endif; ?>
<?php else: ?>
  <p class="auth-lead">Hola <?=h(($perfil['nombre']??'').' '.($perfil['apellido']??''))?>. Para poder crear y consultar tickets debes confirmar primero tu correo.</p>
  <?php if($estado==='enviado'): ?><div class="alert alert-success" role="status">Te enviamos un nuevo enlace de confirmación a <strong><?=h($perfil['correo'])?></strong>.</div><?php elseif($estado==='error'): ?><div class="alert alert-danger" role="alert">No se pudo enviar el correo.</div><?php endif; ?>
  <div class="alert alert-warning text-start"><strong>Correo registrado:</strong><br><?=h($perfil['correo'])?><br><small>Revisa también la carpeta de spam o correo no deseado.</small></div>
  <form method="post" action="reenviar_confirmacion.php"><?=csrf_field()?><button class="auth-submit" type="submit">Reenviar correo de confirmación</button></form><p class="auth-alt" style="display:block"><a class="auth-link" href="../logout.php">Cerrar sesión</a></p>
<?php endif; ?><p class="auth-legal">Sistema de Soporte Técnico — proyecto de portafolio<br><a href="../legal/privacidad.php">Privacidad</a> · <a href="../legal/terminos.php">Términos</a> · <a href="../legal/cookies.php">Cookies</a></p></div></section></main></body></html>
