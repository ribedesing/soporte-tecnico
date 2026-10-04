<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=UTF-8');

if (!usuarioAutenticado() || !in_array($_SESSION['rol'] ?? '', ['secretaria', 'administrador'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
}

try {
    $q = $pdo->query("SELECT u.id_usuarios, (u.ultima_actividad IS NOT NULL AND u.ultima_actividad >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)) AS en_linea FROM usuarios u JOIN roles r ON r.id_roles = u.rol_id WHERE LOWER(TRIM(r.nombre)) IN ('analista', 'tecnico', 'técnico')");
    echo json_encode(['ok' => true, 'analistas' => $q->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
