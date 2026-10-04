<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/password.php';

requerirRol(['secretaria', 'administrador']);


/*
|--------------------------------------------------------------------------
| Solo aceptar POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: analistas.php');
    exit;
}

if (!verificarCsrf($_POST['csrf_token'] ?? null)) {
    header('Location: analistas.php?error=csrf');
    exit;
}


/*
|--------------------------------------------------------------------------
| Datos enviados
|--------------------------------------------------------------------------
*/

$usuario = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';


/*
|--------------------------------------------------------------------------
| Validar usuario
|--------------------------------------------------------------------------
*/

if ($usuario === '') {
    header('Location: analistas.php?error=3');
    exit;
}


/*
|--------------------------------------------------------------------------
| Validar contraseña
|--------------------------------------------------------------------------
*/

if (!passwordValida($password)) {
    header('Location: analistas.php?error=1');
    exit;
}


/*
|--------------------------------------------------------------------------
| Buscar rol de técnico / analista
|--------------------------------------------------------------------------
|
| La tabla roles utiliza:
|
| id_roles
| nombre
|
*/

$stmtRol = $pdo->prepare("
    SELECT id_roles
    FROM roles
    WHERE LOWER(TRIM(nombre)) IN ('tecnico', 'técnico', 'analista')
    LIMIT 1
");

$stmtRol->execute();

$rol = $stmtRol->fetchColumn();


/*
|--------------------------------------------------------------------------
| Verificar que exista el rol
|--------------------------------------------------------------------------
*/

if (!$rol) {
    header('Location: analistas.php?error=4');
    exit;
}


/*
|--------------------------------------------------------------------------
| Verificar si el usuario ya existe
|--------------------------------------------------------------------------
*/

$stmtExiste = $pdo->prepare("
    SELECT id_usuarios
    FROM usuarios
    WHERE usuario = ?
    LIMIT 1
");

$stmtExiste->execute([$usuario]);

if ($stmtExiste->fetchColumn()) {
    header('Location: analistas.php?error=2');
    exit;
}


/*
|--------------------------------------------------------------------------
| Crear contraseña segura
|--------------------------------------------------------------------------
*/

$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);


/*
|--------------------------------------------------------------------------
| Registrar analista
|--------------------------------------------------------------------------
|
| Estructura real de usuarios:
|
| id_usuarios
| usuario
| contrasena
| rol_id
| ultima_actividad
| fecha_creacion
|
*/

try {

    $stmt = $pdo->prepare("
        INSERT INTO usuarios (
            usuario,
            contrasena,
            rol_id,
            fecha_creacion
        )
        VALUES (
            ?,
            ?,
            ?,
            NOW()
        )
    ");

    $stmt->execute([
        $usuario,
        $passwordHash,
        (int)$rol
    ]);


    /*
    |--------------------------------------------------------------------------
    | Guardar temporalmente las credenciales
    |--------------------------------------------------------------------------
    |
    | Se utilizan para mostrarlas una sola vez en
    | analista_credenciales.php.
    |
    */

    $_SESSION['credencial_nueva'] = [
        'usuario'  => $usuario,
        'password' => $password
    ];


    /*
    |--------------------------------------------------------------------------
    | Redirigir
    |--------------------------------------------------------------------------
    */

    header('Location: analista_credenciales.php');
    exit;


} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | Usuario duplicado
    |--------------------------------------------------------------------------
    */

    if ($e->getCode() === '23000') {

        header('Location: analistas.php?error=2');
        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Error inesperado
    |--------------------------------------------------------------------------
    */

    error_log(
        'Error registrando analista: ' . $e->getMessage()
    );

    header('Location: analistas.php?error=5');
    exit;
}