<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
requerirRol(['administrador']); requerirPermiso('departamentos.gestionar');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: departamentos.php'); exit; }
if (!verificarCsrf($_POST['csrf_token']??null)) { header('Location: departamentos.php?error=csrf'); exit; }
$id=(int)($_POST['id']??0);$e=($_POST['estado']??'')==='activo'?'activo':'inactivo';
$pdo->prepare("UPDATE departamentos SET estado=? WHERE id_departamentos=?")->execute([$e,$id]);
$pdo->prepare('INSERT INTO auditoria_accesos(usuario_id,accion,detalle,ip) VALUES(?,?,?,?)')->execute([usuarioActual()['id'],'estado_departamento',"Departamento #{$id} → {$e}",$_SERVER['REMOTE_ADDR']??null]);
header('Location: departamentos.php?ok=Estado+actualizado');exit;
