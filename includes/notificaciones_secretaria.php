<?php
function generarNotificacionesSecretaria(PDO $pdo): void
{
    $secretarias = $pdo->query("SELECT u.id_usuarios
        FROM usuarios u
        INNER JOIN roles r ON r.id_roles = u.rol_id
        WHERE r.nombre = 'secretaria'")->fetchAll(PDO::FETCH_COLUMN);

    if (!$secretarias) {
        return;
    }

    $existe = $pdo->prepare('SELECT 1 FROM notificaciones WHERE usuario_id=? AND url=? LIMIT 1');
    $crear = $pdo->prepare('INSERT INTO notificaciones (usuario_id, titulo, mensaje, url, leida) VALUES (?, ?, ?, ?, 0)');

    $agregar = static function (int $usuarioId, string $titulo, string $mensaje, string $url) use ($existe, $crear): void {
        $existe->execute([$usuarioId, $url]);
        if (!$existe->fetchColumn()) {
            $crear->execute([$usuarioId, $titulo, $mensaje, $url]);
        }
    };

    $tickets = $pdo->query("SELECT id_tickets, titulo
        FROM tickets
        WHERE estado='pendiente'
          AND analista_id IS NULL
          AND fecha_creacion <= DATE_SUB(NOW(), INTERVAL 10 MINUTE)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tickets as $ticket) {
        $url = 'secretaria/tickets.php?ticket=' . (int)$ticket['id_tickets'] . '&aviso=sin_atender';
        foreach ($secretarias as $secretariaId) {
            $agregar((int)$secretariaId, 'Ticket sin atender', 'El ticket #' . str_pad((string)$ticket['id_tickets'], 6, '0', STR_PAD_LEFT) . ' lleva más de 10 minutos sin ser tomado.', $url);
        }
    }

    $hojas = $pdo->query("SELECT id_hojas, estatus
        FROM hojas_servicio
        WHERE estatus IN ('en_proceso', 'pendiente_insumos')
          AND created_at <= DATE_SUB(NOW(), INTERVAL 2 DAY)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($hojas as $hoja) {
        $url = 'secretaria/hojas.php?hoja=' . (int)$hoja['id_hojas'] . '&aviso=pendiente';
        foreach ($secretarias as $secretariaId) {
            $agregar((int)$secretariaId, 'Servicio pendiente de culminar', 'La hoja de servicio #' . (int)$hoja['id_hojas'] . ' lleva más de 2 días sin culminar.', $url);
        }
    }

    $analistas = $pdo->query("SELECT u.id_usuarios, u.usuario
        FROM usuarios u
        INNER JOIN roles r ON r.id_roles = u.rol_id
        LEFT JOIN perfiles_usuarios p ON p.usuario_id = u.id_usuarios
        WHERE r.nombre='analista'
          AND u.fecha_creacion <= DATE_SUB(NOW(), INTERVAL 3 DAY)
          AND (p.id_perfil IS NULL OR p.nombre IS NULL OR p.apellido IS NULL OR p.correo IS NULL OR p.cedula IS NULL)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($analistas as $analista) {
        $url = 'secretaria/analista_detalle.php?id=' . (int)$analista['id_usuarios'] . '&aviso=perfil_pendiente';
        foreach ($secretarias as $secretariaId) {
            $agregar((int)$secretariaId, 'Perfil de analista incompleto', 'El analista ' . $analista['usuario'] . ' lleva más de 3 días sin completar su perfil.', $url);
        }
    }
}

function generarNotificacionesAdministrador(PDO $pdo): void
{
    $administradores = $pdo->query("SELECT u.id_usuarios
        FROM usuarios u
        INNER JOIN roles r ON r.id_roles = u.rol_id
        WHERE r.nombre = 'administrador' AND u.estado = 'activo'")->fetchAll(PDO::FETCH_COLUMN);

    if (!$administradores) {
        return;
    }

    $eventos = $pdo->query("SELECT a.id_auditoria, a.accion, a.detalle, a.usuario_id, a.created_at, u.usuario
        FROM auditoria_accesos a
        INNER JOIN usuarios u ON u.id_usuarios = a.usuario_id
        ORDER BY a.id_auditoria DESC
        LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
    $existe = $pdo->prepare('SELECT 1 FROM notificaciones WHERE usuario_id=? AND url=? LIMIT 1');
    $crear = $pdo->prepare('INSERT INTO notificaciones (usuario_id, titulo, mensaje, url, leida) VALUES (?, ?, ?, ?, 0)');

    foreach ($eventos as $evento) {
        $url = 'admin/auditoria.php?evento=' . (int)$evento['id_auditoria'];
        $titulo = 'Actividad del sistema';
        $detalle = trim((string)($evento['detalle'] ?? ''));
        $mensaje = $evento['usuario'] . ' realizó ' . str_replace('_', ' ', $evento['accion'])
            . ($detalle !== '' ? ': ' . $detalle : '.');

        foreach ($administradores as $administradorId) {
            $existe->execute([(int)$administradorId, $url]);
            if (!$existe->fetchColumn()) {
                $crear->execute([(int)$administradorId, $titulo, $mensaje, $url]);
            }
        }
    }
}
