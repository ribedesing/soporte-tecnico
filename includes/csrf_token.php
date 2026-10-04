<?php
require_once __DIR__ . '/../includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !usuarioAutenticado()) { http_response_code(403); exit; }
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['csrf_token' => csrf_token()], JSON_UNESCAPED_SLASHES);
