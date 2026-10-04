<?php
require_once __DIR__ . '/_layout.php';

$kpis = [
 'usuarios' => (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
 'activos' => (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado='activo'")->fetchColumn(),
 'tickets' => (int)$pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn(),
 'pendientes' => (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE estado IN ('pendiente','asignado','en_proceso')")->fetchColumn(),
 'hojas' => (int)$pdo->query("SELECT COUNT(*) FROM hojas_servicio")->fetchColumn(),
 'hojas_mes' => (int)$pdo->query("SELECT COUNT(*) FROM hojas_servicio WHERE YEAR(fecha)=YEAR(CURDATE()) AND MONTH(fecha)=MONTH(CURDATE())")->fetchColumn(),
];
$actividad = $pdo->query("
 SELECT a.*, u.usuario, COALESCE(CONCAT(p.nombre,' ',p.apellido),u.usuario) nombre_completo
 FROM auditoria_accesos a JOIN usuarios u ON u.id_usuarios=a.usuario_id
 LEFT JOIN perfiles_usuarios p ON p.usuario_id=u.id_usuarios
 ORDER BY a.created_at DESC LIMIT 5")->fetchAll();
$ultimosTickets = $pdo->query("
 SELECT t.id_tickets,t.titulo,t.estado,t.fecha_creacion,d.nombre departamento,
 COALESCE(CONCAT(p.nombre,' ',p.apellido),u.usuario) solicitante
 FROM tickets t JOIN departamentos d ON d.id_departamentos=t.departamento_id
 JOIN usuarios u ON u.id_usuarios=t.solicitante_id LEFT JOIN perfiles_usuarios p ON p.usuario_id=u.id_usuarios
 ORDER BY t.fecha_creacion DESC LIMIT 5")->fetchAll();

adminLayoutStart('Mi Panel');
?>
<div class="admin-page-head"><div><h2>Panel de administración</h2><p>Control general, supervisión y seguridad del sistema.</p></div></div>

<div class="row g-3 mb-4">
<?php
$cards=[
 ['Usuarios','usuarios','bi-people','teal','usuarios.php'],
 ['Usuarios activos','activos','bi-person-check','green','usuarios.php?estado=activo'],
 ['Tickets pendientes','pendientes','bi-ticket-detailed','amber','tickets.php?estado=pendiente'],
 ['Tickets totales','tickets','bi-ticket','blue','tickets.php'],
 ['Hojas de servicio','hojas','bi-file-earmark-text','red','hojas.php'],
 ['Hojas de este mes','hojas_mes','bi-calendar3','purple','hojas.php?periodo=mes'],
];
foreach($cards as [$label,$key,$icon,$tone,$url]): ?>
<div class="col-6 col-xl-2"><a class="admin-kpi-link" href="<?=h($url)?>"><div class="kpi-card admin-kpi">
 <div class="kpi-icon admin-tone-<?=h($tone)?>"><i class="bi <?=h($icon)?>"></i></div>
 <div class="kpi-label mt-2"><?=h($label)?></div><div class="kpi-value"><?=$kpis[$key]?></div>
</div></a></div>
<?php endforeach; ?>
</div>

<div class="row g-3">
 <div class="col-lg-7">
  <div class="card-panel h-100">
   <div class="panel-head"><h2>Tickets recientes</h2><a class="btn btn-sm btn-outline-brand" href="tickets.php">Ver todos</a></div>
   <div class="admin-list">
   <?php foreach($ultimosTickets as $t): ?>
    <a class="admin-list-row" href="tickets.php?ver=<?=(int)$t['id_tickets']?>">
      <div class="admin-list-icon"><i class="bi bi-ticket-detailed"></i></div>
      <div class="flex-grow-1 min-w-0"><div class="fw-semibold text-truncate"><?=h($t['titulo'])?></div><div class="small text-muted"><?=h($t['departamento'])?> · <?=h($t['solicitante'])?></div></div>
      <span class="badge-admin badge-<?=h($t['estado'])?>"><?=h(ucwords(str_replace('_',' ',$t['estado'])))?></span>
    </a>
   <?php endforeach; ?>
   <?php if(!$ultimosTickets): ?><div class="p-4 text-muted">No hay tickets registrados.</div><?php endif; ?>
   </div>
  </div>
 </div>
 <div class="col-lg-5">
  <div class="card-panel h-100">
   <div class="panel-head"><h2>Actividad reciente</h2><a class="btn btn-sm btn-outline-brand" href="auditoria.php">Bitácora</a></div>
   <div class="admin-list">
   <?php foreach($actividad as $a): ?>
    <div class="admin-list-row">
      <div class="admin-list-icon"><i class="bi bi-clock-history"></i></div>
      <div class="flex-grow-1"><div class="fw-semibold"><?=h($a['accion'])?></div><div class="small text-muted"><?=h($a['nombre_completo'])?> · <?=date('d/m/Y H:i',strtotime($a['created_at']))?></div><div class="small"><?=h($a['detalle']??'')?></div></div>
    </div>
   <?php endforeach; ?>
   <?php if(!$actividad): ?><div class="p-4 text-muted">No hay actividad registrada.</div><?php endif; ?>
   </div>
  </div>
 </div>
</div>
<?php adminLayoutEnd(); ?>
