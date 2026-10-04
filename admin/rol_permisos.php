<?php
require_once __DIR__ . '/_layout.php'; requerirPermiso('roles.ver');
$id=(int)($_GET['id']??0);$st=$pdo->prepare("SELECT * FROM roles WHERE id_roles=?");$st->execute([$id]);$rol=$st->fetch();if(!$rol){header('Location: roles.php');exit;}
$perms=$pdo->query("SELECT * FROM permisos ORDER BY modulo,clave")->fetchAll();$q=$pdo->prepare("SELECT permiso_id FROM rol_permisos WHERE rol_id=?");$q->execute([$id]);$sel=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));
adminLayoutStart('Permisos de rol');
?>
<div class="admin-page-head"><div><a href="roles.php" class="small text-decoration-none">← Roles</a><h2 class="mt-1">Permisos: <?=h(ucfirst($rol['nombre']))?></h2><p><?=h($rol['descripcion']??'')?></p></div></div>
<form method="post" action="rol_permisos_guardar.php"><?= csrf_field() ?><input type="hidden" name="rol_id" value="<?=$id?>">
<div class="row g-3"><?php $mod='';foreach($perms as $p): if($mod!==$p['modulo']):$mod=$p['modulo'];?><div class="col-12"><h5 class="text-uppercase text-muted small mt-2 mb-0"><?=h($mod)?></h5></div><?php endif;?>
<div class="col-md-6"><label class="admin-permission"><input class="form-check-input" type="checkbox" name="permisos[]" value="<?=$p['id']?>" <?=in_array((int)$p['id'],$sel,true)?'checked':''?>><span><strong><?=h($p['clave'])?></strong><small><?=h($p['descripcion'])?></small></span></label></div>
<?php endforeach;?></div><div class="mt-4"><button class="btn btn-brand">Guardar permisos</button></div></form>
<?php adminLayoutEnd(); ?>
