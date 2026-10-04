<?php
require_once __DIR__ . '/_layout.php'; requerirPermiso('usuarios.ver');
$estado=$_GET['estado']??'';
$where=[];$params=[];
if(in_array($estado,['activo','inactivo'],true)){ $where[]='u.estado=?';$params[]=$estado; }
$sql="SELECT u.id_usuarios,u.usuario,u.estado,u.ultima_actividad,u.fecha_creacion,r.nombre rol,
COALESCE(p.nombre,'') nombre,COALESCE(p.apellido,'') apellido,COALESCE(p.correo,'') correo,
CASE WHEN r.nombre='secretaria' THEN NULL ELSE d.nombre END departamento
FROM usuarios u JOIN roles r ON r.id_roles=u.rol_id
LEFT JOIN perfiles_usuarios p ON p.usuario_id=u.id_usuarios
LEFT JOIN departamentos d ON d.id_departamentos=p.departamento_id";
if($where)$sql.=' WHERE '.implode(' AND ',$where);
$sql.=" ORDER BY (u.estado='activo') DESC, r.id_roles, p.nombre,p.apellido";
$st=$pdo->prepare($sql);$st->execute($params);$usuarios=$st->fetchAll();$pg=paginar($usuarios);$usuarios=$pg['items'];
$roles=$pdo->query("SELECT id_roles,nombre FROM roles ORDER BY id_roles")->fetchAll();
$departamentos=$pdo->query("SELECT id_departamentos,nombre FROM departamentos WHERE estado='activo' ORDER BY nombre")->fetchAll();
$mensaje=$_GET['ok']??'';
adminLayoutStart('Usuarios');
?>
<div class="admin-page-head"><div><h2>Usuarios</h2><p>Estado, roles y consulta de cuentas del sistema.</p></div>
<button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#nuevoUsuario"><i class="bi bi-plus-lg me-1"></i>Nuevo usuario</button></div>
<?php if($mensaje): ?><div class="alert alert-success"><?=h($mensaje)?></div><?php endif;?>
<?php if(isset($_GET['error'])): ?><div class="alert alert-danger"><?=h($_GET['error'])?></div><?php endif;?>
<div class="card-panel">
 <div class="panel-head flex-wrap gap-2"><h2>Listado de usuarios</h2>
  <form method="get">
   <select name="estado" class="form-select form-select-sm" aria-label="Filtrar usuarios por estado" data-csp-submit-change>
    <option value="" <?= $estado===''?'selected':'' ?>>Todos</option>
    <option value="activo" <?= $estado==='activo'?'selected':'' ?>>Activos</option>
    <option value="inactivo" <?= $estado==='inactivo'?'selected':'' ?>>Inactivos</option>
   </select>
  </form>
 </div>
 <div class="table-responsive"><table class="table align-middle admin-table admin-user-table w-100 mb-0"><thead><tr><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Departamento</th><th>Estado</th><th>Última actividad</th><th></th></tr></thead>
 <tbody><?php foreach($usuarios as $us): $online=$us['ultima_actividad'] && strtotime($us['ultima_actividad'])>=time()-900; ?>
 <tr>
  <td><strong><?=h($us['usuario'])?></strong></td><td><?=h(trim($us['nombre'].' '.$us['apellido'])?:'Perfil pendiente')?></td>
  <td><span class="badge-admin badge-role"><?=h(ucfirst($us['rol']))?></span></td><td><?=h($us['departamento']??'—')?></td>
  <td><span class="badge-admin <?= $us['estado']==='activo'?'badge-completado':'badge-pendiente' ?>"><?=h(ucfirst($us['estado']))?></span>
   <?php if($online): ?><small class="text-success ms-1">● en línea</small><?php endif;?>
  </td>
  <td><?= $us['ultima_actividad'] && $us['ultima_actividad']!=='0000-00-00 00:00:00' ? date('d/m/Y H:i',strtotime($us['ultima_actividad'])):'Nunca' ?></td>
    <td class="text-end"><a class="btn btn-sm btn-light border" href="usuario_detalle.php?id=<?=(int)$us['id_usuarios']?>" title="Ver detalle"><i class="bi bi-eye"></i></a></td>
 </tr>
 <?php endforeach;?></tbody></table></div>
 <div class="admin-user-cards">
 <?php foreach($usuarios as $us): $online=$us['ultima_actividad'] && strtotime($us['ultima_actividad'])>=time()-900; ?>
     <article class="ticket-card admin-user-card">
         <div class="ticket-stub <?= $us['estado']==='activo'?'st-completado':'st-pendiente' ?>"><span><?=h(strtoupper(substr($us['rol'],0,3)))?></span></div>
         <div class="ticket-body">
             <div class="t-top"><p class="t-title mb-1"><?=h(trim($us['nombre'].' '.$us['apellido'])?:$us['usuario'])?></p><span class="badge-admin <?= $us['estado']==='activo'?'badge-completado':'badge-pendiente' ?>"><?=h(ucfirst($us['estado']))?></span></div>
             <div class="t-meta"><strong><?=h($us['usuario'])?></strong> · <?=h(ucfirst($us['rol']))?><?php if($online): ?> · <span class="text-success">En línea</span><?php endif;?></div>
             <div class="t-meta d-flex align-items-center justify-content-between gap-2 flex-wrap"><span><i class="bi bi-building me-1"></i><?=h($us['departamento']??'—')?> · <i class="bi bi-clock me-1"></i><?= $us['ultima_actividad'] && $us['ultima_actividad']!=='0000-00-00 00:00:00' ? date('d/m/Y H:i',strtotime($us['ultima_actividad'])):'Nunca' ?></span><a class="btn btn-sm btn-light border" href="usuario_detalle.php?id=<?=(int)$us['id_usuarios']?>" title="Ver detalle"><i class="bi bi-eye"></i></a></div>
         </div>
     </article>
 <?php endforeach; ?>
 </div>
 <?=renderPaginacion($pg)?>
