<?php
require_once __DIR__ . '/_layout.php'; requerirPermiso('usuarios.ver');
$id=(int)($_GET['id']??0);
$st=$pdo->prepare("SELECT u.*,r.nombre rol,p.*,d.nombre departamento FROM usuarios u JOIN roles r ON r.id_roles=u.rol_id LEFT JOIN perfiles_usuarios p ON p.usuario_id=u.id_usuarios LEFT JOIN departamentos d ON d.id_departamentos=p.departamento_id WHERE u.id_usuarios=?");
$st->execute([$id]);$us=$st->fetch();if(!$us){header('Location: usuarios.php');exit;}
$q=$pdo->prepare("SELECT COUNT(*) FROM tickets WHERE solicitante_id=? OR analista_id=?");$q->execute([$id,$id]);$tickets=(int)$q->fetchColumn();
$q=$pdo->prepare("SELECT COUNT(*) FROM hojas_servicio WHERE tecnico_id=?");$q->execute([$id]);$hojas=(int)$q->fetchColumn();
$q=$pdo->prepare("SELECT * FROM auditoria_accesos WHERE usuario_id=? ORDER BY created_at DESC LIMIT 10");$q->execute([$id]);$aud=$q->fetchAll();
adminLayoutStart('Detalle de usuario');
?>
<div class="admin-page-head"><div><a href="usuarios.php" class="small text-decoration-none">← Usuarios</a><h2 class="mt-1"><?=h(trim(($us['nombre']??'').' '.($us['apellido']??''))?:$us['usuario'])?></h2><p>Detalle de cuenta, perfil y actividad.</p></div><div class="d-flex gap-2">
<?php if((int)$us['id_usuarios']!==(int)$u['id']): ?><form method="post" action="usuario_estado.php" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?=(int)$us['id_usuarios']?>"><input type="hidden" name="estado" value="<?=$us['estado']==='activo'?'inactivo':'activo'?>"><input type="hidden" name="return" value="detalle"><button type="submit" class="btn btn-sm <?= $us['estado']==='activo'?'btn-outline-danger':'btn-outline-success' ?>"><i class="bi bi-power me-1"></i><?= $us['estado']==='activo'?'Desactivar':'Activar' ?></button></form><?php endif; ?>
</div></div>
<div class="row g-3">
<div class="col-xl-8"><div class="card-panel h-100"><div class="panel-head"><h2>Información de cuenta</h2></div><div class="panel-body">
<div class="row g-3">
<?php $campos=[['Usuario',$us['usuario']],['Rol',ucfirst($us['rol'])],['Estado',ucfirst($us['estado'])],['Cédula',$us['cedula']??'—'],['Correo',$us['correo']??'—']]; if($us['rol']!=='secretaria') $campos[]=['Departamento',$us['departamento']??'—']; $campos[]=['Creado',date('d/m/Y H:i',strtotime($us['fecha_creacion']))]; $campos[]=['Última actividad',($us['ultima_actividad']&&$us['ultima_actividad']!=='0000-00-00 00:00:00')?date('d/m/Y H:i',strtotime($us['ultima_actividad'])):'Nunca']; foreach($campos as [$l,$v]):?>
<div class="col-md-6"><div class="admin-field-label"><?=h($l)?></div><div class="admin-field-value"><?=h((string)$v)?></div></div>
<?php endforeach;?>
</div></div></div></div>
<div class="col-xl-4"><div class="card-panel mb-3"><div class="panel-head"><h2>Actividad</h2></div><div class="panel-body"><div class="row text-center"><div class="col-6"><div class="display-6 fw-bold"><?=$hojas?></div><div class="small text-muted">Hojas</div></div><div class="col-6"><div class="display-6 fw-bold"><?=$tickets?></div><div class="small text-muted">Tickets relacionados</div></div></div></div></div>
<div class="card-panel"><div class="panel-head"><h2>Últimas acciones</h2></div><?php foreach($aud as $a):?><div class="admin-list-row"><div class="admin-list-icon"><i class="bi bi-journal"></i></div><div><strong><?=h($a['accion'])?></strong><div class="small text-muted"><?=date('d/m/Y H:i',strtotime($a['created_at']))?> · <?=h($a['detalle']??'')?></div></div></div><?php endforeach;?></div>
</div></div>
<?php adminLayoutEnd(); ?>
