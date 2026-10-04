<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requerirRol(['analista']);
$u = usuarioActual();

if (empty($_SESSION['hoja_en_curso'])) {
    header('Location: iniciar-servicio.php');
    exit;
}
$enCurso = $_SESSION['hoja_en_curso'];
$esContinuacion = !empty($enCurso['continuacion_id']);
$ticket = null;
if (!$esContinuacion && !empty($enCurso['ticket_id'])) {
  $stmtTicket = $pdo->prepare("SELECT t.id_tickets, t.titulo, TRIM(CONCAT(COALESCE(p.nombre,''),' ',COALESCE(p.apellido,''))) AS solicitante, p.cedula FROM tickets t LEFT JOIN perfiles_usuarios p ON p.usuario_id=t.solicitante_id WHERE t.id_tickets=? AND t.analista_id=?");
  $stmtTicket->execute([(int)$enCurso['ticket_id'], $u['id']]);
  $ticket = $stmtTicket->fetch();
  if (!$ticket) {
    unset($_SESSION['hoja_en_curso']);
    header('Location: tickets.php');
    exit;
  }
}
$hojaOriginal = null;
if ($esContinuacion) {
    $q=$pdo->prepare('SELECT hs.*, d.nombre AS departamento FROM hojas_servicio hs JOIN departamentos d ON d.id_departamentos=hs.departamento_solicitante_id WHERE hs.id_hojas=? AND hs.tecnico_id=? AND hs.estatus IN ("en_proceso","pendiente_insumos")');
    $q->execute([(int)$enCurso['continuacion_id'],$u['id']]);
    $hojaOriginal=$q->fetch();
    if(!$hojaOriginal){ unset($_SESSION['hoja_en_curso']); header('Location:mis_hojas.php'); exit; }
}

$departamentoId = $esContinuacion
    ? $hojaOriginal['departamento_solicitante_id']
    : $enCurso['departamento_id'];

$departamento = $pdo->prepare(
    "SELECT nombre FROM departamentos WHERE id_departamentos = :id"
);
$departamento->execute(['id' => $departamentoId]);
$departamento = $departamento->fetchColumn();

$tipos = $pdo->query("SELECT id_servicios, codigo, nombre FROM tipos_servicio WHERE estado='activo' ORDER BY codigo")->fetchAll();

