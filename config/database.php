<?php
require_once __DIR__ . '/../includes/security.php';

date_default_timezone_set('America/Caracas');

$env = is_file(__DIR__ . '/../.env')
    ? (parse_ini_file(__DIR__ . '/../.env', false, INI_SCANNER_RAW) ?: [])
    : [];

$DB_HOST = $env['DB_HOST'] ?? '127.0.0.1';
$DB_PORT = $env['DB_PORT'] ?? '3306';
$DB_NAME = $env['DB_NAME'] ?? 'soporte_tecnico';
$DB_USER = $env['DB_USER'] ?? 'soporte_app';
$DB_PASS = $env['DB_PASS'] ?? '';
$DB_CHARSET = $env['DB_CHARSET'] ?? 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset={$DB_CHARSET}";

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $opciones);
    $pdo->exec("SET time_zone = '-04:00'");
} catch (PDOException $e) {
    emitirLogError('No se pudo conectar a la base de datos.', [
        'host' => $DB_HOST,
        'port' => $DB_PORT,
        'database' => $DB_NAME,
        'user' => $DB_USER,
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
    ]);

    http_response_code(500);
    die('No se pudo conectar a la base de datos. Revisa la configuración del servidor.');
}
