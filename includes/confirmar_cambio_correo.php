<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$ok = false; $error = false; $correo = '';
if (!preg_match('/^[a-f0-9]{64}$/', $token)) $error = true;
if (!$error) {
    $q = $pdo->prepare('SELECT id,usuario_id,correo_nuevo FROM cambios_correo_pendientes WHERE token_hash=? AND usado_en IS NULL AND expira_en>NOW() LIMIT 1');
    $q->execute([hash('sha256',$token)]);
    $cambio = $q->fetch();
    if (!$cambio) $error = true; else $correo = $cambio['correo_nuevo'];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    if (!verificarCsrf($_POST['csrf_token'] ?? null)) $error = true;
    else {
        try {
            $pdo->beginTransaction();
            $q=$pdo->prepare('SELECT id,usuario_id,correo_nuevo FROM cambios_correo_pendientes WHERE token_hash=? AND usado_en IS NULL AND expira_en>NOW() LIMIT 1 FOR UPDATE');
            $q->execute([hash('sha256',$token)]); $cambio=$q->fetch();
            if(!$cambio) throw new RuntimeException('token_invalido');
            $ocupado=$pdo->prepare('SELECT 1 FROM perfiles_usuarios WHERE LOWER(correo)=LOWER(?) AND usuario_id<>? LIMIT 1');
            $ocupado->execute([$cambio['correo_nuevo'],$cambio['usuario_id']]);
            if($ocupado->fetchColumn()) throw new RuntimeException('correo_duplicado');
            $actual = $pdo->prepare('SELECT correo FROM perfiles_usuarios WHERE usuario_id=? LIMIT 1 FOR UPDATE');
            $actual->execute([$cambio['usuario_id']]);
            if (strcasecmp((string)$actual->fetchColumn(), (string)$cambio['correo_nuevo']) !== 0) {
                throw new RuntimeException('correo_cambio');
            }
            $pdo->prepare('UPDATE perfiles_usuarios SET confirmar_correo=1 WHERE usuario_id=?')->execute([$cambio['usuario_id']]);
            $pdo->prepare('UPDATE cambios_correo_pendientes SET usado_en=NOW() WHERE id=?')->execute([$cambio['id']]);
            $pdo->prepare('DELETE FROM cambios_correo_pendientes WHERE usuario_id=? AND usado_en IS NULL')->execute([$cambio['usuario_id']]);
            $pdo->prepare('INSERT INTO auditoria_accesos(usuario_id,accion,detalle,ip) VALUES(?,?,?,?)')->execute([$cambio['usuario_id'],'confirmar_cambio_correo','Confirmó su nuevo correo electrónico',$_SERVER['REMOTE_ADDR']??null]);
            $pdo->commit(); $ok=true;
        } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); emitirLogError('No se pudo confirmar cambio de correo.',['code'=>$e->getCode()]); $error=true; }
    }
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Cambio de correo</title><link rel="icon" href="../assets/img/logo.png"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"></head><body class="bg-light"><main class="container py-5"><div class="card shadow-sm mx-auto" style="max-width:620px"><div class="card-body p-4 text-center"><h1 class="h3 mb-3">Confirmación del nuevo correo</h1><?php if($ok):?><div class="alert alert-success">El nuevo correo fue confirmado correctamente.</div><a class="btn btn-success" href="../index.php">Ir al inicio de sesión</a><?php elseif($error):?><div class="alert alert-danger">El enlace no es válido, ya venció, ya fue utilizado o el correo dejó de estar disponible.</div><a class="btn btn-secondary" href="../index.php">Ir al inicio</a><?php else:?><p>Vas a confirmar <strong><?=htmlspecialchars($correo,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')?></strong> como nuevo correo de tu cuenta.</p><form method="post"><?=csrf_field()?><input type="hidden" name="token" value="<?=htmlspecialchars($token,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')?>"><button class="btn btn-success" type="submit">Confirmar nuevo correo</button></form><?php endif;?></div></div></main></body></html>
