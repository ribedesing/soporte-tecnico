<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requerirRol(['administrador']); requerirPermiso('usuarios.estado'); $u=usuarioActual();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios.php'); exit;
}

$destino=$_POST['return']??'';
$id=(int)($_POST['id']??0);

if (!verificarCsrf($_POST['csrf_token']??null)) {
    header($destino==='detalle'?'Location: usuario_detalle.php?id='.$id.'&error=csrf':'Location: usuarios.php?error=csrf');exit;
}

$estado=($_POST['estado']??'')==='activo'?'activo':'inactivo';
if(!$id || $id===(int)$u['id']){header('Location: usuarios.php?error=Operación+no+permitida');exit;}
$st=$pdo->prepare("UPDATE usuarios SET estado=? WHERE id_usuarios=?");$st->execute([$estado,$id]);
$pdo->prepare("INSERT INTO auditoria_accesos(usuario_id,accion,detalle,ip) VALUES(?,?,?,?)")->execute([$u['id'],'cambiar_estado_usuario',"Usuario #{$id} → {$estado}",$_SERVER['REMOTE_ADDR']??null]);
header($destino==='detalle'?'Location: usuario_detalle.php?id='.$id.'&ok=Estado+actualizado':'Location: usuarios.php?ok=Estado+actualizado');exit;
