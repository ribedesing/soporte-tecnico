<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo por consola.'); }
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/migrator.php';

$env = is_file(__DIR__ . '/../.env') ? (parse_ini_file(__DIR__ . '/../.env', false, INI_SCANNER_RAW) ?: []) : [];
$host = getenv('DB_HOST') ?: ($env['DB_HOST'] ?? '127.0.0.1');
$port = getenv('DB_PORT') ?: ($env['DB_PORT'] ?? '3306');
$name = getenv('DB_NAME') ?: ($env['DB_NAME'] ?? 'soporte_tecnico');
$user = getenv('DB_MIGRATION_USER') ?: ($env['DB_MIGRATION_USER'] ?? ($env['DB_USER'] ?? ''));
$pass = getenv('DB_MIGRATION_PASS') !== false ? getenv('DB_MIGRATION_PASS') : ($env['DB_MIGRATION_PASS'] ?? ($env['DB_PASS'] ?? ''));
$charset = getenv('DB_CHARSET') ?: ($env['DB_CHARSET'] ?? 'utf8mb4');

if ($user === '') { fwrite(STDERR, "DB_MIGRATION_USER no está configurado.\n"); exit(1); }
$dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
$options = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET time_zone = '-04:00'");
    echo "Aplicando migraciones de seguridad...\n";
    ejecutarMigracionesSeguridad($pdo);
    echo "Migraciones aplicadas correctamente.\n";
} catch (Throwable $e) {
    emitirLogError('No se pudieron aplicar las migraciones.', ['code'=>$e->getCode()]);
    fwrite(STDERR, "No se pudieron aplicar las migraciones. Revisa las credenciales de migración y la conexión.\n");
    exit(1);
}
