<?php
require_once __DIR__ . '/_layout.php'; requerirPermiso('auditoria.ver');
$st=$pdo->query("SELECT a.*,u.usuario,COALESCE(CONCAT(p.nombre,' ',p.apellido),u.usuario) nombre FROM auditoria_accesos a JOIN usuarios u ON u.id_usuarios=a.usuario_id LEFT JOIN perfiles_usuarios p ON p.usuario_id=u.id_usuarios ORDER BY a.created_at DESC LIMIT 200");
$rows=$st->fetchAll();$pg=paginar($rows);$rows=$pg['items'];
adminLayoutStart('Bitácora');
?>
<div class="admin-page-head"><div><h2>Bitácora de auditoría</h2><p>Registro de acciones administrativas y eventos relevantes.</p></div></div>
<div class="card-panel"><div class="table-responsive admin-audit-table-wrap"><table class="table admin-table align-middle mb-0"><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Detalle</th><th>IP</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=date('d/m/Y H:i:s',strtotime($r['created_at']))?></td><td><?=h($r['nombre'])?></td><td><span class="badge-admin badge-role"><?=h($r['accion'])?></span></td><td><?=h($r['detalle']??'')?></td><td><?=h($r['ip']??'—')?></td></tr><?php endforeach;?></tbody></table></div><div class="admin-audit-cards">
<?php foreach($rows as $r):?><article><div class="d-flex justify-content-between gap-2"><strong><?=h($r['accion'])?></strong><span class="small text-muted"><?=date('d/m/Y H:i',strtotime($r['created_at']))?></span></div><div class="small"><?=h($r['detalle']??'')?></div><div class="small text-muted mt-1"><?=h($r['nombre'])?> · IP <?=h($r['ip']??'—')?></div></article><?php endforeach;?>
</div><?=renderPaginacion($pg)?></div>
<?php adminLayoutEnd(); ?>
