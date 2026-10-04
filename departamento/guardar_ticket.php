<?php
require_once __DIR__.'/../config/database.php'; require_once __DIR__.'/../includes/auth.php';
requerirRol(['departamento']);
requerirCorreoConfirmado($pdo);
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: crear_ticket.php');exit;}
if(!verificarCsrf($_POST['csrf_token']??null)){header('Location: crear_ticket.php?error=csrf');exit;}
$u=usuarioActual(); $titulo=trim($_POST['titulo']??''); $descripcion=trim($_POST['descripcion']??'');
if(!$titulo||!$descripcion||!$u['departamento_id']){header('Location: crear_ticket.php?error=1');exit;}
try{
 $pdo->beginTransaction();
 $q=$pdo->prepare("INSERT INTO tickets(solicitante_id,departamento_id,titulo,descripcion,estado) VALUES(?,?,?,?, 'pendiente')");$q->execute([$u['id'],$u['departamento_id'],$titulo,$descripcion]);$ticketId=(int)$pdo->lastInsertId();
 $pdo->prepare("INSERT INTO auditoria_accesos(usuario_id,accion,detalle,ip) VALUES(?,?,?,?)")->execute([$u['id'],'crear_ticket','Ticket #'.$ticketId.' creado: '.$titulo,$_SERVER['REMOTE_ADDR']??null]);
 $q=$pdo->query("SELECT u.id_usuarios FROM usuarios u INNER JOIN roles r ON r.id_roles=u.rol_id WHERE r.nombre='analista'");$analistas=$q->fetchAll(PDO::FETCH_COLUMN);
 $mensaje=$u['departamento_nombre'].' necesita asistencia con: '.$titulo.'.';
 $ins=$pdo->prepare("INSERT INTO notificaciones(usuario_id,titulo,mensaje,url,leida) VALUES(?,?,?,?,0)");
 foreach($analistas as $aid){$ins->execute([(int)$aid,'Nuevo ticket',$mensaje,'analista/ver_ticket.php?id='.$ticketId]);}
 $q=$pdo->query("SELECT u.id_usuarios FROM usuarios u INNER JOIN roles r ON r.id_roles=u.rol_id WHERE r.nombre='secretaria'");$secretarias=$q->fetchAll(PDO::FETCH_COLUMN);
 foreach($secretarias as $sid){$ins->execute([(int)$sid,'Nuevo ticket recibido','El departamento envió el ticket "'. $titulo .'".','secretaria/tickets.php?ticket='.$ticketId]);}
 $pdo->commit(); header('Location: ver_ticket.php?id='.$ticketId.'&creado=1'); exit;
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();emitirLogError('No se pudo crear el ticket.',['usuario_id'=>$u['id'],'code'=>$e->getCode()]);header('Location: crear_ticket.php?error=1');exit;}
