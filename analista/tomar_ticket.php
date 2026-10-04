<?php
require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../includes/auth.php';requerirRol(['analista']);
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: tickets.php');exit;}
if(!verificarCsrf($_POST['csrf_token']??null)){header('Location: tickets.php?error=csrf');exit;}
$u=usuarioActual();$id=(int)($_POST['id']??0);if(!$id){header('Location: tickets.php');exit;}
try{
 $pdo->beginTransaction();
 $q=$pdo->prepare("UPDATE tickets SET analista_id=?,estado='asignado',fecha_asignacion=NOW() WHERE id_tickets=? AND analista_id IS NULL AND estado='pendiente'");$q->execute([$u['id'],$id]);
 if($q->rowCount()!==1){$pdo->rollBack();header('Location: ver_ticket.php?id='.$id.'&error=tomado');exit;}
 $q=$pdo->prepare("SELECT solicitante_id,titulo,departamento_id FROM tickets WHERE id_tickets=?");$q->execute([$id]);$ticket=$q->fetch();
 $pdo->prepare("INSERT INTO auditoria_accesos(usuario_id,accion,detalle,ip) VALUES(?,?,?,?)")->execute([$u['id'],'tomar_ticket','Ticket #'.$id.' tomado',$_SERVER['REMOTE_ADDR']??null]);
 if($ticket){$ins=$pdo->prepare("INSERT INTO notificaciones(usuario_id,titulo,mensaje,url,leida) VALUES(?,?,?,?,0)");$ins->execute([(int)$ticket['solicitante_id'],'Ticket asignado','Tu ticket "'.$ticket['titulo'].'" fue asignado a un analista.','departamento/ver_ticket.php?id='.$id]);}
 if($ticket){$q=$pdo->query("SELECT u.id_usuarios FROM usuarios u INNER JOIN roles r ON r.id_roles=u.rol_id WHERE r.nombre='secretaria'");$secretarias=$q->fetchAll(PDO::FETCH_COLUMN);$ins=$pdo->prepare("INSERT INTO notificaciones(usuario_id,titulo,mensaje,url,leida) VALUES(?,?,?,?,0)");foreach($secretarias as $sid){$ins->execute([(int)$sid,'Ticket tomado','El ticket #'.str_pad((string)$id,6,'0',STR_PAD_LEFT).' fue tomado por un analista.','secretaria/tickets.php?ticket='.$id.'&aviso=tomado']);}}
 $pdo->commit();
 $_SESSION['ticket_pendiente'] = $id;
 header('Location: iniciar-servicio.php?ticket='.$id);exit;
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();die('No se pudo tomar el ticket.');}
