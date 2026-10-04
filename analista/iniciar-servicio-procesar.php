<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requerirRol(['analista']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (empty($_POST['departamento_id']) && empty($_POST['ticket_id']))) {
    header('Location: iniciar-servicio.php');
    exit;
}

if (!verificarCsrf($_POST['csrf_token'] ?? null)) {
    header('Location: iniciar-servicio.php?error=csrf');
    exit;
}

$ticketId = (int)($_POST['ticket_id'] ?? 0);
$departamentoId = (int)($_POST['departamento_id'] ?? 0);
if ($ticketId > 0) {
    $stmt = $pdo->prepare("SELECT id_tickets, departamento_id FROM tickets WHERE id_tickets=? AND analista_id=? AND estado IN ('asignado','en_proceso')");
    $stmt->execute([$ticketId, usuarioActual()['id']]);
    $ticket = $stmt->fetch();
    if (!$ticket) {
        header('Location: tickets.php?error=tomado');
        exit;
    }
    $departamentoId = (int)$ticket['departamento_id'];
}
if ($departamentoId <= 0) {
    header('Location: iniciar-servicio.php');
    exit;
}

$_SESSION['hoja_en_curso'] = [
    'departamento_id' => $departamentoId,
    'ticket_id'      => $ticketId ?: null,
    'fecha'           => date('Y-m-d'),
    'hora_inicio'     => date('H:i:s'),
    'iniciado_ts'     => time(),
];
unset($_SESSION['ticket_pendiente']);

header('Location: hoja-servicio.php?iniciada=1');
exit;