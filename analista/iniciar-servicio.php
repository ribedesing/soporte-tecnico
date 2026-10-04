<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requerirRol(['analista']);
$u = usuarioActual();

$departamentos = $pdo->query("SELECT id_departamentos, nombre FROM departamentos WHERE estado='activo' ORDER BY nombre")->fetchAll();

$ticket = null;
$ticketId = (int)($_GET['ticket'] ?? ($_SESSION['ticket_pendiente'] ?? 0));
if ($ticketId > 0) {
  $stmtTicket = $pdo->prepare("SELECT t.id_tickets, t.departamento_id, t.titulo, TRIM(CONCAT(COALESCE(p.nombre,''),' ',COALESCE(p.apellido,''))) AS solicitante, p.cedula FROM tickets t LEFT JOIN perfiles_usuarios p ON p.usuario_id=t.solicitante_id WHERE t.id_tickets=? AND t.analista_id=? AND t.estado IN ('asignado','en_proceso')");
  $stmtTicket->execute([$ticketId, $u['id']]);
  $ticket = $stmtTicket->fetch();
  if (!$ticket) {
    unset($_SESSION['ticket_pendiente']);
    $ticketId = 0;
  }
}

if (!empty($_SESSION['hoja_en_curso'])) {
    header('Location: hoja-servicio.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar servicio | Soporte Técnico</title>
<link rel="icon" href="../assets/img/logo.png">
<link rel="manifest" href="../manifest.json">
<meta name="theme-color" content="#0A3F3A">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="app-shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <div class="content d-flex flex-column align-items-center">
      <div class="card-panel w-100" style="max-width:480px;">
        <div class="panel-body text-center">
          <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:64px;height:64px;border-radius:50%;background:var(--teal-100);color:var(--teal-700);font-size:1.8rem;">
            <i class="bi bi-stopwatch"></i>
          </div>
          <h2 class="fs-5 fw-bold mb-1">¿A qué oficina vas a atender?</h2>
          <p class="text-muted small mb-4">Selecciona el departamento. Al presionar "Iniciar servicio" quedará registrada la hora exacta de inicio y se notificará a secretaría.</p>

          <?php if ($ticket): ?>
            <div class="alert alert-info text-start small">
              <strong>Ticket #<?= str_pad((string)$ticket['id_tickets'], 6, '0', STR_PAD_LEFT) ?></strong>
              · <?= h($ticket['titulo']) ?><br>
              Usuario atendido: <?= h(trim($ticket['solicitante']) ?: 'Sin nombre') ?><?= !empty($ticket['cedula']) ? ' · C.I. ' . h($ticket['cedula']) : '' ?>
            </div>
          <?php endif; ?>

          <form action="iniciar-servicio-procesar.php" method="post"><?= csrf_field() ?>
            <?php if ($ticket): ?><input type="hidden" name="ticket_id" value="<?= (int)$ticket['id_tickets'] ?>"><?php endif; ?>
            <select name="departamento_id" class="form-select form-select-lg mb-3" required <?= $ticket ? 'disabled' : '' ?>>
              <option value="">Seleccione un departamento…</option>
              <?php foreach ($departamentos as $d): ?>
                <option value="<?= $d['id_departamentos'] ?>" <?= $ticket && (int)$ticket['departamento_id'] === (int)$d['id_departamentos'] ? 'selected' : '' ?>><?= h($d['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($ticket): ?><div class="form-text mt-n2 mb-3">Departamento cargado desde el ticket.</div><?php endif; ?>

            <div class="d-flex justify-content-between align-items-center border rounded-3 p-3 mb-4" style="background:var(--paper);">
              <span class="small text-muted">Hora de inicio</span>
              <span class="fw-bold" style="font-family:var(--font-display);" id="horaAhora">--:--</span>
            </div>

            <button type="submit" class="btn btn-brand btn-lg w-100 py-3">
              <i class="bi bi-play-circle-fill me-2"></i>Iniciar servicio ahora
            </button>
          </form>
          <a href="dashboard.php" class="d-inline-block mt-3 small text-muted">Cancelar y volver a mi panel</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
document.getElementById('sidebarToggle')?.addEventListener('click',()=>document.getElementById('sidebar').classList.toggle('show'));
function tick(){
  document.getElementById('horaAhora').textContent = new Date().toLocaleTimeString('es-VE', {hour:'2-digit', minute:'2-digit'});
}
tick(); setInterval(tick, 1000);
</script>
</body>
</html>