</div>

<div class="modal fade" id="nuevoUsuario" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><form method="post" action="usuario_guardar.php"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Registrar usuario</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Nombre</label><input name="nombre" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Apellido</label><input name="apellido" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Cédula</label><input name="cedula" class="form-control" pattern="[VE]-[0-9]{8}" maxlength="10" placeholder="V-00000000" required></div>
<div class="col-md-6"><label class="form-label">Correo</label><input name="correo" type="email" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Usuario</label><input name="usuario" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Rol</label><select name="rol_id" id="nuevoUsuarioRol" class="form-select" required><?php foreach($roles as $r):?><option value="<?=$r['id_roles']?>" data-rol="<?=h($r['nombre'])?>"><?=h(ucfirst($r['nombre']))?></option><?php endforeach;?></select></div>
<div class="col-md-6" id="nuevoUsuarioDepartamento"><label class="form-label">Departamento</label><select name="departamento_id" class="form-select"><option value="">Sin asignar</option><?php foreach($departamentos as $d):?><option value="<?=$d['id_departamentos']?>"><?=h($d['nombre'])?></option><?php endforeach;?></select></div>
<div class="col-md-6"><label class="form-label">Teléfono</label><input name="telefono" class="form-control" pattern="(0424|0414|0412|0422|0426|0416)-[0-9]{7}" maxlength="12" placeholder="0400-0000000"></div>
<div class="col-md-6"><label class="form-label">Contraseña temporal</label><div class="input-group"><input id="adminNewPassword" name="password" type="password" class="form-control" required minlength="12"><button class="btn btn-outline-secondary password-toggle" type="button" data-target="adminNewPassword"><i class="bi bi-eye"></i></button></div></div>
<div class="col-md-6"><label class="form-label">Confirmar contraseña</label><div class="input-group"><input id="adminNewPasswordConfirm" name="password_confirmation" type="password" class="form-control" required minlength="12"><button class="btn btn-outline-secondary password-toggle" type="button" data-target="adminNewPasswordConfirm"><i class="bi bi-eye"></i></button></div></div>
</div><div class="form-text mt-3">Mínimo 12 caracteres, mayúscula, minúscula, número y símbolo.</div>
</div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Crear usuario</button></div>
</form></div></div></div>
<script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
(() => {
    const rol = document.getElementById('nuevoUsuarioRol');
    const departamento = document.getElementById('nuevoUsuarioDepartamento');
    const actualizarDepartamento = () => {
        const esSecretaria = rol?.selectedOptions[0]?.dataset.rol === 'secretaria';
        if (departamento) {
            departamento.hidden = esSecretaria;
            if (esSecretaria) departamento.querySelector('select').value = '';
        }
    };
    rol?.addEventListener('change', actualizarDepartamento);
    actualizarDepartamento();
})();
document.querySelectorAll('.password-toggle').forEach(button => button.addEventListener('click', () => {
    const input = document.getElementById(button.dataset.target);
    const icon = button.querySelector('i');
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    icon.classList.toggle('bi-eye', !visible);
    icon.classList.toggle('bi-eye-slash', visible);
}));
</script>
<?php adminLayoutEnd(); ?>
