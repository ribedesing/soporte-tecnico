<?php

function ejecutarMigracionesSeguridad(PDO $pdo): void {
    $versionSesion = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'version_sesion'")->fetch();
    if (!$versionSesion) {
        $pdo->exec('ALTER TABLE usuarios ADD COLUMN version_sesion INT UNSIGNED NOT NULL DEFAULT 0');
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_intentos (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario VARCHAR(100) NOT NULL,
            ip VARCHAR(45) DEFAULT NULL,
            fallidos INT UNSIGNED NOT NULL DEFAULT 0,
            bloqueado_hasta DATETIME NULL DEFAULT NULL,
            actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_login_intentos_usuario (usuario)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS auth_rate_limits (
            scope VARCHAR(32) NOT NULL,
            key_hash CHAR(64) NOT NULL,
            bucket_start INT UNSIGNED NOT NULL,
            hits INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (scope, key_hash, bucket_start),
            KEY idx_auth_rate_bucket (bucket_start)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");


    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cambios_correo_pendientes (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id INT NOT NULL,
            correo_nuevo VARCHAR(190) NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expira_en DATETIME NOT NULL,
            usado_en DATETIME NULL DEFAULT NULL,
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_cambio_correo_token (token_hash),
            KEY idx_cambio_correo_usuario (usuario_id),
            KEY idx_cambio_correo_expira (expira_en),
            CONSTRAINT fk_cambio_correo_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id_usuarios) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS csrf_tokens (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            token CHAR(64) NOT NULL,
            usuario_id INT DEFAULT NULL,
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uk_csrf_tokens_token (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}
