<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verificarCsrf($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
}

header('Content-Type: application/json; charset=UTF-8');

if (!usuarioAutenticado()) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

try {
    $pdo->prepare('UPDATE usuarios SET ultima_actividad = NOW() WHERE id_usuarios = ?')
        ->execute([(int)$_SESSION['usuario_id']]);
    $_SESSION['ultima_actividad_pulso'] = time();
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
