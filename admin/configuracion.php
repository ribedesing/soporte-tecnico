<?php
require_once __DIR__ . '/_layout.php'; requerirPermiso('configuracion.editar');
$rows=$pdo->query("SELECT clave,valor,descripcion FROM configuracion_sistema ORDER BY clave")->fetchAll();$cfg=[];foreach($rows as $r)$cfg[$r['clave']]=$r['valor'];
$defaults=['nombre_sistema'=>'Soporte Técnico','institucion'=>'Mi Organización','correo_institucional'=>'','telefono_institucional'=>'','direccion_institucional'=>'','timezone'=>'America/Caracas'];
adminLayoutStart('Configuración');
?>
<div class="admin-page-head"><div><h2>Configuración del sistema</h2><p>Datos institucionales que se muestran en el sistema y documentos.</p></div></div>
<form method="post" action="configuracion_guardar.php"><?= csrf_field() ?><div class="card-panel"><div class="panel-head"><h2>Información institucional</h2></div><div class="panel-body"><div class="row g-3">
<?php foreach($defaults as $k=>$def):?><div class="col-md-6"><label class="form-label"><?=h(ucwords(str_replace('_',' ',$k)))?></label><input class="form-control" name="config[<?=h($k)?>]" value="<?=h($cfg[$k]??$def)?>"></div><?php endforeach;?>
</div><div class="mt-4"><button class="btn btn-brand">Guardar configuración</button></div></div></div></form>
<div class="card-panel mt-3"><div class="panel-head"><h2>Numeración de hojas</h2></div><div class="panel-body"><p class="mb-0 text-muted">La numeración de las hojas utiliza el identificador autoincremental de <code>hojas_servicio</code> y se presenta con seis dígitos, por ejemplo <strong>000012</strong>. No se modifica manualmente para evitar duplicados.</p></div></div>
<?php adminLayoutEnd(); ?>
