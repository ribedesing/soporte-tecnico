<?php
require_once __DIR__ . '/_layout.php'; requerirPermiso('roles.ver');
$roles=$pdo->query("SELECT r.*,COUNT(u.id_usuarios) usuarios FROM roles r LEFT JOIN usuarios u ON u.rol_id=r.id_roles GROUP BY r.id_roles ORDER BY r.id_roles")->fetchAll();$pgRoles=paginar($roles,15,'pagina_roles');$roles=$pgRoles['items'];
$permisos=$pdo->query("SELECT * FROM permisos ORDER BY modulo,clave")->fetchAll();$pgPermisos=paginar($permisos,15,'pagina_permisos');$permisos=$pgPermisos['items'];
$asign=$pdo->query("SELECT rp.rol_id,rp.permiso_id FROM rol_permisos rp")->fetchAll();
$map=[];foreach($asign as $a)$map[$a['rol_id'].':'.$a['permiso_id']]=1;
adminLayoutStart('Roles y permisos');
?>
<div class="admin-page-head"><div><h2>Roles y permisos</h2><p>Define qué áreas del sistema están disponibles para cada rol.</p></div></div>
<div class="card-panel mb-3"><div class="panel-head"><h2>Roles</h2></div><div class="table-responsive admin-roles-table-wrap"><table class="table admin-table mb-0"><thead><tr><th>Rol</th><th>Descripción</th><th>Usuarios</th><th></th></tr></thead><tbody>
<?php foreach($roles as $r):?><tr><td><strong><?=h(ucfirst($r['nombre']))?></strong></td><td><?=h($r['descripcion']??'')?></td><td><?=$r['usuarios']?></td><td class="text-end"><a class="btn btn-sm btn-outline-brand" href="rol_permisos.php?id=<?=$r['id_roles']?>">Administrar permisos</a></td></tr><?php endforeach;?>
</tbody></table></div><div class="admin-role-cards">
<?php foreach($roles as $r):?><article class="admin-role-card"><div><h3><?=h(ucfirst($r['nombre']))?></h3><p><?=h($r['descripcion']??'')?></p><span class="small text-muted"><i class="bi bi-people me-1"></i><?=$r['usuarios']?> usuarios</span></div><a class="btn btn-sm btn-outline-brand" href="rol_permisos.php?id=<?=$r['id_roles']?>" title="Administrar permisos"><i class="bi bi-shield-check me-1"></i>Permisos</a></article><?php endforeach;?>
</div><?=renderPaginacion($pgRoles)?></div>
<div class="card-panel"><div class="panel-head"><h2>Permisos disponibles</h2></div><div class="panel-body"><div class="row g-2"><?php foreach($permisos as $p):?><div class="col-md-6"><div class="admin-permission"><i class="bi bi-check2-circle"></i><div><strong><?=h($p['clave'])?></strong><div class="small text-muted"><?=h($p['descripcion'])?></div></div></div></div><?php endforeach;?></div></div><?=renderPaginacion($pgPermisos)?></div>
<?php adminLayoutEnd(); ?>
