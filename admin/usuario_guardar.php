<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/password.php';
require_once __DIR__ . '/../includes/helpers.php';
requerirRol(['administrador']); requerirPermiso('usuarios.crear');
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: usuarios.php');exit;}
if(!verificarCsrf($_POST['csrf_token']??null)){header('Location: usuarios.php?error=csrf');exit;}
$nombre=trim($_POST['nombre']??'');$apellido=trim($_POST['apellido']??'');$cedula=trim($_POST['cedula']??'');
$correo=trim($_POST['correo']??'');$usuario=trim($_POST['usuario']??'');$rol=(int)($_POST['rol_id']??0);$telefono=trim($_POST['telefono']??'');$confirmacion=$_POST['password_confirmation']??'';
$cedula=strtoupper($cedula);
$rolNombre=$pdo->prepare('SELECT nombre FROM roles WHERE id_roles=?');$rolNombre->execute([$rol]);$rolNombre=$rolNombre->fetchColumn();
$dep=$rolNombre==='secretaria'?null:(($_POST['departamento_id']??'')!==''?(int)$_POST['departamento_id']:null);$pass=$_POST['password']??'';
if(!$nombre||!$apellido||!cedulaValida($cedula)||($telefono!==''&&!telefonoValido($telefono))||!filter_var($correo,FILTER_VALIDATE_EMAIL)||!$usuario||!$rol||!passwordValida($pass)||$pass!==$confirmacion){header('Location: usuarios.php?error='.rawurlencode('Datos inválidos o contraseña no válida'));exit;}
try{
 $pdo->beginTransaction();
 $s=$pdo->prepare("INSERT INTO usuarios(usuario,contrasena,rol_id,estado) VALUES(?,?,?,'activo')");
 $s->execute([$usuario,password_hash($pass,PASSWORD_DEFAULT),$rol]);
 $id=(int)$pdo->lastInsertId();
 $s=$pdo->prepare("INSERT INTO perfiles_usuarios(usuario_id,nombre,apellido,cedula,correo,telefono,confirmar_correo,departamento_id) VALUES(?,?,?,?,?,?,0,?)");
 $s->execute([$id,$nombre,$apellido,$cedula,$correo,$telefono?:null,$dep]);
 $pdo->prepare("INSERT INTO auditoria_accesos(usuario_id,accion,detalle,ip) VALUES(?,?,?,?)")->execute([usuarioActual()['id'],'crear_usuario',"Usuario {$usuario}",$_SERVER['REMOTE_ADDR']??null]);
 $pdo->commit();
 header('Location: usuarios.php?ok=Usuario+creado+correctamente');exit;
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();emitirLogError('No se pudo crear usuario.',['message'=>$e->getMessage()]);header('Location: usuarios.php?error='.rawurlencode('No se pudo crear el usuario (¿usuario, cédula o correo duplicados?)'));exit;}