$error = $_GET['error'] ?? '';
$mostrarBannerInicio = isset($_GET['iniciada']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Finalizar hoja de servicio | Soporte Técnico Carrizal</title>
<link rel="icon" href="../assets/img/logo.png">
<link rel="manifest" href="../manifest.json">
<meta name="theme-color" content="#0A3F3A">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
<link href="../assets/css/style.css?v=20260921-2" rel="stylesheet">
</head>
<body>

<div class="app-shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <div class="content worksheet-form-page">
      <?php if ($mostrarBannerInicio): ?>
        <div class="alert d-flex align-items-center gap-2" style="background:var(--teal-100);color:var(--teal-900);border:none;">
          <i class="bi bi-bell-fill"></i>
          <div><strong>Servicio iniciado a las <?= substr($enCurso['hora_inicio'],0,5) ?>.</strong> Se avisó a secretaría que comenzaste en <?= h($departamento) ?>. Completa los datos abajo cuando termines.</div>
        </div>
      <?php endif; ?>

      <?php if ($error === '1'): ?><div class="alert alert-danger">Faltan campos obligatorios. Revisa el formulario.</div><?php endif; ?>
      <?php if ($error === '2'): ?><div class="alert alert-danger">Debes marcar al menos un tipo de servicio.</div><?php endif; ?>
      <?php if ($error === '3'): ?><div class="alert alert-danger">Debes firmar en pantalla para confirmar la conformidad del servicio.</div><?php endif; ?>
      <?php if ($error === '4'): ?><div class="alert alert-danger">Debes escribir la descripción de culminación del servicio.</div><?php endif; ?>

      <?php if ($esContinuacion): ?>
      <form class="card-panel" action="guardar_hoja.php" method="post"><?= csrf_field() ?>
        <div class="panel-body">
          <div class="alert" style="background:var(--teal-100);color:var(--teal-900);border:none;">
            <strong><i class="bi bi-clipboard-check me-1"></i>Culminación de la hoja N° <?= numeroHoja($hojaOriginal['id_hojas']) ?>.</strong>
            Esta es la misma hoja de servicio. Al guardar, su estatus cambiará a <strong>Completado</strong>.
          </div>
          <div class="form-section form-section-mobile-half">
            <p class="fs-title"><span class="fs-num">1</span>Datos generales</p>
            <div class="row g-3"><div class="col-md-6"><label class="form-label">Departamento</label><input class="form-control" value="<?= h($hojaOriginal['departamento']) ?>" disabled></div><div class="col-md-3"><label class="form-label">Fecha</label><input class="form-control" value="<?= date('d/m/Y',strtotime($hojaOriginal['fecha'])) ?>" disabled></div><div class="col-md-3"><label class="form-label">Hora de inicio</label><input class="form-control" value="<?= substr($hojaOriginal['hora_inicio'],0,5) ?>" disabled></div></div>
          </div>
          <div class="form-section">
            <p class="fs-title"><span class="fs-num">4</span>Detalle registrado inicialmente</p>
            <div class="form-control" style="min-height:110px;white-space:pre-wrap;background:var(--paper)"><?= h($hojaOriginal['descripcion']) ?></div>
          </div>
          <div class="form-section form-section-mobile-half">
            <p class="fs-title"><span class="fs-num">5</span>Estatus del servicio</p>
            <div class="form-control" style="background:var(--paper)"><strong><?= $hojaOriginal['estatus']==='en_proceso'?'En proceso':'Pendiente por repuestos / insumos' ?></strong> <span class="text-muted">→ al culminar cambiará a Completado</span></div>
          </div>
          <div class="form-section mb-0">
            <p class="fs-title"><span class="fs-num">6</span>Descripción para culminar el servicio</p>
            <textarea name="descripcion_culminacion" class="form-control mt-1" rows="5" required placeholder="Indique qué se realizó para culminar el servicio, resultados y cualquier información final…"></textarea>
            <div class="form-text">Esta descripción se agregará a la misma hoja de servicio N° <?= numeroHoja($hojaOriginal['id_hojas']) ?>.</div>
          </div>
        </div>
        <div class="panel-body border-top d-flex justify-content-end gap-2"><a href="mis_hojas.php" class="btn btn-outline-brand">Cancelar</a><button class="btn btn-brand" type="submit"><i class="bi bi-check2-circle me-1"></i>Culminar servicio</button></div>
      </form>
      <?php else: ?>
      <form class="card-panel" action="guardar_hoja.php" method="post" enctype="multipart/form-data" id="formHoja"><?= csrf_field() ?>
        <div class="panel-body">

          <div class="form-section form-section-mobile-half">
            <p class="fs-title"><span class="fs-num">1</span>Datos generales</p>
            <div class="row g-3 mt-1">
              <div class="col-md-4">
                <label class="form-label">Técnico / Especialista</label>
                <input type="text" class="form-control" value="<?= h($u['nombre_completo']) ?>" disabled>
              </div>
              <div class="col-md-5">
                <label class="form-label">Departamento u oficina solicitante</label>
                <input type="text" class="form-control" value="<?= h($departamento) ?>" disabled>
              </div>
              <div class="col-md-3">
                <label class="form-label">Fecha</label>
                <input type="text" class="form-control" value="<?= date('d/m/Y', strtotime($enCurso['fecha'])) ?>" disabled>
              </div>
            </div>
            <?php if ($ticket): ?>
              <div class="mt-3 p-3 rounded-3" style="background:var(--paper);">
                <div class="small text-muted">Ticket relacionado</div>
                <strong>#<?= str_pad((string)$ticket['id_tickets'], 6, '0', STR_PAD_LEFT) ?></strong>
                · <?= h($ticket['titulo']) ?>
              </div>
            <?php endif; ?>
          </div>

          <div class="form-section form-section-mobile-half">
            <p class="fs-title"><span class="fs-num">2</span>Tipo de servicio</p>
            <div class="row g-2 mt-1">
              <?php foreach ($tipos as $t): ?>
              <div class="col-md-6">
                <label class="service-check">
                  <input class="form-check-input m-0" type="checkbox" name="tipos[]" value="<?= $t['id_servicios'] ?>">
                  <span>
                    <?= $t['codigo'] ?>. <?= h($t['nombre']) ?>
                    <?php if ($t['codigo'] == 10): ?>
                      <input type="text" name="otro_especifique" class="form-control form-control-sm d-inline-block ms-1" style="width:140px;" placeholder="especifique">
                    <?php endif; ?>
                  </span>
                </label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-section form-section-mobile-half">
            <p class="fs-title"><span class="fs-num">3</span>Tiempo de ejecución</p>
            <div class="row g-3 mt-1">
              <div class="col-md-6">
                <label class="form-label">Hora de inicio</label>
                <input type="text" class="form-control" value="<?= substr($enCurso['hora_inicio'],0,5) ?>" disabled>
                <div class="form-text">Se fijó automáticamente cuando tocaste "Iniciar servicio".</div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Hora de finalización</label>
                <input type="text" class="form-control" value="Se registra al guardar" disabled>
                <div class="form-text">No hace falta anotarla: queda marcada sola al presionar "Guardar".</div>
              </div>
            </div>
          </div>

          <div class="form-section">
            <p class="fs-title"><span class="fs-num">4</span>Detalle del servicio y materiales utilizados</p>
            <textarea id="descripcionServicio" name="descripcion" class="form-control mt-1" rows="4" placeholder="Describa el servicio realizado y los materiales/insumos utilizados…" required></textarea>
          </div>

          <div class="form-section form-section-mobile-half">
            <p class="fs-title"><span class="fs-num">5</span>Estatus del servicio</p>
            <div class="d-flex flex-column flex-sm-row gap-2 mt-1 status-pick">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="estatus" id="estCompletado" value="completado" checked>
                <label class="form-check-label" for="estCompletado">Completado</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="estatus" id="estProceso" value="en_proceso">
                <label class="form-check-label" for="estProceso">En proceso</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="estatus" id="estPendiente" value="pendiente_insumos">
                <label class="form-check-label" for="estPendiente">Pendiente por repuestos / insumos</label>
              </div>
            </div>
          </div>

          <div class="form-section">
            <p class="fs-title"><span class="fs-num">6</span>Evidencia del servicio</p>
            <label class="form-label">Tomar o adjuntar evidencia (fotos)</label>
            <input type="file" class="form-control" name="evidencia" accept="image/*" capture="environment">
            <div class="form-text">Solo se permite una imagen. Desde un teléfono puedes tomar la fotografía directamente con la cámara.</div>
          </div>

          <div class="form-section conformidad-section mb-0">
            <p class="fs-title"><span class="fs-num">7</span>Conformidad</p>
            <div class="row g-3 mt-1">
              <div class="col-12 col-md-7 conformidad-usuario">
                <label class="form-label">Usuario atendido — nombre y cédula</label>
                <input type="text" name="usuario_atendido_nombre" class="form-control mb-2" value="<?= $ticket ? h(trim($ticket['solicitante']) . (!empty($ticket['cedula']) ? ' / C.I. ' . $ticket['cedula'] : '')) : '' ?>" placeholder="Nombre completo / C.I." required <?= $ticket ? 'readonly' : '' ?>>

                <div class="border rounded-3 position-relative" style="background:var(--paper);">
                  <canvas id="signaturePad" style="width:100%;height:150px;display:block;touch-action:none;user-select:none;-webkit-user-select:none;cursor:crosshair;pointer-events:auto;"></canvas>
                  <span id="firmaPlaceholder" class="text-muted small position-absolute top-50 start-50 translate-middle" style="pointer-events:none;">
                    <i class="bi bi-vector-pen me-1"></i>Firme aquí con el dedo
                  </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <span class="small text-muted">La firma en pantalla es obligatoria para confirmar la conformidad del servicio.</span>
                  <button type="button" id="btnLimpiarFirma" class="btn btn-outline-brand btn-sm flex-shrink-0 ms-2"><i class="bi bi-eraser me-1"></i>Limpiar</button>
                </div>
                <input type="hidden" name="firma_dataurl" id="firmaDataUrl">
              </div>

              <div class="col-12 col-md-5 especialista-tecnico">
                <label class="form-label">Especialista / técnico</label>
                <input type="text" class="form-control" value="<?= h($u['nombre_completo']) ?>" disabled>
              </div>
            </div>
          </div>

        </div>

        <div class="panel-body border-top d-flex flex-column flex-sm-row gap-2 justify-content-end">
          <a href="dashboard.php" class="btn btn-outline-brand order-2 order-sm-1">Guardar y salir después</a>
          <button type="submit" class="btn btn-brand order-1 order-sm-3 py-2"><i class="bi bi-check2-circle me-1"></i>Finalizar y guardar</button>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/4.1.7/signature_pad.umd.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script nonce="<?=htmlspecialchars(soporteCspNonce(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
document.getElementById('sidebarToggle')?.addEventListener('click',()=>document.getElementById('sidebar').classList.toggle('show'));

const canvas = document.getElementById('signaturePad');
const placeholder = document.getElementById('firmaPlaceholder');
let pad = null;
let firmaResizeFrame = null;

function initSignaturePad() {
  if (!canvas) return;

  canvas.style.touchAction = 'none';
  canvas.style.cursor = 'crosshair';
  canvas.style.userSelect = 'none';
  canvas.style.webkitUserSelect = 'none';

  const savedData = pad ? pad.toData() : [];
  const rect = canvas.getBoundingClientRect();
  const width = Math.max(Math.round(rect.width || canvas.clientWidth || 300), 300);
  const height = Math.max(Math.round(rect.height || 150), 120);
  const ratio = Math.max(window.devicePixelRatio || 1, 1);

  canvas.width = Math.round(width * ratio);
  canvas.height = Math.round(height * ratio);

  const ctx = canvas.getContext('2d');
  if (ctx) {
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.scale(ratio, ratio);
  }

  pad = new SignaturePad(canvas, {
    penColor: '#16231F',
    backgroundColor: 'rgba(0,0,0,0)',
    minWidth: 1,
    maxWidth: 2,
    throttle: 16,
    velocityFilterWeight: 0.7
  });

  if (savedData.length) {
    pad.fromData(savedData);
    if (placeholder) placeholder.style.display = 'none';
  }

  pad.addEventListener('beginStroke', () => {
    if (placeholder) placeholder.style.display = 'none';
  });
}

window.addEventListener('resize', () => {
  if (firmaResizeFrame) cancelAnimationFrame(firmaResizeFrame);
  firmaResizeFrame = requestAnimationFrame(() => {
    const savedData = pad ? pad.toData() : [];
    initSignaturePad();
    if (savedData.length && pad) {
      pad.fromData(savedData);
      if (placeholder) placeholder.style.display = 'none';
    }
  });
});

initSignaturePad();

document.getElementById('btnLimpiarFirma').addEventListener('click', () => { if (pad) { pad.clear(); } if (placeholder) placeholder.style.display = 'block'; });

async function comprimirEvidencia(input){
  const archivo = input.files[0];
  if(!archivo || !archivo.type.startsWith('image/')) return;

  try {
    const imagen = await createImageBitmap(archivo);
    const maximo = 1600;
    const escala = Math.min(1, maximo / Math.max(imagen.width, imagen.height));
    const canvasImagen = document.createElement('canvas');
    canvasImagen.width = Math.max(1, Math.round(imagen.width * escala));
    canvasImagen.height = Math.max(1, Math.round(imagen.height * escala));
    canvasImagen.getContext('2d').drawImage(imagen, 0, 0, canvasImagen.width, canvasImagen.height);

    const blob = await new Promise(resolve => canvasImagen.toBlob(resolve, 'image/jpeg', .78));
    if(!blob || blob.size >= archivo.size) return;

    const nombre = archivo.name.replace(/\.[^.]+$/, '') + '.jpg';
    const archivoComprimido = new File([blob], nombre, {type:'image/jpeg', lastModified:Date.now()});
    const transferencia = new DataTransfer();
    transferencia.items.add(archivoComprimido);
    input.files = transferencia.files;
  } catch(error) {
    console.warn('No se pudo comprimir la evidencia antes de subirla.', error);
  }
}

document.getElementById('formHoja').addEventListener('submit', async function(e){
  e.preventDefault();
  if (pad && !pad.isEmpty()) {
    document.getElementById('firmaDataUrl').value = pad.toDataURL('image/png');
  } else {
    alert('Debes firmar en pantalla para confirmar la conformidad del servicio.');
    return false;
  }
  const tiposMarcados = document.querySelectorAll('input[name="tipos[]"]:checked').length;
  if(tiposMarcados === 0){
    alert('Debes marcar al menos un tipo de servicio.');
    return false;
  }
  await comprimirEvidencia(document.querySelector('input[name="evidencia"]'));
  this.submit();
});
</script>
<?php include __DIR__ . '/../includes/alert_modal.php'; ?>
</body>
</html>