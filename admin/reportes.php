<?php
require_once __DIR__ . '/_layout.php'; requerirPermiso('reportes.ver');
$porEstado=$pdo->query("SELECT estado,COUNT(*) total FROM tickets GROUP BY estado ORDER BY total DESC")->fetchAll();
$porDep=$pdo->query("SELECT d.nombre,COUNT(t.id_tickets) total FROM departamentos d LEFT JOIN tickets t ON t.departamento_id=d.id_departamentos GROUP BY d.id_departamentos ORDER BY total DESC,d.nombre")->fetchAll();
$porAnalista=$pdo->query("SELECT COALESCE(CONCAT(p.nombre,' ',p.apellido),u.usuario) nombre,COUNT(h.id_hojas) total,SUM(h.estatus='completado') completadas,ROUND(AVG(h.tiempo_total_minutos),0) promedio FROM usuarios u JOIN roles r ON r.id_roles=u.rol_id LEFT JOIN perfiles_usuarios p ON p.usuario_id=u.id_usuarios LEFT JOIN hojas_servicio h ON h.tecnico_id=u.id_usuarios WHERE r.nombre='analista' GROUP BY u.id_usuarios,p.nombre,p.apellido ORDER BY total DESC")->fetchAll();
adminLayoutStart('Reportes');
?>
<div class="admin-page-head"><div><h2>Reportes</h2><p>Listado general de indicadores para seguimiento administrativo.</p></div><button type="button" class="btn btn-brand no-print" data-csp-print><i class="bi bi-printer me-1"></i>Imprimir reporte</button></div>
<div class="report-print">
	<div class="report-print-header">
		<img src="../assets/img/logo.png" alt="Logo del sistema">
		<div><h1>Reporte general</h1><p>Sistema de Soporte Técnico</p></div>
		<div class="report-print-date">Generado<br><?=date('d/m/Y H:i')?></div>
	</div>
	<div class="card-panel"><div class="panel-head"><h2>Listado general de indicadores</h2></div><div class="table-responsive report-table-wrap"><table class="table admin-table report-table w-100 mb-0"><thead><tr><th>Tipo</th><th>Elemento</th><th>Total</th><th>Completadas</th><th>Tiempo promedio</th></tr></thead><tbody>
	<?php foreach($porEstado as $r):?><tr><td>Tickets por estado</td><td><?=h(ucwords(str_replace('_',' ',$r['estado'])))?></td><td><strong><?=$r['total']?></strong></td><td>—</td><td>—</td></tr><?php endforeach;?>
	<?php foreach($porDep as $r):?><tr><td>Tickets por departamento</td><td><?=h($r['nombre'])?></td><td><strong><?=$r['total']?></strong></td><td>—</td><td>—</td></tr><?php endforeach;?>
	<?php foreach($porAnalista as $r):?><tr><td>Servicios por analista</td><td><?=h($r['nombre'])?></td><td><strong><?=$r['total']?></strong></td><td><?=$r['completadas']??0?></td><td><?=minutosATexto($r['promedio']!==null?(int)$r['promedio']:null)?></td></tr><?php endforeach;?>
	<?php if(!$porEstado && !$porDep && !$porAnalista):?><tr><td colspan="5" class="text-center text-muted py-4">No hay datos para mostrar.</td></tr><?php endif;?></tbody></table></div><div class="report-mobile-list">
	<?php foreach($porEstado as $r):?><article><span class="small text-muted">Tickets por estado</span><strong><?=h(ucwords(str_replace('_',' ',$r['estado'])))?></strong><span>Total: <?=$r['total']?></span></article><?php endforeach;?>
	<?php foreach($porDep as $r):?><article><span class="small text-muted">Tickets por departamento</span><strong><?=h($r['nombre'])?></strong><span>Total: <?=$r['total']?></span></article><?php endforeach;?>
	<?php foreach($porAnalista as $r):?><article><span class="small text-muted">Servicios por analista</span><strong><?=h($r['nombre'])?></strong><span><?=$r['total']?> total · <?=$r['completadas']??0?> completadas · <?=minutosATexto($r['promedio']!==null?(int)$r['promedio']:null)?></span></article><?php endforeach;?>
	</div></div></div>
</div>
<?php adminLayoutEnd(); ?>
