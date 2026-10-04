<?php

/* Seguridad transversal: debe cargarse antes de session_start(). */
function soporteSolicitudHttps(): bool
{
    if (getenv('APP_TRUST_PROXY') === '1') {
        $proto = strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
        if ($proto !== '') {
            return $proto === 'https';
        }
    }

    return (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

if (getenv('APP_FORCE_HTTPS') === '1' && !soporteSolicitudHttps()) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if ($host !== '' && !preg_match('/[\r\n]/', $host)) {
        header('Location: https://' . $host . $uri, true, 308);
        exit;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => soporteSolicitudHttps() || getenv('APP_FORCE_HTTPS') === '1',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!function_exists('emitirLogError')) {
    function emitirLogError(string $mensaje, array $context = []): void {
        $timestamp = date('c');
        $payload = ['time' => $timestamp, 'message' => $mensaje];
        if ($context !== []) $payload['context'] = $context;

        $logDir = getenv('SOPORTE_LOG_DIR') ?: (__DIR__ . '/../logs');
        if (!is_dir($logDir) && !@mkdir($logDir, 0750, true) && !is_dir($logDir)) $logDir = null;
        $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        if ($logDir) @file_put_contents($logDir . '/app.log', $line, FILE_APPEND | LOCK_EX);
        error_log($line);
    }
}

if (!defined('SOPORTE_MANEJADOR_ERRORES')) {
    define('SOPORTE_MANEJADOR_ERRORES', true);
    /* Producción nunca muestra detalles internos. APP_DEBUG solo debe existir en desarrollo. */
    $depurar = getenv('APP_DEBUG') === '1' && getenv('APP_ENV') !== 'production';
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    if (!$depurar) {
        set_exception_handler(static function (Throwable $e): void {
            emitirLogError('Excepción no controlada', [
                'mensaje' => $e->getMessage(),
                'archivo' => basename($e->getFile()),
                'linea' => $e->getLine(),
            ]);
            if (!headers_sent()) http_response_code(500);
            echo '<!doctype html><meta charset="utf-8"><title>Error</title><p style="font-family:sans-serif;padding:2rem">Ocurrió un error inesperado. Intenta de nuevo o contacta a soporte.</p>';
        });
    }
}


if (!function_exists('soporteCspNonce')) {
    function soporteCspNonce(): string {
        static $nonce = null;
        if ($nonce === null) $nonce = base64_encode(random_bytes(18));
        return $nonce;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) $_SESSION['csrf_tokens'] = [];
        /* Cada formulario recibe un token aleatorio distinto; se consume una sola vez. */
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_tokens'][$token] = time();
        /* Limpieza de tokens viejos y límite para evitar crecimiento de sesión. */
        foreach ($_SESSION['csrf_tokens'] as $t => $created) {
            if (!is_int($created) || $created < time() - 7200) unset($_SESSION['csrf_tokens'][$t]);
        }
        if (count($_SESSION['csrf_tokens']) > 100) $_SESSION['csrf_tokens'] = array_slice($_SESSION['csrf_tokens'], -100, null, true);
        return $token;
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    }
}

if (!function_exists('verificarCsrf')) {
    function verificarCsrf(?string $token): bool {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) return false;
        $tokens = $_SESSION['csrf_tokens'] ?? [];
        if (!is_array($tokens) || !isset($tokens[$token])) return false;
        $creado = (int)$tokens[$token];
        unset($_SESSION['csrf_tokens'][$token]);
        return $creado >= time() - 7200;
    }
}

if (!function_exists('excedeLimiteAuth')) {
    function excedeLimiteAuth(PDO $pdo, string $scope, string $identificador, int $maximo, int $ventana): bool {
        $ahora = time();
        $inicioVentana = intdiv($ahora, $ventana) * $ventana;
        $hash = hash('sha256', strtolower(trim($identificador)));
        $stmt = $pdo->prepare('INSERT INTO auth_rate_limits (scope, key_hash, bucket_start, hits) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE hits = hits + 1');
        $stmt->execute([$scope, $hash, $inicioVentana]);
        $stmt = $pdo->prepare('SELECT hits FROM auth_rate_limits WHERE scope = ? AND key_hash = ? AND bucket_start = ?');
        $stmt->execute([$scope, $hash, $inicioVentana]);
        $intentos = (int)$stmt->fetchColumn();
        if (random_int(1, 100) === 1) $pdo->prepare('DELETE FROM auth_rate_limits WHERE bucket_start < ?')->execute([$ahora - 86400]);
        return $intentos > $maximo;
    }
}

if (!function_exists('enviarCabecerasSeguridad')) {
    function enviarCabecerasSeguridad(): void {
        if (headers_sent()) return;
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
        header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action 'self'; img-src 'self' data: blob:; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; script-src 'self' 'nonce-" . soporteCspNonce() . "' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; script-src-attr 'none'; connect-src 'self'; upgrade-insecure-requests");
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');
        if (soporteSolicitudHttps()) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/* También se aplican a páginas públicas como login/registro. */
enviarCabecerasSeguridad();
