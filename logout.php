<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && usuarioAutenticado()) {
	echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Cerrar sesión</title></head><body style="font-family:sans-serif;max-width:420px;margin:15vh auto;padding:1rem"><h1>Cerrar sesión</h1><p>Confirma que deseas salir del sistema.</p><form method="post" action="logout.php">' . csrf_field() . '<button type="submit">Cerrar sesión</button></form></body></html>';
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	header('Allow: POST');
	exit('Método no permitido.');
}

if (!verificarCsrf($_POST['csrf_token'] ?? null)) {
	http_response_code(403);
	exit('Solicitud no válida.');
}

$usuarioId = $_SESSION['usuario_id'] ?? null;

// La sesión debe cerrarse aunque la actualización de actividad falle.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
	$parametros = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000, $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']);
}
session_destroy();

if ($usuarioId !== null) {
	require_once __DIR__ . '/config/database.php';
	$pdo->prepare('UPDATE usuarios SET ultima_actividad = NULL WHERE id_usuarios = ?')->execute([$usuarioId]);
}

header('Location: index.php');
exit;
