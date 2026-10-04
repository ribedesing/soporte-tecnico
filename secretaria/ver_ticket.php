<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requerirRol(['secretaria', 'administrador']);
$seccion_activa = 'tickets';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: tickets.php');
    exit;
}

$stmt = $pdo->prepare("SELECT
    t.*,
    d.nombre AS departamento,
    COALESCE(NULLIF(TRIM(CONCAT(COALESCE(p.nombre, ''), ' ', COALESCE(p.apellido, ''))), ''), su.usuario) AS solicitante,
    COALESCE(NULLIF(TRIM(CONCAT(COALESCE(ap.nombre, ''), ' ', COALESCE(ap.apellido, ''))), ''), au.usuario, 'Sin asignar') AS analista
    FROM tickets t
    JOIN departamentos d ON d.id_departamentos = t.departamento_id
    JOIN usuarios su ON su.id_usuarios = t.solicitante_id
    LEFT JOIN perfiles_usuarios p ON p.usuario_id = su.id_usuarios
    LEFT JOIN usuarios au ON au.id_usuarios = t.analista_id
    LEFT JOIN perfiles_usuarios ap ON ap.usuario_id = au.id_usuarios
    WHERE t.id_tickets = ?
    LIMIT 1");
$stmt->execute([$id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header('Location: tickets.php');
    exit;
}

function estadoTicketSecretaria(string $estado): array
{
    return match ($estado) {
        'completado' => ['badge-completado', 'Completado'],
        'en_proceso' => ['badge-proceso', 'En proceso'],
        'asignado' => ['badge-proceso', 'Asignado'],
        'cancelado' => ['badge-pendiente', 'Cancelado'],
        default => ['badge-pendiente', 'Pendiente'],
    };
}

[$estadoClase, $estadoTexto] = estadoTicketSecretaria((string)$ticket['estado']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ticket #<?= str_pad((string)$id, 6, '0', STR_PAD_LEFT) ?> | Secretaría</title>
<link rel="icon" href="../assets/img/logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
.ticket-detail-description {
    white-space: pre-wrap;
    line-height: 1.7;
    overflow-wrap: anywhere;
}
.detail-item {
    height: 100%;
    padding: 14px 16px;
    border: 1px solid var(--border, #e7e7e7);
    border-radius: 12px;
    background: var(--surface-soft, #fafafa);
}
.detail-label {
    display: block;
    font-size: .78rem;
    color: #6c757d;
    margin-bottom: 4px;
}
.detail-value {
    font-weight: 500;
    overflow-wrap: anywhere;
}
@media (max-width: 576px) {
    .ticket-actions .btn { width: 100%; }
}
</style>
</head>
<body>
<div class="app-shell">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>
        <div class="content">
            <div class="card-panel" style="max-width: 950px; margin: 0 auto;">
                <div class="panel-head">
                    <div>
                        <div class="small text-muted mb-1">Detalle de solicitud</div>
                        <h2 class="mb-0">Ticket #<?= str_pad((string)$id, 6, '0', STR_PAD_LEFT) ?></h2>
                    </div>
                    <span class="<?= $estadoClase ?> badge"><?= h($estadoTexto) ?></span>
                </div>

                <div class="panel-body">
                    <h3 class="fs-4 mb-2"><?= h($ticket['titulo']) ?></h3>

                    <div class="ticket-detail-description mt-3 mb-4"><?= h($ticket['descripcion']) ?></div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="detail-item">
                                <span class="detail-label"><i class="bi bi-building me-1"></i> Departamento</span>
                                <div class="detail-value"><?= h($ticket['departamento']) ?></div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-item">
                                <span class="detail-label"><i class="bi bi-person me-1"></i> Solicitante</span>
                                <div class="detail-value"><?= h($ticket['solicitante'] ?: 'Sin solicitante') ?></div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-item">
                                <span class="detail-label"><i class="bi bi-person-badge me-1"></i> Analista asignado</span>
                                <div class="detail-value"><?= h($ticket['analista'] ?: 'Sin asignar') ?></div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-item">
                                <span class="detail-label"><i class="bi bi-flag me-1"></i> Estado</span>
                                <div class="detail-value"><?= h($estadoTexto) ?></div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="detail-item">
                                <span class="detail-label"><i class="bi bi-calendar3 me-1"></i> Fecha de creación</span>
                                <div class="detail-value"><?= date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])) ?></div>
                            </div>
                        </div>
                        <?php if (!empty($ticket['fecha_actualizacion'])): ?>
                        <div class="col-12 col-md-6">
                            <div class="detail-item">
                                <span class="detail-label"><i class="bi bi-clock-history me-1"></i> Última actualización</span>
                                <div class="detail-value"><?= date('d/m/Y H:i', strtotime($ticket['fecha_actualizacion'])) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="ticket-actions d-flex flex-wrap gap-2 mt-4">
                        <a href="tickets.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Volver a tickets
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar')?.classList.toggle('show');
});
</script>
</body>
</html>
